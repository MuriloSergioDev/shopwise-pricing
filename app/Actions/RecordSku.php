<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Sku;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Upserts a SKU of the read model from a catalog event. Events from different topics can arrive out of order,
 * so a state older than the stored one is ignored.
 */
final readonly class RecordSku
{
    public function handle(string $sku, bool $active, CarbonInterface $changedAt): void
    {
        DB::transaction(function () use ($sku, $active, $changedAt): void {
            $known = Sku::query()->lockForUpdate()->find($sku);
            if ($known instanceof Sku && $known->changed_at->greaterThan($changedAt)) {
                return;
            }

            $known ??= new Sku(['sku' => $sku]);
            $known->fill(['active' => $active, 'changed_at' => $changedAt]);
            $known->save();
        });
    }
}
