<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

use Andy87\ClientsBase\Dto\ApiError;
use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Описывает DTO ответа API.
 */
interface ResponseInterface
{
    /**
     * Проверяет, содержит ли ответ ошибку.
     *
     * @return bool true, если ответ содержит ошибку.
     */
    public function hasError(): bool;

    /**
     * Возвращает данные ошибки.
     *
     * @return ApiError|null Данные ошибки или null.
     */
    public function getError(): ?ApiError;

    /**
     * Возвращает HTTP-статус ответа.
     *
     * @return int HTTP-статус.
     */
    public function getStatusCode(): int;

    /**
     * Возвращает заголовки ответа.
     *
     * @return array<string, string> Заголовки ответа.
     */
    public function getHeaders(): array;

    /**
     * Возвращает raw тело HTTP-ответа.
     *
     * @return string Raw тело ответа.
     */
    public function getRawBody(): string;

    /**
     * Возвращает декодированное тело ответа.
     *
     * @return array<string, mixed>|list<mixed> Декодированное тело ответа.
     */
    public function getDecodedBody(): array;

    /**
     * Возвращает HTTP-запрос, если он доступен.
     *
     * @return HttpRequest|null HTTP-запрос или null.
     */
    public function getRequest(): ?HttpRequest;

    /**
     * Возвращает исходные данные ответа.
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function toArray(): array;
}
