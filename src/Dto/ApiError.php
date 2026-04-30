<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Dto;

/**
 * Хранит нормализованные данные ошибки API.
 */
class ApiError
{
    /** @var int|string|null Машинный код ошибки API или HTTP-статус при отсутствии кода API. */
    public int|string|null $code;

    /** @var int|null HTTP-статус ответа. */
    public ?int $statusCode;

    /** @var string|null Текст ошибки. */
    public ?string $message;

    /** @var string|null Тип ошибки. */
    public ?string $type;

    /** @var mixed Дополнительное значение ошибки. */
    public mixed $value;

    /** @var array<string, mixed>|list<mixed>|null Детали ошибки. */
    public array|null $details;

    /** @var array<string, mixed>|list<mixed> Исходное тело ошибки. */
    public array $raw;

    /** @var array<string, string> Заголовки ответа. */
    public array $headers;

    /** @var string Raw тело ответа. */
    public string $rawBody;

    /**
     * Создаёт DTO ошибки API.
     *
     * @param array<string, mixed>|list<mixed> $raw Исходное тело ошибки.
     * @param int|null $statusCode HTTP-статус ответа.
     * @param array<string, string> $headers Заголовки ответа.
     * @param string $rawBody Raw тело ответа.
     *
     * @return void
     */
    public function __construct(
        array $raw = [],
        ?int $statusCode = null,
        array $headers = [],
        string $rawBody = '',
    ) {
        $error = is_array($raw['error'] ?? null) ? $raw['error'] : $raw;
        $code = $error['code'] ?? null;

        $this->statusCode = $statusCode;
        $this->code = is_int($code) || is_string($code) ? $code : $statusCode;
        $this->message = isset($error['message']) ? (string) $error['message'] : null;
        $this->type = isset($error['type']) ? (string) $error['type'] : null;
        $this->value = $error['value'] ?? null;
        $this->details = is_array($error['details'] ?? null) ? $error['details'] : null;
        $this->raw = $raw;
        $this->headers = $headers;
        $this->rawBody = $rawBody;
    }
}
