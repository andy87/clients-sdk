<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Retry;

use Andy87\ClientsBase\Contracts\RetryPolicyInterface;
use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Повторяет безопасные HTTP-запросы по настраиваемым статусам и сетевым ошибкам.
 */
class DefaultRetryPolicy implements RetryPolicyInterface
{
    /**
     * Создаёт retry policy.
     *
     * @param int $maxAttempts Максимальное количество попыток.
     * @param list<int> $statusCodes HTTP-статусы для повтора.
     * @param list<string> $methods HTTP-методы для повтора.
     * @param int $baseDelayMs Базовая задержка в миллисекундах.
     * @param int $maxDelayMs Максимальная задержка в миллисекундах.
     * @param bool $respectRetryAfter Учитывать заголовок Retry-After.
     *
     * @return void
     */
    public function __construct(
        private int $maxAttempts = 3,
        private array $statusCodes = [429, 500, 502, 503, 504],
        private array $methods = ['GET', 'HEAD', 'OPTIONS'],
        private int $baseDelayMs = 250,
        private int $maxDelayMs = 5000,
        private bool $respectRetryAfter = true,
    ) {
    }

    /**
     * Проверяет, нужно ли повторить запрос.
     *
     * @param int $attempt Номер выполненной попытки.
     * @param HttpRequest $request HTTP-запрос.
     * @param HttpResponse|null $response HTTP-ответ.
     * @param \Throwable|null $exception Исключение транспорта.
     *
     * @return bool true, если запрос нужно повторить.
     */
    public function shouldRetry(int $attempt, HttpRequest $request, ?HttpResponse $response = null, ?\Throwable $exception = null): bool
    {
        if ($attempt >= $this->maxAttempts) {
            return false;
        }

        if (!in_array(strtoupper($request->method), $this->methods, true)) {
            return false;
        }

        if ($exception !== null) {
            return true;
        }

        return $response !== null && in_array($response->statusCode, $this->statusCodes, true);
    }

    /**
     * Возвращает задержку перед следующей попыткой.
     *
     * @param int $attempt Номер выполненной попытки.
     * @param HttpRequest $request HTTP-запрос.
     * @param HttpResponse|null $response HTTP-ответ.
     * @param \Throwable|null $exception Исключение транспорта.
     *
     * @return int Задержка в миллисекундах.
     */
    public function getDelayMs(int $attempt, HttpRequest $request, ?HttpResponse $response = null, ?\Throwable $exception = null): int
    {
        if ($this->respectRetryAfter && $response !== null) {
            $retryAfter = $this->getRetryAfter($response);

            if ($retryAfter !== null) {
                return min($retryAfter * 1000, $this->maxDelayMs);
            }
        }

        return min($this->baseDelayMs * (2 ** max(0, $attempt - 1)), $this->maxDelayMs);
    }

    /**
     * Возвращает задержку из Retry-After.
     *
     * @param HttpResponse $response HTTP-ответ.
     *
     * @return int|null Задержка в секундах или null.
     */
    private function getRetryAfter(HttpResponse $response): ?int
    {
        foreach ($response->headers as $name => $value) {
            if (strcasecmp($name, 'Retry-After') !== 0) {
                continue;
            }

            if (ctype_digit($value)) {
                return max(0, (int) $value);
            }

            $timestamp = strtotime($value);

            return $timestamp === false ? null : max(0, $timestamp - time());
        }

        return null;
    }
}
