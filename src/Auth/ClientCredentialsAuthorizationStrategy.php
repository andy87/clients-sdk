<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Auth;

use Andy87\ClientsBase\Contracts\AuthorizationStrategyInterface;
use Andy87\ClientsBase\Contracts\HttpTransportInterface;
use Andy87\ClientsBase\Exception\AuthorizationException;
use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Выполняет OAuth client_credentials авторизацию и кэширует access token в памяти процесса.
 */
class ClientCredentialsAuthorizationStrategy implements AuthorizationStrategyInterface
{
    private ?string $accessToken = null;
    private int $expiresAt = 0;

    /**
     * Создаёт стратегию OAuth client_credentials.
     *
     * @param string $tokenUrl URL получения токена.
     * @param string $clientId Client ID.
     * @param string $clientSecret Client Secret.
     * @param string|null $scope OAuth scope.
     * @param int $timeout Таймаут запроса токена.
     *
     * @return void
     */
    public function __construct(
        private string $tokenUrl,
        private string $clientId,
        private string $clientSecret,
        private ?string $scope = null,
        private int $timeout = 30,
    ) {
    }

    /**
     * Возвращает Bearer-заголовок авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для запроса токена.
     *
     * @return array<string, string>
     *
     * @throws AuthorizationException Если токен не получен.
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        if ($this->accessToken === null || time() >= $this->expiresAt) {
            $this->requestToken($transport);
        }

        return ['Authorization' => 'Bearer ' . $this->accessToken];
    }

    /**
     * Запрашивает новый access token.
     *
     * @param HttpTransportInterface $transport Транспорт.
     *
     * @return void
     *
     * @throws AuthorizationException Если API авторизации вернул ошибку.
     */
    private function requestToken(HttpTransportInterface $transport): void
    {
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        if ($this->scope !== null) {
            $body['scope'] = $this->scope;
        }

        $response = $transport->send(new HttpRequest(
            method: 'POST',
            url: $this->tokenUrl,
            headers: ['Accept' => 'application/json'],
            body: $body,
            contentType: 'application/x-www-form-urlencoded',
            timeout: $this->timeout,
        ));

        $data = $response->json();

        if ($response->statusCode >= 400 || !isset($data['access_token'])) {
            throw new AuthorizationException('OAuth client_credentials authorization failed.');
        }

        $this->accessToken = (string) $data['access_token'];
        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
        $this->expiresAt = time() + max(60, $expiresIn - 60);
    }
}
