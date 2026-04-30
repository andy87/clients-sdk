<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Provider;

use Andy87\ClientsBase\Config\ClientOptions;
use Andy87\ClientsBase\Contracts\AuthorizationQueryStrategyInterface;
use Andy87\ClientsBase\Contracts\AuthorizationStrategyInterface;
use Andy87\ClientsBase\Contracts\HttpTransportInterface;
use Andy87\ClientsBase\Contracts\PromptInterface;
use Andy87\ClientsBase\Contracts\ResponseInterface;
use Andy87\ClientsBase\Event\AfterRequestEvent;
use Andy87\ClientsBase\Event\BeforeRequestEvent;
use Andy87\ClientsBase\Event\ClientEvents;
use Andy87\ClientsBase\Event\RequestExceptionEvent;
use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;
use Andy87\ClientsBase\Exception\ResponseHydrationException;
use Andy87\ClientsBase\Runtime\ClientRuntime;

/**
 * Базовый provider для вызова API-методов через Prompt и Response DTO.
 */
abstract class AbstractProvider
{
    /** @var ClientRuntime Runtime-контекст клиента. */
    protected ClientRuntime $runtime;

    /** @var ClientOptions Настройки выполнения запросов. */
    protected ClientOptions $options;

    /**
     * Создаёт provider.
     *
     * @param string $baseUrl Базовый URL API.
     * @param AuthorizationStrategyInterface $authorizationStrategy Стратегия авторизации.
     * @param HttpTransportInterface $transport HTTP-транспорт.
     * @param int $timeout Таймаут запросов.
     * @param ClientRuntime|null $runtime Runtime-контекст клиента.
     * @param ClientOptions|null $options Настройки выполнения запросов.
     *
     * @return void
     */
    public function __construct(
        protected string $baseUrl,
        protected AuthorizationStrategyInterface $authorizationStrategy,
        protected HttpTransportInterface $transport,
        protected int $timeout = 30,
        ?ClientRuntime $runtime = null,
        ?ClientOptions $options = null,
    ) {
        $this->options = $options ?? new ClientOptions(timeout: $timeout);
        $this->timeout = $this->options->timeout;
        $this->runtime = $runtime ?? new ClientRuntime($this->options->headers, $this->options->events);
    }

    /**
     * Отправляет запрос и возвращает DTO ответа.
     *
     * @template T of ResponseInterface
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param class-string<T> $responseClass Класс DTO ответа.
     *
     * @return T DTO ответа.
     *
     * @throws \InvalidArgumentException Если Prompt невалиден.
     * @throws \RuntimeException Если HTTP-транспорт, декодирование или гидрация завершились ошибкой.
     */
    protected function request(PromptInterface $prompt, string $responseClass): ResponseInterface
    {
        $prompt->validate();

        $headers = $this->runtime->mergeHeaders(['Accept' => 'application/json'], $this->runtime->getHeaders());

        $extraQuery = [];

        if ($prompt->requiresAuthorization()) {
            $headers = $this->runtime->mergeHeaders($headers, $this->authorizationStrategy->getAuthorizationHeaders($this->transport));

            if ($this->authorizationStrategy instanceof AuthorizationQueryStrategyInterface) {
                $extraQuery = $this->authorizationStrategy->getAuthorizationQueryParameters($this->transport);
            }
        }

        $httpRequest = $this->options->requestFactory->create(
            prompt: $prompt,
            baseUrl: $this->baseUrl,
            headers: $headers,
            timeout: $this->timeout,
            extraQuery: $extraQuery,
        );

        $this->runtime->dispatch(ClientEvents::BEFORE_REQUEST, new BeforeRequestEvent($this, $prompt, $httpRequest));

        try {
            $httpResponse = $this->sendWithRetry($httpRequest);
            $data = $this->options->responseDecoder->decode($httpResponse);
            $error = $httpResponse->statusCode >= 400 ? $this->options->errorFactory->create($httpResponse, $data) : null;

            try {
                $response = new $responseClass(
                    $data,
                    $error,
                    $httpResponse->statusCode,
                    $httpResponse->headers,
                    $httpResponse->body,
                    $data,
                    $httpRequest,
                    $this->options->strictValidation,
                );
            } catch (\Throwable $exception) {
                throw new ResponseHydrationException(
                    sprintf('Response DTO "%s" hydration failed: %s', $responseClass, $exception->getMessage()),
                    0,
                    $exception,
                );
            }
        } catch (\Throwable $exception) {
            $this->runtime->dispatch(ClientEvents::REQUEST_EXCEPTION, new RequestExceptionEvent($this, $prompt, $httpRequest, $exception));

            throw $exception;
        }

        $this->runtime->dispatch(ClientEvents::AFTER_REQUEST, new AfterRequestEvent($this, $prompt, $httpRequest, $httpResponse, $response));

        return $response;
    }

    /**
     * Отправляет HTTP-запрос с учётом retry policy.
     *
     * @param HttpRequest $httpRequest HTTP-запрос.
     *
     * @return HttpResponse HTTP-ответ.
     *
     * @throws \Throwable Если транспорт завершился ошибкой и retry policy не требует повтора.
     */
    protected function sendWithRetry(HttpRequest $httpRequest): HttpResponse
    {
        $attempt = 0;

        while (true) {
            ++$attempt;
            $httpRequest->metadata['attempt'] = $attempt;

            try {
                $response = $this->transport->send($httpRequest);
            } catch (\Throwable $exception) {
                if (!$this->options->retryPolicy->shouldRetry($attempt, $httpRequest, null, $exception)) {
                    throw $exception;
                }

                $this->waitBeforeRetry($this->options->retryPolicy->getDelayMs($attempt, $httpRequest, null, $exception));
                continue;
            }

            if (!$this->options->retryPolicy->shouldRetry($attempt, $httpRequest, $response)) {
                $httpRequest->metadata['attempts'] = $attempt;

                return $response;
            }

            $this->waitBeforeRetry($this->options->retryPolicy->getDelayMs($attempt, $httpRequest, $response));
        }
    }

    /**
     * Выполняет задержку перед повторной попыткой.
     *
     * @param int $delayMs Задержка в миллисекундах.
     *
     * @return void
     */
    protected function waitBeforeRetry(int $delayMs): void
    {
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }
}
