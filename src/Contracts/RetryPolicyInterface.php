<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Определяет, нужно ли повторять HTTP-запрос после ответа или исключения.
 */
interface RetryPolicyInterface
{
    /**
     * Проверяет, нужно ли повторить запрос.
     *
     * @param int $attempt Номер выполненной попытки, начиная с 1.
     * @param HttpRequest $request HTTP-запрос.
     * @param HttpResponse|null $response HTTP-ответ, если он получен.
     * @param \Throwable|null $exception Исключение транспорта, если оно возникло.
     *
     * @return bool true, если запрос нужно повторить.
     */
    public function shouldRetry(int $attempt, HttpRequest $request, ?HttpResponse $response = null, ?\Throwable $exception = null): bool;

    /**
     * Возвращает задержку перед следующей попыткой.
     *
     * @param int $attempt Номер выполненной попытки, начиная с 1.
     * @param HttpRequest $request HTTP-запрос.
     * @param HttpResponse|null $response HTTP-ответ, если он получен.
     * @param \Throwable|null $exception Исключение транспорта, если оно возникло.
     *
     * @return int Задержка в миллисекундах.
     */
    public function getDelayMs(int $attempt, HttpRequest $request, ?HttpResponse $response = null, ?\Throwable $exception = null): int;
}
