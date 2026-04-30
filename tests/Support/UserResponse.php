<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests\Support;

use Andy87\ClientsBase\Response\AbstractResponse;

/**
 * Тестовый Response DTO для проверки гидрации ответа.
 */
class UserResponse extends AbstractResponse
{
    protected const FIELD_MAP = ['id' => 'id', 'name' => 'name'];
    protected const REQUIRED_FIELDS = ['id'];

    public int $id;
    public ?string $name = null;
}
