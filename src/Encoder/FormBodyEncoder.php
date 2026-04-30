<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Encoder;

use Andy87\ClientsBase\Contracts\BodyEncoderInterface;
use Andy87\ClientsBase\Http\HttpBody;

/**
 * Кодирует тело HTTP-запроса в application/x-www-form-urlencoded.
 */
class FormBodyEncoder implements BodyEncoderInterface
{
    /**
     * Кодирует тело запроса в form-urlencoded строку.
     *
     * @param array<string, mixed>|list<mixed>|string|null $body Тело запроса.
     * @param string|null $contentType Желаемый Content-Type.
     *
     * @return HttpBody Закодированное тело.
     *
     * @throws \InvalidArgumentException Если тело не является массивом или строкой.
     */
    public function encode(array|string|null $body, ?string $contentType): HttpBody
    {
        if ($body === null) {
            return new HttpBody();
        }

        if (is_string($body)) {
            return new HttpBody($body, $contentType ?? 'application/x-www-form-urlencoded');
        }

        return new HttpBody(http_build_query($body), $contentType ?? 'application/x-www-form-urlencoded');
    }
}
