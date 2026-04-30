<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests\Support;

use Andy87\ClientsBase\Contracts\HttpTransportInterface;
use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Тестовый HTTP-транспорт с очередью заранее заданных ответов.
 */
class FakeTransport implements HttpTransportInterface
{
    /** @var list<HttpRequest> Отправленные запросы. */
    public array $requests = [];

    /** @var list<HttpResponse|\Throwable> Очередь ответов или исключений. */
    private array $queue;

    /**
     * Создаёт тестовый транспорт.
     *
     * @param list<HttpResponse|\Throwable> $queue Очередь ответов или исключений.
     *
     * @return void
     */
    public function __construct(array $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Отправляет HTTP-запрос и возвращает следующий ответ из очереди.
     *
     * @param HttpRequest $request Запрос.
     *
     * @return HttpResponse Ответ.
     *
     * @throws \Throwable Если в очереди находится исключение.
     */
    public function send(HttpRequest $request): HttpResponse
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue);

        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next ?? new HttpResponse(200, [], '{}');
    }
}
