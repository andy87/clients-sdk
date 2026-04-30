<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Encoder;

use Andy87\ClientsBase\Contracts\BodyEncoderInterface;
use Andy87\ClientsBase\Http\HttpBody;

/**
 * Выбирает кодировщик тела запроса по Content-Type.
 */
class DefaultBodyEncoder implements BodyEncoderInterface
{
    /**
     * Создаёт кодировщик тела запроса по умолчанию.
     *
     * @param BodyEncoderInterface $jsonEncoder JSON-кодировщик.
     * @param BodyEncoderInterface $formEncoder Form-urlencoded кодировщик.
     * @param BodyEncoderInterface $multipartEncoder Multipart-кодировщик.
     *
     * @return void
     */
    public function __construct(
        private BodyEncoderInterface $jsonEncoder = new JsonBodyEncoder(),
        private BodyEncoderInterface $formEncoder = new FormBodyEncoder(),
        private BodyEncoderInterface $multipartEncoder = new MultipartBodyEncoder(),
    ) {
    }

    /**
     * Кодирует тело запроса по Content-Type.
     *
     * @param array<string, mixed>|list<mixed>|string|null $body Тело запроса.
     * @param string|null $contentType Желаемый Content-Type.
     *
     * @return HttpBody Закодированное тело.
     *
     * @throws \JsonException Если JSON-кодирование завершилось ошибкой.
     * @throws \InvalidArgumentException Если тело нельзя закодировать.
     */
    public function encode(array|string|null $body, ?string $contentType): HttpBody
    {
        if ($contentType !== null && str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            return $this->formEncoder->encode($body, $contentType);
        }

        if ($contentType !== null && str_starts_with($contentType, 'multipart/form-data')) {
            return $this->multipartEncoder->encode($body, $contentType);
        }

        return $this->jsonEncoder->encode($body, $contentType);
    }
}
