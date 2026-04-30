<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Event;

/**
 * Описывает событие завершения инициализации API-клиента.
 */
class AfterInitEvent
{
    /**
     * Создаёт событие завершения инициализации API-клиента.
     *
     * @param object $client Инициализированный API-клиент.
     *
     * @return void
     */
    public function __construct(
        public object $client,
    ) {
    }
}
