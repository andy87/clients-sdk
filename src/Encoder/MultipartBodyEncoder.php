<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Encoder;

use Andy87\ClientsBase\Contracts\BodyEncoderInterface;
use Andy87\ClientsBase\Http\HttpBody;
use Andy87\ClientsBase\Http\MultipartFile;

/**
 * Кодирует тело HTTP-запроса в multipart/form-data.
 */
class MultipartBodyEncoder implements BodyEncoderInterface
{
    /**
     * Кодирует тело запроса в multipart/form-data.
     *
     * @param array<string, mixed>|list<mixed>|string|null $body Тело запроса.
     * @param string|null $contentType Желаемый Content-Type.
     *
     * @return HttpBody Закодированное multipart-тело.
     *
     * @throws \InvalidArgumentException Если тело или файл некорректны.
     */
    public function encode(array|string|null $body, ?string $contentType): HttpBody
    {
        if ($body === null) {
            return new HttpBody();
        }

        if (!is_array($body)) {
            throw new \InvalidArgumentException('Multipart body must be an array.');
        }

        $boundary = '----clients-sdk-' . bin2hex(random_bytes(12));
        $content = '';

        foreach ($body as $name => $value) {
            $content .= $this->encodePart((string) $name, $value, $boundary);
        }

        $content .= '--' . $boundary . "--\r\n";

        return new HttpBody($content, $contentType ?? 'multipart/form-data; boundary=' . $boundary);
    }

    /**
     * Кодирует одну часть multipart/form-data.
     *
     * @param string $name Имя поля.
     * @param mixed $value Значение поля.
     * @param string $boundary Boundary multipart-запроса.
     *
     * @return string Закодированная часть.
     *
     * @throws \InvalidArgumentException Если файл не читается.
     */
    private function encodePart(string $name, mixed $value, string $boundary): string
    {
        if ($value instanceof MultipartFile) {
            if (!is_readable($value->path)) {
                throw new \InvalidArgumentException(sprintf('Multipart file "%s" is not readable.', $value->path));
            }

            $filename = $value->filename ?? basename($value->path);

            return '--' . $boundary . "\r\n"
                . sprintf('Content-Disposition: form-data; name="%s"; filename="%s"', $this->escape($name), $this->escape($filename)) . "\r\n"
                . 'Content-Type: ' . $value->contentType . "\r\n\r\n"
                . file_get_contents($value->path) . "\r\n";
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return '--' . $boundary . "\r\n"
            . sprintf('Content-Disposition: form-data; name="%s"', $this->escape($name)) . "\r\n\r\n"
            . (string) $value . "\r\n";
    }

    /**
     * Экранирует значение multipart-заголовка.
     *
     * @param string $value Значение.
     *
     * @return string Экранированное значение.
     */
    private function escape(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
