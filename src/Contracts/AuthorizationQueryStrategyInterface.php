<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

/**
 * Описывает стратегию авторизации, добавляющую параметры в query-string.
 */
interface AuthorizationQueryStrategyInterface
{
    /**
     * Возвращает query-параметры авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для служебных запросов авторизации.
     *
     * @return array<string, mixed> Query-параметры авторизации.
     *
     * @throws \RuntimeException Если авторизация не выполнена.
     */
    public function getAuthorizationQueryParameters(HttpTransportInterface $transport): array;
}
