<?php

declare(strict_types=1);

use App\Messaging\Handlers\ProductPublishedHandler;
use App\Messaging\Handlers\VariantChangedHandler;

return [
    'source' => env('SERVICE_NAME', 'pricing'),

    'consumer_group' => env('KAFKA_CONSUMER_GROUP_ID', env('SERVICE_NAME', 'pricing')),

    // Event type => handler. Every type listed here needs its topic in deploy/values.yaml → consumers.
    'handlers' => [
        'catalog.variant-changed' => VariantChangedHandler::class,
        'catalog.product-published' => ProductPublishedHandler::class,
    ],

    'max_attempts' => 3,

    'relay_batch_size' => 100,
];
