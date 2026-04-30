# Clients Base

Base abstractions for building typed PHP API clients.

[Russian documentation](docs/ru/README.md)

## Overview

`andy87/clients-sdk` provides a small set of reusable building blocks for API client SDKs:

- prompt DTOs for request method, endpoint, path parameters, query parameters, body and validation;
- response DTOs for normalized response data, status code, headers and API errors;
- provider base class for executing typed API methods;
- pluggable authorization strategies;
- pluggable HTTP transport with a native PHP stream implementation.

The package does not generate API clients and does not depend on a specific HTTP client library.

## Requirements

- PHP 8.1 or higher.
- Composer.

## Installation

```bash
composer require andy87/clients-sdk
```

## Core Concepts

The package separates an API call into three parts:

- `PromptInterface` describes an outgoing request.
- `ResponseInterface` describes a typed API response.
- `AbstractProvider` connects prompts, responses, authorization and HTTP transport.

`NativeHttpTransport` can be used without extra dependencies. If a project needs another transport, implement `HttpTransportInterface`.

## Prompt DTO

Extend `AbstractPrompt` to describe a request. The base class hydrates declared properties from input data, validates required fields, builds path/query/body arrays and normalizes nested DTO values through `toArray()` or `toValue()` when those methods exist.

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Prompt\AbstractPrompt;

/**
 * Describes a request for loading one user by identifier.
 */
final class GetUserPrompt extends AbstractPrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/users/{id}';
    protected const FIELD_MAP = [
        'id' => 'id',
        'includePosts' => 'include_posts',
    ];
    protected const REQUIRED_FIELDS = ['id'];
    protected const PATH_FIELDS = ['id'];
    protected const QUERY_FIELDS = ['includePosts'];
    protected const BODY_FIELDS = [];
    protected const CONTENT_TYPE = null;

    public int $id;
    public ?bool $includePosts = null;
}
```

## Response DTO

Extend `AbstractResponse` to describe data returned by the API. On successful responses the base class hydrates properties listed in `FIELD_MAP` and validates `REQUIRED_FIELDS`. On HTTP errors it stores `ApiError` and skips required-field validation.

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Response\AbstractResponse;

/**
 * Contains user data returned by the API.
 */
final class GetUserResponse extends AbstractResponse
{
    protected const FIELD_MAP = [
        'id' => 'id',
        'name' => 'name',
    ];
    protected const REQUIRED_FIELDS = ['id', 'name'];

    public int $id;
    public string $name;
}
```

## Provider Usage

Extend `AbstractProvider` and expose public methods for concrete API operations. The protected `request()` method validates the prompt, adds authorization headers when required, sends the HTTP request and returns the requested response DTO.

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Provider\AbstractProvider;

/**
 * Provides typed access to user API methods.
 */
final class UsersProvider extends AbstractProvider
{
    /**
     * Loads one user by identifier.
     *
     * @param int $id User identifier.
     *
     * @return GetUserResponse Typed API response.
     *
     * @throws InvalidArgumentException When prompt validation fails.
     * @throws RuntimeException When authorization or transport fails.
     * @throws UnexpectedValueException When a successful response misses required fields.
     */
    public function getUser(int $id): GetUserResponse
    {
        return $this->request(
            new GetUserPrompt(['id' => $id]),
            GetUserResponse::class,
        );
    }
}
```

Create the provider with a base URL, authorization strategy and transport:

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Auth\NullAuthorizationStrategy;
use Andy87\ClientsBase\Config\ClientOptions;
use Andy87\ClientsBase\Http\NativeHttpTransport;
use Andy87\ClientsBase\Retry\DefaultRetryPolicy;

$provider = new UsersProvider(
    baseUrl: 'https://api.example.com',
    authorizationStrategy: new NullAuthorizationStrategy(),
    transport: new NativeHttpTransport(),
    options: new ClientOptions(
        timeout: 30,
        retryPolicy: new DefaultRetryPolicy(maxAttempts: 2),
    ),
);

$response = $provider->getUser(123);

if ($response->hasError()) {
    $error = $response->getError();
    echo $error?->message ?? 'API request failed.';
}

echo $response->getStatusCode();
```

## Client Options

`ClientOptions` is the main extension point. If it is not passed, the SDK uses safe defaults: JSON requests and responses, strict successful response validation, native no-retry policy and default request factory.

Configurable parts:

- `timeout`, `headers`, `events`;
- `strictValidation`;
- `retryPolicy`;
- `queryEncoder`;
- `bodyEncoder`;
- `responseDecoder`;
- `errorFactory`;
- `requestFactory`.

Retry is disabled by default. Use `DefaultRetryPolicy` only when repeated requests are safe for the target API operation.

## Runtime Events and Headers

`ClientRuntime` stores default request headers and event listeners shared by a client and its providers. Pass the same runtime object to providers that must share headers and listeners.

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Event\BeforeRequestEvent;
use Andy87\ClientsBase\Event\ClientEvents;
use Andy87\ClientsBase\Runtime\ClientRuntime;

$runtime = new ClientRuntime(
    headers: [
        'X-Client' => 'crm',
    ],
    events: [
        ClientEvents::BEFORE_REQUEST => static function (BeforeRequestEvent $event): void {
            $event->request->headers['X-Trace-Id'] = bin2hex(random_bytes(8));
        },
    ],
);

$runtime->addHeaders([
    'X-Account' => 'main',
]);
```

Supported events:

- `ClientEvents::AFTER_INIT` after a concrete client finishes initialization.
- `ClientEvents::BEFORE_REQUEST` before transport sends a mutable `HttpRequest`.
- `ClientEvents::AFTER_REQUEST` after raw HTTP response is converted to a typed response DTO.
- `ClientEvents::REQUEST_EXCEPTION` after transport, JSON decoding or response DTO construction fails.

Header names are merged case-insensitively. Authorization headers override default runtime headers, and `BEFORE_REQUEST` listeners can still mutate the final request.

## Authorization

Use `NullAuthorizationStrategy` for public APIs:

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Auth\NullAuthorizationStrategy;

$authorization = new NullAuthorizationStrategy();
```

Use `ClientCredentialsAuthorizationStrategy` for OAuth `client_credentials`. The strategy requests an access token through the configured transport and caches it in memory until it expires.

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Auth\ClientCredentialsAuthorizationStrategy;

$authorization = new ClientCredentialsAuthorizationStrategy(
    tokenUrl: 'https://auth.example.com/oauth/token',
    clientId: 'client-id',
    clientSecret: 'client-secret',
    scope: 'users.read',
    timeout: 30,
);
```

Other built-in strategies:

- `BearerTokenAuthorizationStrategy` for a static Bearer token;
- `BasicAuthorizationStrategy` for HTTP Basic auth;
- `ApiKeyAuthorizationStrategy` for header or query API keys;
- `CallbackAuthorizationStrategy` for project-specific authorization headers.

Prompts require authorization by default. Override the prompt constant when a request is public:

```php
protected const AUTHORIZATION_REQUIRED = false;
```

## HTTP Transport

`NativeHttpTransport` sends requests through PHP stream wrappers. It supports:

- query parameters;
- JSON request bodies;
- `application/x-www-form-urlencoded` request bodies;
- `multipart/form-data` request bodies through `MultipartFile`;
- already encoded raw request bodies;
- response status code and headers;
- JSON response decoding through `HttpResponse::json()`.

Custom transports must implement `HttpTransportInterface`:

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Contracts\HttpTransportInterface;
use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Sends HTTP requests through an application-specific client.
 */
final class CustomTransport implements HttpTransportInterface
{
    /**
     * Sends an HTTP request.
     *
     * @param HttpRequest $request Request data.
     *
     * @return HttpResponse Response data.
     *
     * @throws RuntimeException When the request cannot be sent.
     */
    public function send(HttpRequest $request): HttpResponse
    {
        throw new RuntimeException('Implement transport integration here.');
    }
}
```

## Error Handling

- Prompt validation throws `InvalidArgumentException` when a required field is missing or empty.
- Request factory validation can throw `ValidationException` when an endpoint contains an unfilled path placeholder.
- Authorization failures throw `AuthorizationException`.
- Transport failures throw `TransportException`.
- Successful non-JSON responses throw `ResponseDecodeException`.
- Response DTO construction failures throw `ResponseHydrationException`.
- HTTP responses with status code `400` or higher are converted to `ApiError` and available through `ResponseInterface::getError()`, including non-JSON error bodies.
- Successful responses with missing required fields throw `UnexpectedValueException` when `strictValidation` is enabled.

## License

MIT.
