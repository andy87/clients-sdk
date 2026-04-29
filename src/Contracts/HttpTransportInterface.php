<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Описывает транспортный слой HTTP-запросов.
 */
interface HttpTransportInterface
{
    /**
     * Отправляет HTTP-запрос.
     *
     * @param HttpRequest $request Запрос.
     *
     * @return HttpResponse Ответ.
     *
     * @throws \RuntimeException Если транспорт не смог выполнить запрос.
     */
    public function send(HttpRequest $request): HttpResponse;
}
