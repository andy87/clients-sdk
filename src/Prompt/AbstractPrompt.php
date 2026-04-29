<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Prompt;

use Andy87\ClientsBase\Contracts\PromptInterface;

/**
 * Базовый DTO запроса с гидрацией и разбором path/query/body-полей.
 */
abstract class AbstractPrompt implements PromptInterface
{
    /** @var array<string, string> Карта PHP-свойств в имена полей API. */
    protected const FIELD_MAP = [];

    /** @var list<string> Обязательные PHP-свойства. */
    protected const REQUIRED_FIELDS = [];

    /** @var list<string> PHP-свойства, которые могут быть null по OpenAPI nullable. */
    protected const NULLABLE_FIELDS = [];

    /** @var array<string, class-string|array{0:class-string}> Правила преобразования вложенных моделей. */
    protected const CASTS = [];

    /** @var list<string> PHP-свойства path-параметров. */
    protected const PATH_FIELDS = [];

    /** @var list<string> PHP-свойства query-параметров. */
    protected const QUERY_FIELDS = [];

    /** @var list<string> PHP-свойства тела запроса. */
    protected const BODY_FIELDS = [];

    /** @var string|null Content-Type тела запроса. */
    protected const CONTENT_TYPE = null;

    /** @var bool Нужно ли добавлять авторизацию к запросу. */
    protected const AUTHORIZATION_REQUIRED = true;

    /** @var array<string, mixed> Исходные данные конструктора. */
    protected array $raw = [];

    /**
     * Создаёт DTO запроса.
     *
     * @param array<string, mixed> $data Значения полей запроса.
     *
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->raw = $data;

        foreach (static::FIELD_MAP as $property => $apiName) {
            if (array_key_exists($property, $data)) {
                $this->{$property} = $this->cast($property, $data[$property]);
                continue;
            }

            if (array_key_exists($apiName, $data)) {
                $this->{$property} = $this->cast($property, $data[$apiName]);
            }
        }
    }

    /**
     * Возвращает HTTP-метод запроса.
     *
     * @return string HTTP-метод.
     */
    public function getMethod(): string
    {
        return static::METHOD;
    }

    /**
     * Возвращает endpoint запроса.
     *
     * @return string Endpoint.
     */
    public function getEndpoint(): string
    {
        return static::ENDPOINT;
    }

    /**
     * Возвращает Content-Type тела запроса.
     *
     * @return string|null Content-Type или null.
     */
    public function getContentType(): ?string
    {
        return static::CONTENT_TYPE;
    }

    /**
     * Проверяет, нужна ли авторизация для запроса.
     *
     * @return bool true, если запрос требует авторизацию.
     */
    public function requiresAuthorization(): bool
    {
        return static::AUTHORIZATION_REQUIRED;
    }

    /**
     * Возвращает path-параметры.
     *
     * @return array<string, mixed>
     */
    public function getPathParameters(): array
    {
        return $this->collect(static::PATH_FIELDS);
    }

    /**
     * Возвращает query-параметры.
     *
     * @return array<string, mixed>
     */
    public function getQueryParameters(): array
    {
        return $this->collect(static::QUERY_FIELDS);
    }

    /**
     * Возвращает тело запроса.
     *
     * @return array<string, mixed>|list<mixed>|null
     */
    public function getBody(): array|null
    {
        if (static::BODY_FIELDS === []) {
            return null;
        }

        return $this->collect(static::BODY_FIELDS);
    }

    /**
     * Проверяет обязательные поля запроса.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если обязательное поле не заполнено.
     */
    public function validate(): void
    {
        foreach (static::REQUIRED_FIELDS as $property) {
            if (!$this->isPropertyInitialized($property)) {
                throw new \InvalidArgumentException(sprintf('Field "%s" is required.', $property));
            }

            $value = $this->{$property};

            if (($value === null && !in_array($property, static::NULLABLE_FIELDS, true)) || $value === []) {
                throw new \InvalidArgumentException(sprintf('Field "%s" is required.', $property));
            }
        }
    }

    /**
     * Собирает значения указанных PHP-свойств с именами API.
     *
     * @param list<string> $properties PHP-свойства.
     *
     * @return array<string, mixed>
     */
    protected function collect(array $properties): array
    {
        $result = [];

        foreach ($properties as $property) {
            if (!$this->isPropertyInitialized($property)) {
                continue;
            }

            $value = $this->{$property};
            $includeNull = $value === null
                && in_array($property, static::REQUIRED_FIELDS, true)
                && in_array($property, static::NULLABLE_FIELDS, true);

            if ($value === null && !$includeNull) {
                continue;
            }

            $apiName = static::FIELD_MAP[$property] ?? $property;
            $result[$apiName] = $this->normalize($value);
        }

        return $result;
    }

    /**
     * Проверяет, что typed-свойство существует и инициализировано.
     *
     * @param string $property Имя PHP-свойства.
     *
     * @return bool true, если свойство можно безопасно читать.
     */
    private function isPropertyInitialized(string $property): bool
    {
        if (!property_exists($this, $property)) {
            return false;
        }

        $reflection = new \ReflectionProperty($this, $property);

        return $reflection->isInitialized($this);
    }

    /**
     * Применяет cast-правило к значению запроса.
     *
     * @param string $property PHP-свойство.
     * @param mixed $value Значение запроса.
     *
     * @return mixed Преобразованное значение.
     */
    private function cast(string $property, mixed $value): mixed
    {
        if ($value === null || !array_key_exists($property, static::CASTS)) {
            return $value;
        }

        $cast = static::CASTS[$property];

        if (is_array($cast)) {
            $className = $cast[0] ?? null;

            if (!is_string($className) || !is_array($value)) {
                return $value;
            }

            return array_map(
                static fn (mixed $item): mixed => is_array($item) ? new $className($item) : $item,
                $value,
            );
        }

        if (is_string($cast)) {
            return new $cast($value);
        }

        return $value;
    }

    /**
     * Нормализует вложенные DTO/модели в массивы перед отправкой.
     *
     * @param mixed $value Значение.
     *
     * @return mixed Нормализованное значение.
     */
    private function normalize(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'toValue')) {
            return $value->toValue();
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
        }

        return $value;
    }
}
