<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Response;

use Andy87\ClientsBase\Contracts\ResponseInterface;
use Andy87\ClientsBase\Dto\ApiError;

/**
 * Базовый DTO ответа с гидрацией, ошибкой и проверкой обязательных полей.
 */
abstract class AbstractResponse implements ResponseInterface
{
    /** @var array<string, string> Карта PHP-свойств в имена полей API. */
    protected const FIELD_MAP = [];

    /** @var list<string> Обязательные PHP-свойства ответа. */
    protected const REQUIRED_FIELDS = [];

    /** @var list<string> PHP-свойства, которые могут быть null по OpenAPI nullable. */
    protected const NULLABLE_FIELDS = [];

    /** @var array<string, class-string|array{0:class-string}> Правила преобразования вложенных моделей. */
    protected const CASTS = [];

    /** @var class-string|null OpenAPI schema-модель всего ответа. */
    protected const MODEL = null;

    /** @var ApiError|null Данные ошибки API. */
    public ?ApiError $error = null;

    /** @var int HTTP-статус ответа. */
    public int $statusCode = 0;

    /** @var array<string, string> Заголовки ответа. */
    public array $headers = [];

    /** @var array<string, mixed>|list<mixed> Исходные данные ответа. */
    protected array $raw = [];

    /** @var object|null OpenAPI schema-модель всего ответа. */
    public ?object $model = null;

    /**
     * Создаёт DTO ответа.
     *
     * @param array<string, mixed>|list<mixed> $data Данные ответа.
     * @param ApiError|null $error Данные ошибки.
     * @param int $statusCode HTTP-статус.
     * @param array<string, string> $headers Заголовки ответа.
     *
     * @return void
     *
     * @throws \UnexpectedValueException Если обязательное поле ответа отсутствует.
     */
    public function __construct(array $data = [], ?ApiError $error = null, int $statusCode = 0, array $headers = [])
    {
        $this->raw = $data;
        $this->error = $error;
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        if ($error === null && is_string(static::MODEL) && class_exists(static::MODEL)) {
            $this->model = new (static::MODEL)($data);
        }

        foreach (static::FIELD_MAP as $property => $apiName) {
            if (array_key_exists($apiName, $data)) {
                $this->{$property} = $this->cast($property, $data[$apiName]);
            }
        }

        if ($error === null) {
            $this->validateRequiredFields();
        }
    }

    /**
     * Проверяет, содержит ли ответ ошибку.
     *
     * @return bool true, если есть ошибка.
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Возвращает ошибку ответа.
     *
     * @return ApiError|null Данные ошибки.
     */
    public function getError(): ?ApiError
    {
        return $this->error;
    }

    /**
     * Возвращает исходные данные ответа.
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * Проверяет обязательные поля ответа.
     *
     * @return void
     *
     * @throws \UnexpectedValueException Если обязательное поле отсутствует.
     */
    protected function validateRequiredFields(): void
    {
        foreach (static::REQUIRED_FIELDS as $property) {
            if (!$this->isPropertyInitialized($property)) {
                throw new \UnexpectedValueException(sprintf('Required response field "%s" is missing.', $property));
            }

            $value = $this->{$property};

            if ($value === null && !in_array($property, static::NULLABLE_FIELDS, true)) {
                throw new \UnexpectedValueException(sprintf('Required response field "%s" is missing.', $property));
            }
        }
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
     * Применяет cast-правило к значению ответа.
     *
     * @param string $property PHP-свойство.
     * @param mixed $value Значение ответа.
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
}
