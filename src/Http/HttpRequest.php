<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Http;

/**
 * Хранит данные исходящего HTTP-запроса.
 */
class HttpRequest
{
    /**
     * Создаёт HTTP-запрос.
     *
     * @param string $method HTTP-метод.
     * @param string $url Полный URL.
     * @param array<string, string> $headers Заголовки.
     * @param array<string, mixed> $query Query-параметры.
     * @param array<string, mixed>|list<mixed>|null $body Тело запроса.
     * @param string|null $contentType Content-Type тела.
     * @param int $timeout Таймаут в секундах.
     *
     * @return void
     */
    public function __construct(
        public string $method,
        public string $url,
        public array $headers = [],
        public array $query = [],
        public array|null $body = null,
        public ?string $contentType = null,
        public int $timeout = 30,
    ) {
    }
}
