<?php

declare(strict_types=1);

function mongoConfig(): array
{
    return [
        'uri' => getenv('TICKETFLOW_MONGODB_URI') ?: 'mongodb://127.0.0.1:27017',
        'database' => getenv('TICKETFLOW_MONGODB_DATABASE') ?: 'ticketflow_demo',
        'collection' => getenv('TICKETFLOW_MONGODB_COLLECTION') ?: 'tickets_demo',
    ];
}
