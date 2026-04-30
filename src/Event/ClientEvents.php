<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Event;

/**
 * Хранит имена событий, поддерживаемых базовым SDK.
 */
final class ClientEvents
{
    public const AFTER_INIT = 'client.after_init';
    public const BEFORE_REQUEST = 'request.before';
    public const AFTER_REQUEST = 'request.after';
    public const REQUEST_EXCEPTION = 'request.exception';

    /**
     * Запрещает создание объекта со списком констант событий.
     *
     * @return void
     */
    private function __construct()
    {
    }
}
