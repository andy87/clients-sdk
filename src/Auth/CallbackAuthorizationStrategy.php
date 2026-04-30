<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Auth;

use Andy87\ClientsBase\Contracts\AuthorizationStrategyInterface;
use Andy87\ClientsBase\Contracts\HttpTransportInterface;

/**
 * Получает заголовки авторизации из пользовательского callback.
 */
class CallbackAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * Создаёт callback-стратегию авторизации.
     *
     * @param callable $callback Callback, возвращающий array<string,string>.
     *
     * @return void
     */
    public function __construct(
        private mixed $callback,
    ) {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Authorization callback must be callable.');
        }
    }

    /**
     * Возвращает заголовки авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для служебных запросов авторизации.
     *
     * @return array<string, string>
     *
     * @throws \UnexpectedValueException Если callback вернул не массив.
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        $headers = ($this->callback)($transport);

        if (!is_array($headers)) {
            throw new \UnexpectedValueException('Authorization callback must return an array.');
        }

        /** @var array<string, string> $headers */
        return $headers;
    }
}
