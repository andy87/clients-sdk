<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Http;

use Andy87\ClientsBase\Decoder\JsonResponseDecoder;

/**
 * Хранит данные HTTP-ответа.
 */
class HttpResponse
{
    /**
     * Создаёт HTTP-ответ.
     *
     * @param int $statusCode HTTP-статус.
     * @param array<string, string> $headers Заголовки ответа.
     * @param string $body Тело ответа.
     *
     * @return void
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
    ) {
    }

    /**
     * Декодирует JSON-тело ответа.
     *
     * @return array<string, mixed>|list<mixed>
     *
     * @throws \RuntimeException Если JSON некорректен.
     */
    public function json(): array
    {
        return (new JsonResponseDecoder())->decode($this);
    }
}
