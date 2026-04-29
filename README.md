# Clients Base

Base abstractions for building typed PHP API clients.

[Russian documentation](docs/ru/README.md)

## Overview

`andy87/clients-base` provides a small set of reusable building blocks for API client SDKs:

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
composer require andy87/clients-base
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
use Andy87\ClientsBase\Http\NativeHttpTransport;

$provider = new UsersProvider(
    baseUrl: 'https://api.example.com',
    authorizationStrategy: new NullAuthorizationStrategy(),
    transport: new NativeHttpTransport(),
    timeout: 30,
);

$response = $provider->getUser(123);

if ($response->hasError()) {
    $error = $response->getError();
    echo $error?->message ?? 'API request failed.';
}
```

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

Prompts require authorization by default. Override the prompt constant when a request is public:

```php
protected const AUTHORIZATION_REQUIRED = false;
```

## HTTP Transport

`NativeHttpTransport` sends requests through PHP stream wrappers. It supports:

- query parameters;
- JSON request bodies;
- `application/x-www-form-urlencoded` request bodies;
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
- Authorization can throw `RuntimeException` when the OAuth token cannot be received.
- Transport failures throw `RuntimeException`.
- Non-JSON responses throw `RuntimeException` during `HttpResponse::json()`.
- HTTP responses with status code `400` or higher are converted to `ApiError` and available through `ResponseInterface::getError()`.
- Successful responses with missing required fields throw `UnexpectedValueException`.

## License

MIT.
