<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Decoder;

use Andy87\ClientsBase\Contracts\ResponseDecoderInterface;
use Andy87\ClientsBase\Exception\ResponseDecodeException;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Декодирует JSON-ответы API.
 */
class JsonResponseDecoder implements ResponseDecoderInterface
{
    /**
     * Декодирует JSON-тело ответа.
     *
     * @param HttpResponse $response Raw HTTP-ответ.
     *
     * @return array<string, mixed>|list<mixed> Декодированное тело ответа.
     *
     * @throws ResponseDecodeException Если успешный ответ не является JSON-объектом или массивом.
     */
    public function decode(HttpResponse $response): array
    {
        if ($response->body === '') {
            return [];
        }

        try {
            $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            if ($response->statusCode >= 400) {
                return [];
            }

            throw new ResponseDecodeException('API returned invalid JSON response.', 0, $exception);
        }

        if (!is_array($data)) {
            if ($response->statusCode >= 400) {
                return [];
            }

            throw new ResponseDecodeException('API returned non-object JSON response.');
        }

        return $data;
    }
}
