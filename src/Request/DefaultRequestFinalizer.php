<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Request;

use Andy87\ClientsBase\Contracts\BodyEncoderInterface;
use Andy87\ClientsBase\Contracts\QueryEncoderInterface;
use Andy87\ClientsBase\Contracts\RequestFinalizerInterface;
use Andy87\ClientsBase\Encoder\DefaultBodyEncoder;
use Andy87\ClientsBase\Encoder\DefaultQueryEncoder;
use Andy87\ClientsBase\Http\HeaderUtils;
use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Финализирует производные данные HTTP-запроса после пользовательских изменений.
 */
class DefaultRequestFinalizer implements RequestFinalizerInterface
{
    /**
     * Создаёт финализатор HTTP-запроса.
     *
     * @param QueryEncoderInterface $queryEncoder Кодировщик query-параметров.
     * @param BodyEncoderInterface $bodyEncoder Кодировщик тела запроса.
     *
     * @return void
     */
    public function __construct(
        private QueryEncoderInterface $queryEncoder = new DefaultQueryEncoder(),
        private BodyEncoderInterface $bodyEncoder = new DefaultBodyEncoder(),
    ) {
    }

    /**
     * Пересчитывает encoded query-string, raw body и заголовки по текущему состоянию запроса.
     *
     * @param HttpRequest $request HTTP-запрос после пользовательских изменений.
     *
     * @return HttpRequest Тот же HTTP-запрос с актуальной metadata.
     *
     * @throws \JsonException Если тело запроса нельзя закодировать в JSON.
     * @throws \InvalidArgumentException Если тело или заголовки некорректны.
     */
    public function finalize(HttpRequest $request): HttpRequest
    {
        $request->metadata['queryString'] = $this->queryEncoder->encode($request->query);
        $request->headers = HeaderUtils::merge([], $request->headers);

        if ($request->body !== null) {
            $encodedBody = $this->bodyEncoder->encode($request->body, $request->contentType);

            $request->rawBody = $encodedBody->content;
            $request->contentType = $encodedBody->contentType ?? $request->contentType;
            $request->headers = HeaderUtils::merge($request->headers, $encodedBody->headers);

            if ($encodedBody->contentType !== null) {
                $request->headers = HeaderUtils::merge($request->headers, ['Content-Type' => $encodedBody->contentType]);
            }
        } elseif ($request->contentType !== null && !HeaderUtils::has($request->headers, 'Content-Type')) {
            $request->headers['Content-Type'] = $request->contentType;
        }

        return $request;
    }
}
