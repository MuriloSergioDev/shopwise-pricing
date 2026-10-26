<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\RecordSku;
use Carbon\CarbonImmutable;
use Shopwise\Contracts\Events\Catalog\ProductPublishedV1;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * A published product carries its active variants: records each SKU as active.
 */
final readonly class ProductPublishedHandler implements MessageHandler
{
    public function __construct(private RecordSku $recordSku) {}

    public function handle(Envelope $envelope): void
    {
        $event = ProductPublishedV1::fromArray($envelope->data);
        $publishedAt = CarbonImmutable::parse($event->publishedAt);

        foreach ($event->variants as $variant) {
            if (is_array($variant) && is_string($variant['sku'] ?? null)) {
                $this->recordSku->handle($variant['sku'], true, $publishedAt);
            }
        }
    }
}
