<?php

declare(strict_types=1);

namespace App\Messaging\Handlers;

use App\Actions\RecordSku;
use Carbon\CarbonImmutable;
use Shopwise\Contracts\Events\Catalog\VariantChangedV1;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageHandler;

/**
 * Keeps the SKU read model in step with catalog: a new SKU becomes priceable, a deactivated one is marked inactive.
 */
final readonly class VariantChangedHandler implements MessageHandler
{
    public function __construct(private RecordSku $recordSku) {}

    public function handle(Envelope $envelope): void
    {
        $event = VariantChangedV1::fromArray($envelope->data);

        $this->recordSku->handle($event->sku, $event->active, CarbonImmutable::parse($event->changedAt));
    }
}
