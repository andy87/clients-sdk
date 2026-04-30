<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Encoder;

use Andy87\ClientsBase\Contracts\BodyEncoderInterface;
use Andy87\ClientsBase\Http\HttpBody;

/**
 * Кодирует тело HTTP-запроса в JSON.
 */
class JsonBodyEncoder implements BodyEncoderInterface
{
    /**
     * Кодирует тело запроса в JSON.
     *
     * @param array<string, mixed>|list<mixed>|string|null $body Тело запроса.
     * @param string|null $contentType Желаемый Content-Type.
     *
     * @return HttpBody Закодированное JSON-тело.
     *
     * @throws \JsonException Если JSON-кодирование завершилось ошибкой.
     */
    public function encode(array|string|null $body, ?string $contentType): HttpBody
    {
        if ($body === null) {
            return new HttpBody();
        }

        if (is_string($body)) {
            return new HttpBody($body, $contentType ?? 'application/json');
        }

        return new HttpBody(
            json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            $contentType ?? 'application/json',
        );
    }
}
