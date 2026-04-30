<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Config;

use Andy87\ClientsBase\Contracts\ApiErrorFactoryInterface;
use Andy87\ClientsBase\Contracts\BodyEncoderInterface;
use Andy87\ClientsBase\Contracts\QueryEncoderInterface;
use Andy87\ClientsBase\Contracts\RequestFactoryInterface;
use Andy87\ClientsBase\Contracts\RequestFinalizerInterface;
use Andy87\ClientsBase\Contracts\ResponseDecoderInterface;
use Andy87\ClientsBase\Contracts\RetryPolicyInterface;
use Andy87\ClientsBase\Decoder\JsonResponseDecoder;
use Andy87\ClientsBase\Encoder\DefaultBodyEncoder;
use Andy87\ClientsBase\Encoder\DefaultQueryEncoder;
use Andy87\ClientsBase\Error\DefaultApiErrorFactory;
use Andy87\ClientsBase\Http\HeaderUtils;
use Andy87\ClientsBase\Request\DefaultRequestFinalizer;
use Andy87\ClientsBase\Request\DefaultRequestFactory;
use Andy87\ClientsBase\Retry\NoRetryPolicy;

/**
 * Хранит настраиваемые параметры выполнения API-запросов.
 */
class ClientOptions
{
    /**
     * Создаёт настройки клиента.
     *
     * @param int $timeout Таймаут запросов в секундах.
     * @param array<string, string> $headers Дефолтные заголовки.
     * @param array<string, callable|list<callable>> $events Обработчики событий.
     * @param bool $strictValidation Проверять обязательные поля успешных ответов.
     * @param RetryPolicyInterface|null $retryPolicy Retry policy.
     * @param QueryEncoderInterface|null $queryEncoder Кодировщик query-параметров.
     * @param BodyEncoderInterface|null $bodyEncoder Кодировщик тела запроса.
     * @param ResponseDecoderInterface|null $responseDecoder Декодер ответа.
     * @param ApiErrorFactoryInterface|null $errorFactory Фабрика ошибок API.
     * @param RequestFactoryInterface|null $requestFactory Фабрика HTTP-запросов.
     * @param RequestFinalizerInterface|null $requestFinalizer Финализатор HTTP-запроса.
     *
     * @return void
     */
    public function __construct(
        public int $timeout = 30,
        public array $headers = [],
        public array $events = [],
        public bool $strictValidation = true,
        public ?RetryPolicyInterface $retryPolicy = null,
        public ?QueryEncoderInterface $queryEncoder = null,
        public ?BodyEncoderInterface $bodyEncoder = null,
        public ?ResponseDecoderInterface $responseDecoder = null,
        public ?ApiErrorFactoryInterface $errorFactory = null,
        public ?RequestFactoryInterface $requestFactory = null,
        public ?RequestFinalizerInterface $requestFinalizer = null,
    ) {
        $this->headers = HeaderUtils::merge([], $this->headers);
        $this->retryPolicy ??= new NoRetryPolicy();
        $this->queryEncoder ??= new DefaultQueryEncoder();
        $this->bodyEncoder ??= new DefaultBodyEncoder();
        $this->responseDecoder ??= new JsonResponseDecoder();
        $this->errorFactory ??= new DefaultApiErrorFactory();
        $this->requestFactory ??= new DefaultRequestFactory($this->queryEncoder, $this->bodyEncoder);
        $this->requestFinalizer ??= new DefaultRequestFinalizer($this->queryEncoder);
    }
}
