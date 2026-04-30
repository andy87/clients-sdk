<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Auth;

use Andy87\ClientsBase\Contracts\AuthorizationQueryStrategyInterface;
use Andy87\ClientsBase\Contracts\AuthorizationStrategyInterface;
use Andy87\ClientsBase\Contracts\HttpTransportInterface;

/**
 * Добавляет API key в header или query-параметр.
 */
class ApiKeyAuthorizationStrategy implements AuthorizationStrategyInterface, AuthorizationQueryStrategyInterface
{
    public const LOCATION_HEADER = 'header';
    public const LOCATION_QUERY = 'query';

    /**
     * Создаёт стратегию API key.
     *
     * @param string $name Имя header или query-параметра.
     * @param string $value Значение API key.
     * @param string $location Место передачи ключа: header или query.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если параметры некорректны.
     */
    public function __construct(
        private string $name,
        private string $value,
        private string $location = self::LOCATION_HEADER,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('API key name must be a non-empty string.');
        }

        if ($value === '') {
            throw new \InvalidArgumentException('API key value must be a non-empty string.');
        }

        if (!in_array($location, [self::LOCATION_HEADER, self::LOCATION_QUERY], true)) {
            throw new \InvalidArgumentException('API key location must be "header" or "query".');
        }
    }

    /**
     * Возвращает заголовки авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для служебных запросов авторизации.
     *
     * @return array<string, string>
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        if ($this->location !== self::LOCATION_HEADER) {
            return [];
        }

        return [$this->name => $this->value];
    }

    /**
     * Возвращает query-параметры авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для служебных запросов авторизации.
     *
     * @return array<string, mixed>
     */
    public function getAuthorizationQueryParameters(HttpTransportInterface $transport): array
    {
        if ($this->location !== self::LOCATION_QUERY) {
            return [];
        }

        return [$this->name => $this->value];
    }
}
