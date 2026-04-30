<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests;

use Andy87\ClientsBase\Auth\ApiKeyAuthorizationStrategy;
use Andy87\ClientsBase\Auth\ClientCredentialsAuthorizationStrategy;
use Andy87\ClientsBase\Auth\NullAuthorizationStrategy;
use Andy87\ClientsBase\Config\ClientOptions;
use Andy87\ClientsBase\Dto\ApiError;
use Andy87\ClientsBase\Exception\AuthorizationException;
use Andy87\ClientsBase\Exception\ResponseDecodeException;
use Andy87\ClientsBase\Exception\ValidationException;
use Andy87\ClientsBase\Encoder\DefaultBodyEncoder;
use Andy87\ClientsBase\Encoder\MultipartBodyEncoder;
use Andy87\ClientsBase\Http\HttpResponse;
use Andy87\ClientsBase\Http\HttpRequest;
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
        self::assertSame('https://api.example.test/users/10', $transport->requests[0]->url);
        self::assertSame(['include_posts' => true], $transport->requests[0]->query);
        self::assertSame('include_posts=1', $transport->requests[0]->metadata['queryString']);
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
     * Проверяет, что retry methods нормализуются независимо от регистра.
     *
     * @return void
     */
    public function testRetryPolicyNormalizesConfiguredMethods(): void
    {
        $policy = new DefaultRetryPolicy(maxAttempts: 2, methods: ['get']);

        self::assertTrue($policy->shouldRetry(
            1,
            new HttpRequest('GET', 'https://api.example.test'),
            new HttpResponse(503, [], '{}'),
        ));
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

        self::assertSame('https://api.example.test/users/1', $transport->requests[0]->url);
        self::assertSame(['api_key' => 'secret'], $transport->requests[0]->query);
        self::assertSame('api_key=secret', $transport->requests[0]->metadata['queryString']);
    }

    /**
     * Проверяет, что OAuth token request содержит закодированное form-urlencoded тело.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationUsesRawFormBody(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"access_token":"token","expires_in":3600}')]);
        $authorization = new ClientCredentialsAuthorizationStrategy('https://auth.example.test/token', 'client', 'secret');

        $headers = $authorization->getAuthorizationHeaders($transport);

        self::assertSame(['Authorization' => 'Bearer token'], $headers);
        self::assertSame('grant_type=client_credentials&client_id=client&client_secret=secret', $transport->requests[0]->rawBody);
        self::assertSame('application/x-www-form-urlencoded', $transport->requests[0]->headers['Content-Type']);
    }

    /**
     * Проверяет, что ошибка декодирования OAuth-ответа оборачивается в AuthorizationException.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationWrapsDecodeFailure(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], 'not-json')]);
        $authorization = new ClientCredentialsAuthorizationStrategy('https://auth.example.test/token', 'client', 'secret');

        try {
            $authorization->getAuthorizationHeaders($transport);
            self::fail('AuthorizationException was not thrown.');
        } catch (AuthorizationException $exception) {
            self::assertInstanceOf(ResponseDecodeException::class, $exception->getPrevious());
        }
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

    /**
     * Проверяет, что multipart Content-Type содержит фактический boundary.
     *
     * @return void
     */
    public function testMultipartBodyAddsBoundaryToContentType(): void
    {
        $body = (new MultipartBodyEncoder())->encode(['file' => 'abc'], 'multipart/form-data');

        self::assertStringContainsString('boundary=', $body->contentType ?? '');
        self::assertStringContainsString('--' . substr((string) $body->contentType, strpos((string) $body->contentType, 'boundary=') + 9), $body->content ?? '');
    }

    /**
     * Проверяет, что multipart encoder использует переданный boundary.
     *
     * @return void
     */
    public function testMultipartBodyUsesProvidedBoundary(): void
    {
        $body = (new MultipartBodyEncoder())->encode(['file' => 'abc'], 'multipart/form-data; boundary=test-boundary');

        self::assertSame('multipart/form-data; boundary=test-boundary', $body->contentType);
        self::assertStringContainsString('--test-boundary', $body->content ?? '');
    }

    /**
     * Проверяет регистронезависимый выбор encoder-а по Content-Type.
     *
     * @return void
     */
    public function testBodyEncoderMatchesContentTypeCaseInsensitively(): void
    {
        $body = (new DefaultBodyEncoder())->encode(['a' => 1], 'Application/X-WWW-FORM-URLENCODED');

        self::assertSame('a=1', $body->content);
        self::assertSame('Application/X-WWW-FORM-URLENCODED', $body->contentType);
    }

    /**
     * Проверяет, что строковый machine-readable code ошибки API сохраняется без приведения к int.
     *
     * @return void
     */
    public function testApiErrorPreservesStringCode(): void
    {
        $error = new ApiError(['error' => ['code' => 'invalid_request', 'message' => 'Bad request']], 400);

        self::assertSame('invalid_request', $error->code);
        self::assertSame(400, $error->statusCode);
    }
}
