<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Request;

use Andy87\ClientsBase\Contracts\BodyEncoderInterface;
use Andy87\ClientsBase\Contracts\PromptInterface;
use Andy87\ClientsBase\Contracts\QueryEncoderInterface;
use Andy87\ClientsBase\Contracts\RequestFactoryInterface;
use Andy87\ClientsBase\Encoder\DefaultBodyEncoder;
use Andy87\ClientsBase\Encoder\DefaultQueryEncoder;
use Andy87\ClientsBase\Exception\ValidationException;
use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Создаёт HTTP-запрос из Prompt DTO.
 */
class DefaultRequestFactory implements RequestFactoryInterface
{
    /**
     * Создаёт фабрику HTTP-запросов.
     *
     * @param QueryEncoderInterface $queryEncoder Кодировщик query-параметров.
     * @param BodyEncoderInterface $bodyEncoder Кодировщик тела запроса.
     *
     * @return void
     */
    public function __construct(
        private QueryEncoderInterface $queryEncoder = new DefaultQueryEncoder(),
        private BodyEncoderInterface $bodyEncoder = new DefaultBodyEncoder(),
    ) {
    }

    /**
     * Собирает HTTP-запрос для транспорта.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param string $baseUrl Базовый URL API.
     * @param array<string, string> $headers Заголовки запроса.
     * @param int $timeout Таймаут запроса в секундах.
     * @param array<string, mixed> $extraQuery Дополнительные query-параметры.
     *
     * @return HttpRequest HTTP-запрос.
     *
     * @throws ValidationException Если endpoint содержит незаполненные path-плейсхолдеры.
     * @throws \JsonException Если body нельзя закодировать в JSON.
     */
    public function create(
        PromptInterface $prompt,
        string $baseUrl,
        array $headers,
        int $timeout,
        array $extraQuery = [],
    ): HttpRequest {
        $endpoint = $this->buildEndpoint($prompt);
        $query = array_merge($prompt->getQueryParameters(), $extraQuery);
        $queryString = $this->queryEncoder->encode($query);
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if ($queryString !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
        }

        $encodedBody = $this->bodyEncoder->encode($prompt->getBody(), $prompt->getContentType());
        $headers = array_merge($headers, $encodedBody->headers);

        if ($encodedBody->contentType !== null && !$this->hasHeader($headers, 'Content-Type')) {
            $headers['Content-Type'] = $encodedBody->contentType;
        }

        return new HttpRequest(
            method: $prompt->getMethod(),
            url: $url,
            headers: $headers,
            query: [],
            body: $prompt->getBody(),
            contentType: $encodedBody->contentType ?? $prompt->getContentType(),
            timeout: $timeout,
            rawBody: $encodedBody->content,
        );
    }

    /**
     * Собирает endpoint с path-параметрами.
     *
     * @param PromptInterface $prompt DTO запроса.
     *
     * @return string Endpoint.
     *
     * @throws ValidationException Если endpoint содержит незаполненные path-плейсхолдеры.
     */
    private function buildEndpoint(PromptInterface $prompt): string
    {
        $endpoint = $prompt->getEndpoint();

        foreach ($prompt->getPathParameters() as $name => $value) {
            $endpoint = str_replace('{' . $name . '}', rawurlencode((string) $value), $endpoint);
        }

        if (preg_match('/\{[^}]+\}/', $endpoint, $matches) === 1) {
            throw new ValidationException(sprintf('Endpoint path parameter "%s" is not filled.', $matches[0]));
        }

        return $endpoint;
    }

    /**
     * Проверяет наличие заголовка без учёта регистра.
     *
     * @param array<string, string> $headers Заголовки.
     * @param string $name Имя заголовка.
     *
     * @return bool true, если заголовок найден.
     */
    private function hasHeader(array $headers, string $name): bool
    {
        foreach ($headers as $headerName => $_) {
            if (strcasecmp($headerName, $name) === 0) {
                return true;
            }
        }

        return false;
    }
}
