<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Provider;

use Andy87\ClientsBase\Contracts\AuthorizationStrategyInterface;
use Andy87\ClientsBase\Contracts\HttpTransportInterface;
use Andy87\ClientsBase\Contracts\PromptInterface;
use Andy87\ClientsBase\Contracts\ResponseInterface;
use Andy87\ClientsBase\Dto\ApiError;
use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Базовый provider для вызова API-методов через Prompt и Response DTO.
 */
abstract class AbstractProvider
{
    /**
     * Создаёт provider.
     *
     * @param string $baseUrl Базовый URL API.
     * @param AuthorizationStrategyInterface $authorizationStrategy Стратегия авторизации.
     * @param HttpTransportInterface $transport HTTP-транспорт.
     * @param int $timeout Таймаут запросов.
     *
     * @return void
     */
    public function __construct(
        protected string $baseUrl,
        protected AuthorizationStrategyInterface $authorizationStrategy,
        protected HttpTransportInterface $transport,
        protected int $timeout = 30,
    ) {
    }

    /**
     * Отправляет запрос и возвращает DTO ответа.
     *
     * @template T of ResponseInterface
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param class-string<T> $responseClass Класс DTO ответа.
     *
     * @return T DTO ответа.
     *
     * @throws \InvalidArgumentException Если Prompt невалиден.
     * @throws \RuntimeException Если HTTP-транспорт завершился ошибкой.
     */
    protected function request(PromptInterface $prompt, string $responseClass): ResponseInterface
    {
        $prompt->validate();

        $headers = ['Accept' => 'application/json'];

        if ($prompt->requiresAuthorization()) {
            $headers = array_merge($headers, $this->authorizationStrategy->getAuthorizationHeaders($this->transport));
        }

        $httpResponse = $this->transport->send(new HttpRequest(
            method: $prompt->getMethod(),
            url: $this->buildUrl($prompt),
            headers: $headers,
            query: $prompt->getQueryParameters(),
            body: $prompt->getBody(),
            contentType: $prompt->getContentType(),
            timeout: $this->timeout,
        ));

        $data = $httpResponse->json();
        $error = $httpResponse->statusCode >= 400 ? new ApiError($data, $httpResponse->statusCode) : null;

        return new $responseClass($data, $error, $httpResponse->statusCode, $httpResponse->headers);
    }

    /**
     * Собирает полный URL запроса.
     *
     * @param PromptInterface $prompt DTO запроса.
     *
     * @return string Полный URL.
     */
    private function buildUrl(PromptInterface $prompt): string
    {
        $endpoint = $prompt->getEndpoint();

        foreach ($prompt->getPathParameters() as $name => $value) {
            $endpoint = str_replace('{' . $name . '}', rawurlencode((string) $value), $endpoint);
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }
}
