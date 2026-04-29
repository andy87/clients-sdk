<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Http;

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
        if ($this->body === '') {
            return [];
        }

        $data = json_decode($this->body, true);

        if (!is_array($data)) {
            throw new \RuntimeException('API returned non-JSON response.');
        }

        return $data;
    }
}
