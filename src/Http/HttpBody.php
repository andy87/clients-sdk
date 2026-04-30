<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Http;

/**
 * Хранит закодированное тело HTTP-запроса и связанные с ним заголовки.
 */
class HttpBody
{
    /**
     * Создаёт закодированное тело HTTP-запроса.
     *
     * @param string|null $content Содержимое тела или null, если тело отсутствует.
     * @param string|null $contentType Content-Type тела.
     * @param array<string, string> $headers Дополнительные заголовки тела.
     *
     * @return void
     */
    public function __construct(
        public ?string $content = null,
        public ?string $contentType = null,
        public array $headers = [],
    ) {
    }
}
