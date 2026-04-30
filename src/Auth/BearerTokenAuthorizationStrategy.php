<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Auth;

use Andy87\ClientsBase\Contracts\AuthorizationStrategyInterface;
use Andy87\ClientsBase\Contracts\HttpTransportInterface;

/**
 * Добавляет статический Bearer token в заголовок Authorization.
 */
class BearerTokenAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * Создаёт стратегию Bearer token.
     *
     * @param string $token Access token.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если token пустой.
     */
    public function __construct(
        private string $token,
    ) {
        if ($token === '') {
            throw new \InvalidArgumentException('Bearer token must be a non-empty string.');
        }
    }

    /**
     * Возвращает Bearer-заголовок авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для служебных запросов авторизации.
     *
     * @return array<string, string>
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }
}
