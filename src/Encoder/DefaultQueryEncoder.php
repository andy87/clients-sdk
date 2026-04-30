<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Encoder;

use Andy87\ClientsBase\Contracts\QueryEncoderInterface;

/**
 * Кодирует query-параметры стандартным PHP-форматом RFC 3986.
 */
class DefaultQueryEncoder implements QueryEncoderInterface
{
    /**
     * Кодирует query-параметры.
     *
     * @param array<string, mixed> $query Query-параметры.
     *
     * @return string Query-string или пустая строка.
     */
    public function encode(array $query): string
    {
        $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== []);

        if ($query === []) {
            return '';
        }

        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
