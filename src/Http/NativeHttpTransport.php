<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Http;

use Andy87\ClientsBase\Contracts\HttpTransportInterface;

/**
 * Выполняет HTTP-запросы средствами PHP stream wrapper.
 */
class NativeHttpTransport implements HttpTransportInterface
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
    public function send(HttpRequest $request): HttpResponse
    {
        $url = $this->buildUrl($request->url, $request->query);
        $headers = $request->headers;
        $body = null;

        if ($request->body !== null) {
            if ($request->contentType === 'application/x-www-form-urlencoded') {
                $body = http_build_query($request->body);
            } else {
                $body = json_encode($request->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $headers['Content-Type'] = $request->contentType ?? 'application/json';
            }
        }

        if ($request->contentType !== null && !isset($headers['Content-Type'])) {
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
            throw new \RuntimeException($error['message'] ?? 'HTTP request failed.');
        }

        [$statusCode, $responseHeaders] = $this->parseResponseHeaders($http_response_header ?? []);

        return new HttpResponse($statusCode, $responseHeaders, $responseBody);
    }

    /**
     * Собирает URL с query-параметрами.
     *
     * @param string $url Базовый URL.
     * @param array<string, mixed> $query Query-параметры.
     *
     * @return string URL.
     */
    private function buildUrl(string $url, array $query): string
    {
        $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== []);

        if ($query === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($query);
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
            $lines[] = $name . ': ' . $value;
        }

        return implode("\r\n", $lines);
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
