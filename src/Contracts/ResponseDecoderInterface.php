<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Декодирует raw HTTP-ответ в данные для Response DTO.
 */
interface ResponseDecoderInterface
{
    /**
     * Декодирует тело HTTP-ответа.
     *
     * @param HttpResponse $response Raw HTTP-ответ.
     *
     * @return array<string, mixed>|list<mixed> Декодированное тело ответа.
     *
     * @throws \RuntimeException Если успешный ответ нельзя декодировать.
     */
    public function decode(HttpResponse $response): array;
}
