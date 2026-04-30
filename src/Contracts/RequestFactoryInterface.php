<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Создаёт HTTP-запрос из Prompt DTO и настроек клиента.
 */
interface RequestFactoryInterface
{
    /**
     * Собирает HTTP-запрос для транспорта.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param string $baseUrl Базовый URL API.
     * @param array<string, string> $headers Заголовки запроса.
     * @param int $timeout Таймаут запроса в секундах.
     * @param array<string, mixed> $extraQuery Дополнительные query-параметры.
     *
     * @return HttpRequest HTTP-запрос.
     *
     * @throws \InvalidArgumentException Если endpoint или path-параметры некорректны.
     */
    public function create(
        PromptInterface $prompt,
        string $baseUrl,
        array $headers,
        int $timeout,
        array $extraQuery = [],
    ): HttpRequest;
}
