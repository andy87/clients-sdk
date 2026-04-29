<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Auth;

use Andy87\ClientsBase\Contracts\AuthorizationStrategyInterface;
use Andy87\ClientsBase\Contracts\HttpTransportInterface;

/**
 * Отключает авторизацию для публичных API-методов.
 */
class NullAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * Возвращает пустой набор заголовков авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт.
     *
     * @return array<string, string>
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        return [];
    }
}
