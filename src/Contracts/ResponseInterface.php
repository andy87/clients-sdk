<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

use Andy87\ClientsBase\Dto\ApiError;

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
     * Возвращает исходные данные ответа.
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function toArray(): array;
}
