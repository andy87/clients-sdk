<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Финализирует mutable HTTP-запрос после пользовательских событий и перед отправкой транспортом.
 */
interface RequestFinalizerInterface
{
    /**
     * Подготавливает производные данные запроса для транспорта.
     *
     * @param HttpRequest $request HTTP-запрос после пользовательских изменений.
     *
     * @return HttpRequest Финализированный HTTP-запрос.
     */
    public function finalize(HttpRequest $request): HttpRequest;
}
