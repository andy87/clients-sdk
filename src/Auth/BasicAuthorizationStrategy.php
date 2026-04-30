<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Auth;

use Andy87\ClientsBase\Contracts\AuthorizationStrategyInterface;
use Andy87\ClientsBase\Contracts\HttpTransportInterface;

/**
 * Добавляет HTTP Basic Authorization.
 */
class BasicAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * Создаёт стратегию Basic authorization.
     *
     * @param string $username Имя пользователя.
     * @param string $password Пароль.
     *
     * @return void
     */
    public function __construct(
        private string $username,
        private string $password,
    ) {
    }

    /**
     * Возвращает Basic-заголовок авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для служебных запросов авторизации.
     *
     * @return array<string, string>
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        return ['Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password)];
    }
}
