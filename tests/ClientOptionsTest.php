<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests;

use Andy87\ClientsBase\Config\ClientOptions;
use Andy87\ClientsBase\Decoder\JsonResponseDecoder;
use Andy87\ClientsBase\Encoder\DefaultBodyEncoder;
use Andy87\ClientsBase\Encoder\DefaultQueryEncoder;
use Andy87\ClientsBase\Error\DefaultApiErrorFactory;
use Andy87\ClientsBase\Request\DefaultRequestFactory;
use Andy87\ClientsBase\Retry\NoRetryPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет настройки клиента по умолчанию.
 */
class ClientOptionsTest extends TestCase
{
    /**
     * Проверяет, что ClientOptions создаёт безопасные default-компоненты.
     *
     * @return void
     */
    public function testDefaultOptionsUseStrictSafeComponents(): void
    {
        $options = new ClientOptions();

        self::assertSame(30, $options->timeout);
        self::assertTrue($options->strictValidation);
        self::assertInstanceOf(NoRetryPolicy::class, $options->retryPolicy);
        self::assertInstanceOf(DefaultQueryEncoder::class, $options->queryEncoder);
        self::assertInstanceOf(DefaultBodyEncoder::class, $options->bodyEncoder);
        self::assertInstanceOf(JsonResponseDecoder::class, $options->responseDecoder);
        self::assertInstanceOf(DefaultApiErrorFactory::class, $options->errorFactory);
        self::assertInstanceOf(DefaultRequestFactory::class, $options->requestFactory);
    }
}
