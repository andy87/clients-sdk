<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests\Support;

use Andy87\ClientsBase\Prompt\AbstractPrompt;

/**
 * Тестовый Prompt DTO для проверки кодирования тела запроса.
 */
class CreateUserPrompt extends AbstractPrompt
{
    protected const METHOD = 'POST';
    protected const ENDPOINT = '/users';
    protected const CONTENT_TYPE = 'application/json';
    protected const FIELD_MAP = ['name' => 'name'];
    protected const REQUIRED_FIELDS = ['name'];
    protected const BODY_FIELDS = ['name'];

    public string $name;
}
