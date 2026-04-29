<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

/**
 * Описывает DTO запроса к API.
 */
interface PromptInterface
{
    /**
     * Возвращает HTTP-метод запроса.
     *
     * @return string HTTP-метод.
     */
    public function getMethod(): string;

    /**
     * Возвращает endpoint запроса.
     *
     * @return string Endpoint с возможными path-плейсхолдерами.
     */
    public function getEndpoint(): string;

    /**
     * Возвращает Content-Type тела запроса.
     *
     * @return string|null Content-Type или null, если тело отсутствует.
     */
    public function getContentType(): ?string;

    /**
     * Проверяет, нужна ли авторизация для запроса.
     *
     * @return bool true, если запрос требует авторизацию.
     */
    public function requiresAuthorization(): bool;

    /**
     * Возвращает path-параметры запроса.
     *
     * @return array<string, mixed>
     */
    public function getPathParameters(): array;

    /**
     * Возвращает query-параметры запроса.
     *
     * @return array<string, mixed>
     */
    public function getQueryParameters(): array;

    /**
     * Возвращает тело запроса.
     *
     * @return array<string, mixed>|list<mixed>|null
     */
    public function getBody(): array|null;

    /**
     * Проверяет обязательные поля запроса.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если обязательное поле не заполнено.
     */
    public function validate(): void;
}
