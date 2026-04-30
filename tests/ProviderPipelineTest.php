<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests;

use Andy87\ClientsBase\Auth\ApiKeyAuthorizationStrategy;
use Andy87\ClientsBase\Auth\NullAuthorizationStrategy;
use Andy87\ClientsBase\Config\ClientOptions;
use Andy87\ClientsBase\Exception\ResponseDecodeException;
use Andy87\ClientsBase\Exception\ValidationException;
use Andy87\ClientsBase\Http\HttpResponse;
use Andy87\ClientsBase\Retry\DefaultRetryPolicy;
use Andy87\ClientsBase\Tests\Support\CreateUserPrompt;
use Andy87\ClientsBase\Tests\Support\FakeTransport;
use Andy87\ClientsBase\Tests\Support\GetUserPrompt;
use Andy87\ClientsBase\Tests\Support\TestProvider;
use Andy87\ClientsBase\Tests\Support\UserResponse;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет request pipeline базового provider-а.
 */
class ProviderPipelineTest extends TestCase
{
    /**
     * Проверяет успешный JSON-запрос на default-настройках.
     *
     * @return void
     */
    public function testDefaultRequestReturnsHydratedResponseWithMetadata(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, ['X-Request-Id' => 'abc'], '{"id":10,"name":"Ivan"}'),
        ]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        /** @var UserResponse $response */
        $response = $provider->call(new GetUserPrompt(['id' => 10, 'includePosts' => true]), UserResponse::class);

        self::assertSame(10, $response->id);
        self::assertSame('Ivan', $response->name);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['X-Request-Id' => 'abc'], $response->getHeaders());
        self::assertSame('{"id":10,"name":"Ivan"}', $response->getRawBody());
        self::assertSame(['id' => 10, 'name' => 'Ivan'], $response->getDecodedBody());
        self::assertNotNull($response->getRequest());
        self::assertSame('https://api.example.test/users/10?include_posts=1', $transport->requests[0]->url);
    }

    /**
     * Проверяет кодирование JSON-тела запроса.
     *
     * @return void
     */
    public function testJsonBodyIsEncodedBeforeTransport(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $provider->call(new CreateUserPrompt(['name' => 'Ivan']), UserResponse::class);

        self::assertSame('{"name":"Ivan"}', $transport->requests[0]->rawBody);
        self::assertSame('application/json', $transport->requests[0]->headers['Content-Type']);
    }

    /**
     * Проверяет, что HTTP-ошибка возвращается как Response с ApiError.
     *
     * @return void
     */
    public function testHttpErrorReturnsResponseWithApiError(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(400, ['X-Trace' => 't'], '{"error":{"code":123,"message":"Bad request","type":"validation"}}'),
        ]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $response = $provider->call(new GetUserPrompt(['id' => 5]), UserResponse::class);

        self::assertTrue($response->hasError());
        self::assertSame(400, $response->getStatusCode());
        self::assertSame(123, $response->getError()?->code);
        self::assertSame('Bad request', $response->getError()?->message);
        self::assertSame('{"error":{"code":123,"message":"Bad request","type":"validation"}}', $response->getError()?->rawBody);
    }

    /**
     * Проверяет, что не-JSON тело HTTP-ошибки не ломает Response flow.
     *
     * @return void
     */
    public function testNonJsonHttpErrorStillReturnsResponse(): void
    {
        $transport = new FakeTransport([new HttpResponse(500, [], '<html>error</html>')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $response = $provider->call(new GetUserPrompt(['id' => 5]), UserResponse::class);

        self::assertTrue($response->hasError());
        self::assertSame(500, $response->getError()?->statusCode);
        self::assertSame('<html>error</html>', $response->getRawBody());
    }

    /**
     * Проверяет, что успешный не-JSON ответ считается ошибкой декодирования.
     *
     * @return void
     */
    public function testSuccessfulNonJsonResponseThrowsDecodeException(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '<html>ok</html>')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $this->expectException(ResponseDecodeException::class);

        $provider->call(new GetUserPrompt(['id' => 5]), UserResponse::class);
    }

    /**
     * Проверяет retry policy при включённой настройке.
     *
     * @return void
     */
    public function testRetryPolicyIsOptIn(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(503, [], '{"error":{"message":"busy"}}'),
            new HttpResponse(200, [], '{"id":7,"name":"Retry"}'),
        ]);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            $transport,
            options: new ClientOptions(retryPolicy: new DefaultRetryPolicy(maxAttempts: 2, baseDelayMs: 0)),
        );

        /** @var UserResponse $response */
        $response = $provider->call(new GetUserPrompt(['id' => 7]), UserResponse::class);

        self::assertSame('Retry', $response->name);
        self::assertCount(2, $transport->requests);
        self::assertSame(2, $transport->requests[1]->metadata['attempts']);
    }

    /**
     * Проверяет query API key авторизацию.
     *
     * @return void
     */
    public function testApiKeyCanBeSentInQuery(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new ApiKeyAuthorizationStrategy('api_key', 'secret', ApiKeyAuthorizationStrategy::LOCATION_QUERY),
            $transport,
        );

        $provider->call(new GetUserPrompt(['id' => 1]), UserResponse::class);

        self::assertSame('https://api.example.test/users/1?api_key=secret', $transport->requests[0]->url);
    }

    /**
     * Проверяет ошибку незаполненного path-параметра.
     *
     * @return void
     */
    public function testMissingPathParameterThrowsValidationException(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $this->expectException(ValidationException::class);

        $provider->call(new class(['id' => 1]) extends GetUserPrompt {
            protected const PATH_FIELDS = [];
        }, UserResponse::class);
    }
}
