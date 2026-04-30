<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Request;

use Andy87\ClientsBase\Contracts\QueryEncoderInterface;
use Andy87\ClientsBase\Contracts\RequestFinalizerInterface;
use Andy87\ClientsBase\Encoder\DefaultQueryEncoder;
use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Финализирует запрос стандартным query encoder-ом после пользовательских изменений.
 */
class DefaultRequestFinalizer implements RequestFinalizerInterface
{
    /**
     * Создаёт финализатор HTTP-запроса.
     *
     * @param QueryEncoderInterface $queryEncoder Кодировщик query-параметров.
     *
     * @return void
     */
    public function __construct(
        private QueryEncoderInterface $queryEncoder = new DefaultQueryEncoder(),
    ) {
    }

    /**
     * Пересчитывает encoded query-string по текущему состоянию HttpRequest->query.
     *
     * @param HttpRequest $request HTTP-запрос после пользовательских изменений.
     *
     * @return HttpRequest Тот же HTTP-запрос с актуальной metadata.
     */
    public function finalize(HttpRequest $request): HttpRequest
    {
        $request->metadata['queryString'] = $this->queryEncoder->encode($request->query);

        return $request;
    }
}
