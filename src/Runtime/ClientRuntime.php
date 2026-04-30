<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Runtime;

/**
 * Хранит runtime-настройки API-клиента: обработчики событий и дефолтные заголовки.
 */
class ClientRuntime
{
    /** @var array<string, list<callable>> Обработчики событий по имени события. */
    private array $listeners = [];

    /** @var array<string, string> Дефолтные пользовательские заголовки. */
    private array $headers = [];

    /**
     * Создаёт runtime-контекст API-клиента.
     *
     * @param array<string, string> $headers Дефолтные пользовательские заголовки.
     * @param array<string, callable|list<callable>> $events Обработчики событий.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если заголовки или обработчики описаны некорректно.
     */
    public function __construct(array $headers = [], array $events = [])
    {
        $this->setHeaders($headers);
        $this->addEventListeners($events);
    }

    /**
     * Добавляет обработчик события.
     *
     * @param string $eventName Имя события.
     * @param callable $listener Обработчик события.
     *
     * @return static Текущий runtime-контекст.
     *
     * @throws \InvalidArgumentException Если имя события некорректно.
     */
    public function on(string $eventName, callable $listener): static
    {
        $eventName = $this->normalizeEventName($eventName);
        $this->listeners[$eventName] ??= [];
        $this->listeners[$eventName][] = $listener;

        return $this;
    }

    /**
     * Вызывает обработчики события.
     *
     * @param string $eventName Имя события.
     * @param object $event Объект события.
     *
     * @return void
     *
     * @throws \Throwable Если обработчик события выбросил исключение.
     */
    public function dispatch(string $eventName, object $event): void
    {
        foreach ($this->listeners[$eventName] ?? [] as $listener) {
            $listener($event);
        }
    }

    /**
     * Полностью заменяет дефолтные пользовательские заголовки.
     *
     * @param array<string, string> $headers Заголовки.
     *
     * @return static Текущий runtime-контекст.
     *
     * @throws \InvalidArgumentException Если заголовки описаны некорректно.
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $this->mergeHeaders([], $headers);

        return $this;
    }

    /**
     * Добавляет или перезаписывает дефолтные пользовательские заголовки.
     *
     * @param array<string, string> $headers Заголовки.
     *
     * @return static Текущий runtime-контекст.
     *
     * @throws \InvalidArgumentException Если заголовки описаны некорректно.
     */
    public function addHeaders(array $headers): static
    {
        $this->headers = $this->mergeHeaders($this->headers, $headers);

        return $this;
    }

    /**
     * Возвращает дефолтные пользовательские заголовки.
     *
     * @return array<string, string> Заголовки.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Объединяет заголовки с перезаписью по имени без учёта регистра.
     *
     * @param array<string, string> $base Базовые заголовки.
     * @param array<string, string> $headers Добавляемые заголовки.
     *
     * @return array<string, string> Объединённые заголовки.
     *
     * @throws \InvalidArgumentException Если заголовки описаны некорректно.
     */
    public function mergeHeaders(array $base, array $headers): array
    {
        $result = [];
        $index = [];

        foreach ($base as $name => $value) {
            $normalizedName = $this->normalizeHeaderName($name);
            $result[$normalizedName] = $this->normalizeHeaderValue($value);
            $index[strtolower($normalizedName)] = $normalizedName;
        }

        foreach ($headers as $name => $value) {
            $normalizedName = $this->normalizeHeaderName($name);
            $lowerName = strtolower($normalizedName);

            if (isset($index[$lowerName])) {
                unset($result[$index[$lowerName]]);
            }

            $result[$normalizedName] = $this->normalizeHeaderValue($value);
            $index[$lowerName] = $normalizedName;
        }

        return $result;
    }

    /**
     * Добавляет набор обработчиков событий из массива опций.
     *
     * @param array<string, callable|list<callable>> $events Обработчики событий.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если обработчики описаны некорректно.
     */
    private function addEventListeners(array $events): void
    {
        foreach ($events as $eventName => $listeners) {
            $eventName = $this->normalizeEventName($eventName);

            if (is_callable($listeners)) {
                $this->on($eventName, $listeners);
                continue;
            }

            if (!is_array($listeners)) {
                throw new \InvalidArgumentException(sprintf('Event "%s" listeners must be callable or list of callables.', $eventName));
            }

            foreach ($listeners as $listener) {
                if (!is_callable($listener)) {
                    throw new \InvalidArgumentException(sprintf('Event "%s" listener must be callable.', $eventName));
                }

                $this->on($eventName, $listener);
            }
        }
    }

    /**
     * Нормализует имя события.
     *
     * @param string|int $eventName Имя события.
     *
     * @return string Нормализованное имя события.
     *
     * @throws \InvalidArgumentException Если имя события некорректно.
     */
    private function normalizeEventName(string|int $eventName): string
    {
        if (!is_string($eventName)) {
            throw new \InvalidArgumentException('Event name must be a string.');
        }

        $eventName = trim($eventName);

        if ($eventName === '') {
            throw new \InvalidArgumentException('Event name must be a non-empty string.');
        }

        return $eventName;
    }

    /**
     * Нормализует имя заголовка.
     *
     * @param string|int $name Имя заголовка.
     *
     * @return string Нормализованное имя заголовка.
     *
     * @throws \InvalidArgumentException Если имя заголовка некорректно.
     */
    private function normalizeHeaderName(string|int $name): string
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Header name must be a string.');
        }

        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Header name must be a non-empty string.');
        }

        return $name;
    }

    /**
     * Нормализует значение заголовка.
     *
     * @param mixed $value Значение заголовка.
     *
     * @return string Нормализованное значение заголовка.
     *
     * @throws \InvalidArgumentException Если значение заголовка некорректно.
     */
    private function normalizeHeaderValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \InvalidArgumentException('Header value must be scalar or Stringable.');
    }
}
