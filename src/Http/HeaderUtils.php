<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Http;

/**
 * Содержит общие операции с HTTP-заголовками без учёта регистра имени.
 */
final class HeaderUtils
{
    /**
     * Запрещает создание utility-класса.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Проверяет наличие заголовка без учёта регистра.
     *
     * @param array<string, mixed> $headers Заголовки.
     * @param string $name Имя заголовка.
     *
     * @return bool true, если заголовок найден.
     */
    public static function has(array $headers, string $name): bool
    {
        $needle = strtolower(self::normalizeName($name));

        foreach ($headers as $headerName => $_) {
            if (strtolower(self::normalizeName($headerName)) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Объединяет заголовки с перезаписью по имени без учёта регистра.
     *
     * @param array<string, mixed> $base Базовые заголовки.
     * @param array<string, mixed> $headers Добавляемые заголовки.
     *
     * @return array<string, string> Объединённые заголовки.
     *
     * @throws \InvalidArgumentException Если заголовки описаны некорректно.
     */
    public static function merge(array $base, array $headers): array
    {
        $result = [];
        $index = [];

        foreach ($base as $name => $value) {
            $normalizedName = self::normalizeName($name);
            $result[$normalizedName] = self::normalizeValue($value);
            $index[strtolower($normalizedName)] = $normalizedName;
        }

        foreach ($headers as $name => $value) {
            $normalizedName = self::normalizeName($name);
            $lowerName = strtolower($normalizedName);

            if (isset($index[$lowerName])) {
                unset($result[$index[$lowerName]]);
            }

            $result[$normalizedName] = self::normalizeValue($value);
            $index[$lowerName] = $normalizedName;
        }

        return $result;
    }

    /**
     * Нормализует имя заголовка и запрещает CRLF injection.
     *
     * @param mixed $name Имя заголовка.
     *
     * @return string Нормализованное имя.
     *
     * @throws \InvalidArgumentException Если имя заголовка некорректно.
     */
    public static function normalizeName(mixed $name): string
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Header name must be a string.');
        }

        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Header name must be a non-empty string.');
        }

        if (str_contains($name, "\r") || str_contains($name, "\n")) {
            throw new \InvalidArgumentException('Header name must not contain CR or LF.');
        }

        return $name;
    }

    /**
     * Нормализует значение заголовка и запрещает CRLF injection.
     *
     * @param mixed $value Значение заголовка.
     *
     * @return string Нормализованное значение.
     *
     * @throws \InvalidArgumentException Если значение заголовка некорректно.
     */
    public static function normalizeValue(mixed $value): string
    {
        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('Header value must be scalar or Stringable.');
        }

        if (str_contains($value, "\r") || str_contains($value, "\n")) {
            throw new \InvalidArgumentException('Header value must not contain CR or LF.');
        }

        return $value;
    }
}
