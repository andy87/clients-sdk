<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

/**
 * Кодирует query-параметры HTTP-запроса.
 */
interface QueryEncoderInterface
{
    /**
     * Кодирует query-параметры в строку без ведущего знака вопроса.
     *
     * @param array<string, mixed> $query Query-параметры.
     *
     * @return string Query-string или пустая строка.
     */
    public function encode(array $query): string;
}
