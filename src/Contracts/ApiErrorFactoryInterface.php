<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

use Andy87\ClientsBase\Dto\ApiError;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Создаёт нормализованную ошибку API из HTTP-ответа.
 */
interface ApiErrorFactoryInterface
{
    /**
     * Создаёт DTO ошибки API.
     *
     * @param HttpResponse $response Raw HTTP-ответ.
     * @param array<string, mixed>|list<mixed> $decodedBody Декодированное тело ответа.
     *
     * @return ApiError DTO ошибки API.
     */
    public function create(HttpResponse $response, array $decodedBody): ApiError;
}
