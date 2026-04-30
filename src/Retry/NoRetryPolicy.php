<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Retry;

use Andy87\ClientsBase\Contracts\RetryPolicyInterface;
use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Отключает повторные HTTP-запросы.
 */
class NoRetryPolicy implements RetryPolicyInterface
{
    /**
     * Проверяет, нужно ли повторить запрос.
     *
     * @param int $attempt Номер попытки.
     * @param HttpRequest $request HTTP-запрос.
     * @param HttpResponse|null $response HTTP-ответ.
     * @param \Throwable|null $exception Исключение транспорта.
     *
     * @return bool Всегда false.
     */
    public function shouldRetry(int $attempt, HttpRequest $request, ?HttpResponse $response = null, ?\Throwable $exception = null): bool
    {
        return false;
    }

    /**
     * Возвращает задержку перед следующей попыткой.
     *
     * @param int $attempt Номер попытки.
     * @param HttpRequest $request HTTP-запрос.
     * @param HttpResponse|null $response HTTP-ответ.
     * @param \Throwable|null $exception Исключение транспорта.
     *
     * @return int Всегда 0.
     */
    public function getDelayMs(int $attempt, HttpRequest $request, ?HttpResponse $response = null, ?\Throwable $exception = null): int
    {
        return 0;
    }
}
