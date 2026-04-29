<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Dto;

/**
 * Хранит нормализованные данные ошибки API.
 */
class ApiError
{
    /** @var int|null HTTP-статус или код ошибки API. */
    public ?int $code;

    /** @var string|null Текст ошибки. */
    public ?string $message;

    /** @var string|null Тип ошибки. */
    public ?string $type;

    /** @var mixed Дополнительное значение ошибки. */
    public mixed $value;

    /** @var array<string, mixed>|list<mixed> Исходное тело ошибки. */
    public array $raw;

    /**
     * Создаёт DTO ошибки API.
     *
     * @param array<string, mixed>|list<mixed> $raw Исходное тело ошибки.
     * @param int|null $statusCode HTTP-статус ответа.
     *
     * @return void
     */
    public function __construct(array $raw = [], ?int $statusCode = null)
    {
        $error = is_array($raw['error'] ?? null) ? $raw['error'] : $raw;

        $this->code = isset($error['code']) ? (int) $error['code'] : $statusCode;
        $this->message = isset($error['message']) ? (string) $error['message'] : null;
        $this->type = isset($error['type']) ? (string) $error['type'] : null;
        $this->value = $error['value'] ?? null;
        $this->raw = $raw;
    }
}
