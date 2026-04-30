<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Error;

use Andy87\ClientsBase\Contracts\ApiErrorFactoryInterface;
use Andy87\ClientsBase\Dto\ApiError;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Создаёт ApiError из распространённых форматов ошибок API.
 */
class DefaultApiErrorFactory implements ApiErrorFactoryInterface
{
    /**
     * Создаёт DTO ошибки API.
     *
     * @param HttpResponse $response Raw HTTP-ответ.
     * @param array<string, mixed>|list<mixed> $decodedBody Декодированное тело ответа.
     *
     * @return ApiError DTO ошибки API.
     */
    public function create(HttpResponse $response, array $decodedBody): ApiError
    {
        return new ApiError(
            raw: $decodedBody,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->body,
        );
    }
}
