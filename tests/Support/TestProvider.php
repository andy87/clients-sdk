<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests\Support;

use Andy87\ClientsBase\Contracts\PromptInterface;
use Andy87\ClientsBase\Contracts\ResponseInterface;
use Andy87\ClientsBase\Provider\AbstractProvider;

/**
 * Тестовый provider, открывающий protected request() для PHPUnit.
 */
class TestProvider extends AbstractProvider
{
    /**
     * Выполняет тестовый API-запрос.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param class-string<ResponseInterface> $responseClass Класс DTO ответа.
     *
     * @return ResponseInterface DTO ответа.
     */
    public function call(PromptInterface $prompt, string $responseClass): ResponseInterface
    {
        return $this->request($prompt, $responseClass);
    }

    /**
     * Выполняет тестовый API-запрос с произвольным классом ответа для проверки runtime-контракта.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param class-string $responseClass Класс DTO ответа.
     *
     * @return ResponseInterface DTO ответа.
     */
    public function callAnyResponseClass(PromptInterface $prompt, string $responseClass): ResponseInterface
    {
        return $this->request($prompt, $responseClass);
    }
}
