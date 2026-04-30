<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Http;

use Andy87\ClientsBase\Contracts\HttpTransportInterface;
use Andy87\ClientsBase\Contracts\QueryEncoderInterface;
use Andy87\ClientsBase\Encoder\DefaultQueryEncoder;
use Andy87\ClientsBase\Exception\TransportException;

/**
 * Выполняет HTTP-запросы средствами PHP stream wrapper.
 */
class NativeHttpTransport implements HttpTransportInterface
{
    /**
     * Создаёт native HTTP-транспорт.
     *
     * @param QueryEncoderInterface $queryEncoder Кодировщик query-параметров.
     *
     * @return void
     */
    public function __construct(
        private QueryEncoderInterface $queryEncoder = new DefaultQueryEncoder(),
    ) {
    }

    /**
     * Отправляет HTTP-запрос.
     *
     * @param HttpRequest $request Запрос.
     *
     * @return HttpResponse Ответ.
     *
     * @throws \RuntimeException Если транспорт не смог выполнить запрос.
     */
    public function send(HttpRequest $request): HttpResponse
    {
        $url = $this->buildUrl(
            $request->url,
            $request->query,
            is_string($request->metadata['queryString'] ?? null) ? $request->metadata['queryString'] : null,
        );
        $headers = $request->headers;
        $body = null;

        if ($request->rawBody !== null) {
            $body = $request->rawBody;
        } elseif ($request->body !== null) {
            if ($this->mediaType($request->contentType) === 'application/x-www-form-urlencoded') {
                $body = http_build_query($request->body);
            } else {
                $body = json_encode($request->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                if (!HeaderUtils::has($headers, 'Content-Type')) {
                    $headers['Content-Type'] = $request->contentType ?? 'application/json';
                }
            }
        }

        if ($request->contentType !== null && !HeaderUtils::has($headers, 'Content-Type')) {
            $headers['Content-Type'] = $request->contentType;
        }

        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($request->method),
                'header' => $this->formatHeaders($headers),
                'content' => $body ?? '',
                'ignore_errors' => true,
                'timeout' => $request->timeout,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            $error = error_get_last();
            throw new TransportException($error['message'] ?? 'HTTP request failed.');
        }

        [$statusCode, $responseHeaders] = $this->parseResponseHeaders($http_response_header ?? []);

        return new HttpResponse($statusCode, $responseHeaders, $responseBody);
    }

    /**
     * Собирает URL с query-параметрами.
     *
     * @param string $url Базовый URL.
     * @param array<string, mixed> $query Query-параметры.
     * @param string|null $queryString Уже закодированная query-строка.
     *
     * @return string URL.
     */
    private function buildUrl(string $url, array $query, ?string $queryString = null): string
    {
        $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== []);

        if ($query === []) {
            return $url;
        }

        $queryString ??= $this->queryEncoder->encode($query);

        if ($queryString === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . $queryString;
    }

    /**
     * Форматирует заголовки для stream context.
     *
     * @param array<string, string> $headers Заголовки.
     *
     * @return string Заголовки в HTTP-формате.
     */
    private function formatHeaders(array $headers): string
    {
        $lines = [];

        foreach ($headers as $name => $value) {
            $lines[] = HeaderUtils::normalizeName($name) . ': ' . HeaderUtils::normalizeValue($value);
        }

        return implode("\r\n", $lines);
    }

    /**
     * Возвращает нормализованный media type без параметров Content-Type.
     *
     * @param string|null $contentType Content-Type.
     *
     * @return string|null Media type или null.
     */
    private function mediaType(?string $contentType): ?string
    {
        if ($contentType === null) {
            return null;
        }

        return strtolower(trim(explode(';', $contentType, 2)[0]));
    }

    /**
     * Парсит заголовки ответа.
     *
     * @param list<string> $headers Raw-заголовки.
     *
     * @return array{0:int,1:array<string,string>}
     */
    private function parseResponseHeaders(array $headers): array
    {
        $statusCode = 0;
        $parsed = [];

        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $statusCode = (int) $matches[1];
                continue;
            }

            if (str_contains($header, ':')) {
                [$name, $value] = explode(':', $header, 2);
                $parsed[trim($name)] = trim($value);
            }
        }

        return [$statusCode, $parsed];
    }
}
