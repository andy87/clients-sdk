<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Http;

/**
 * Описывает файл для multipart/form-data запроса.
 */
class MultipartFile
{
    /**
     * Создаёт описание файла multipart/form-data.
     *
     * @param string $path Путь к локальному файлу.
     * @param string|null $filename Имя файла в HTTP-запросе.
     * @param string $contentType MIME-тип файла.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если путь к файлу пустой.
     */
    public function __construct(
        public string $path,
        public ?string $filename = null,
        public string $contentType = 'application/octet-stream',
    ) {
        if ($path === '') {
            throw new \InvalidArgumentException('Multipart file path must be a non-empty string.');
        }
    }
}
