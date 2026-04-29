<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

/**
 * Описывает подключаемый сценарий авторизации API-клиента.
 */
interface AuthorizationStrategyInterface
{
    /**
     * Возвращает HTTP-заголовки авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для служебных запросов авторизации.
     *
     * @return array<string, string>
     *
     * @throws \RuntimeException Если авторизация не выполнена.
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array;
}
