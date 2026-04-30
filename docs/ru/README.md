# Clients Base

Базовые абстракции для создания типизированных PHP API-клиентов.

[English documentation](../../README.md)

## Обзор

`andy87/clients-sdk` предоставляет небольшой набор переиспользуемых компонентов для SDK API-клиентов:

- prompt DTO для HTTP-метода, endpoint, path-параметров, query-параметров, тела запроса и валидации;
- response DTO для нормализованных данных ответа, HTTP-статуса, заголовков и ошибок API;
- базовый provider для выполнения типизированных API-методов;
- подключаемые стратегии авторизации;
- подключаемый HTTP-транспорт с нативной реализацией через PHP stream wrapper.

Пакет не генерирует API-клиенты и не привязан к конкретной HTTP-библиотеке.

## Требования

- PHP 8.1 или выше.
- Composer.

## Установка

```bash
composer require andy87/clients-sdk
```

## Основные понятия

Пакет разделяет API-вызов на три части:

- `PromptInterface` описывает исходящий запрос.
- `ResponseInterface` описывает типизированный ответ API.
- `AbstractProvider` связывает prompt, response, авторизацию и HTTP-транспорт.

`NativeHttpTransport` можно использовать без дополнительных зависимостей. Если проекту нужен другой транспорт, реализуйте `HttpTransportInterface`.

## Prompt DTO

Наследуйте `AbstractPrompt`, чтобы описать запрос. Базовый класс заполняет объявленные свойства из входных данных, проверяет обязательные поля, собирает path/query/body-массивы и нормализует вложенные DTO через `toArray()` или `toValue()`, если такие методы существуют.

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Prompt\AbstractPrompt;

/**
 * Описывает запрос получения одного пользователя по идентификатору.
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

Наследуйте `AbstractResponse`, чтобы описать данные, которые возвращает API. При успешном ответе базовый класс заполняет свойства из `FIELD_MAP` и проверяет `REQUIRED_FIELDS`. При HTTP-ошибке он сохраняет `ApiError` и пропускает проверку обязательных полей.

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Response\AbstractResponse;

/**
 * Хранит данные пользователя, возвращенные API.
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

## Использование provider

Наследуйте `AbstractProvider` и добавляйте публичные методы для конкретных API-операций. Защищенный метод `request()` валидирует prompt, добавляет заголовки авторизации, если они нужны, отправляет HTTP-запрос и возвращает указанный response DTO.

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Provider\AbstractProvider;

/**
 * Предоставляет типизированный доступ к API-методам пользователей.
 */
final class UsersProvider extends AbstractProvider
{
    /**
     * Загружает одного пользователя по идентификатору.
     *
     * @param int $id Идентификатор пользователя.
     *
     * @return GetUserResponse Типизированный ответ API.
     *
     * @throws InvalidArgumentException Если prompt не прошел валидацию.
     * @throws RuntimeException Если авторизация или транспорт завершились ошибкой.
     * @throws UnexpectedValueException Если в успешном ответе нет обязательных полей.
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

Создайте provider с базовым URL, стратегией авторизации и транспортом:

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

## Настройки клиента

`ClientOptions` — основная точка расширения SDK. Если объект не передан, SDK использует безопасные настройки по умолчанию: JSON-запросы и ответы, строгую проверку успешных ответов, отключённые повторы и стандартную фабрику запросов.

Настраиваемые части:

- `timeout`, `headers`, `events`;
- `strictValidation`;
- `retryPolicy`;
- `queryEncoder`;
- `bodyEncoder`;
- `responseDecoder`;
- `errorFactory`;
- `requestFactory`.

Повторы запросов выключены по умолчанию. Используйте `DefaultRetryPolicy` только для API-операций, где повтор безопасен.

## Runtime-события и заголовки

`ClientRuntime` хранит дефолтные заголовки запросов и обработчики событий, общие для клиента и его provider-ов. Передавайте один runtime-объект во все provider-ы, которым нужны общие заголовки и listeners.

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

Поддерживаемые события:

- `ClientEvents::AFTER_INIT` после завершения инициализации конкретного клиента.
- `ClientEvents::BEFORE_REQUEST` перед отправкой mutable `HttpRequest` транспортом.
- `ClientEvents::AFTER_REQUEST` после преобразования raw HTTP-ответа в типизированный response DTO.
- `ClientEvents::REQUEST_EXCEPTION` после ошибки транспорта, JSON-декодирования или создания response DTO.

Имена заголовков объединяются без учёта регистра. Заголовки авторизации перекрывают дефолтные runtime-заголовки, а listeners `BEFORE_REQUEST` могут изменить уже финальный запрос.

## Авторизация

Используйте `NullAuthorizationStrategy` для публичных API:

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Auth\NullAuthorizationStrategy;

$authorization = new NullAuthorizationStrategy();
```

Используйте `ClientCredentialsAuthorizationStrategy` для OAuth `client_credentials`. Стратегия запрашивает access token через настроенный транспорт и кэширует его в памяти процесса до истечения срока действия.

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

Другие встроенные стратегии:

- `BearerTokenAuthorizationStrategy` для статического Bearer token;
- `BasicAuthorizationStrategy` для HTTP Basic auth;
- `ApiKeyAuthorizationStrategy` для API key в header или query;
- `CallbackAuthorizationStrategy` для проектной логики авторизационных заголовков.

По умолчанию prompt требует авторизацию. Переопределите константу prompt, если запрос публичный:

```php
protected const AUTHORIZATION_REQUIRED = false;
```

## HTTP-транспорт

`NativeHttpTransport` отправляет запросы через PHP stream wrapper. Он поддерживает:

- query-параметры;
- JSON-тела запросов;
- тела запросов `application/x-www-form-urlencoded`;
- тела запросов `multipart/form-data` через `MultipartFile`;
- заранее закодированные raw-тела запросов;
- HTTP-статус и заголовки ответа;
- декодирование JSON-ответа через `HttpResponse::json()`.

Пользовательский транспорт должен реализовать `HttpTransportInterface`:

```php
<?php

declare(strict_types=1);

use Andy87\ClientsBase\Contracts\HttpTransportInterface;
use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Отправляет HTTP-запросы через клиент приложения.
 */
final class CustomTransport implements HttpTransportInterface
{
    /**
     * Отправляет HTTP-запрос.
     *
     * @param HttpRequest $request Данные запроса.
     *
     * @return HttpResponse Данные ответа.
     *
     * @throws RuntimeException Если запрос невозможно отправить.
     */
    public function send(HttpRequest $request): HttpResponse
    {
        throw new RuntimeException('Implement transport integration here.');
    }
}
```

## Обработка ошибок

- Валидация prompt выбрасывает `InvalidArgumentException`, если обязательное поле отсутствует или пустое.
- Валидация фабрики запроса выбрасывает `ValidationException`, если endpoint содержит незаполненный path-плейсхолдер.
- Ошибки авторизации выбрасывают `AuthorizationException`.
- Ошибки транспорта выбрасывают `TransportException`.
- Успешные не-JSON ответы выбрасывают `ResponseDecodeException`.
- Ошибки создания Response DTO выбрасывают `ResponseHydrationException`.
- HTTP-ответы со статусом `400` и выше преобразуются в `ApiError` и доступны через `ResponseInterface::getError()`, включая не-JSON тела ошибок.
- Успешные ответы без обязательных полей выбрасывают `UnexpectedValueException`, если включён `strictValidation`.

## Лицензия

MIT.
