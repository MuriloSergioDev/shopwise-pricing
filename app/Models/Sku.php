<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SkuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A SKU as catalog announced it. Read model only: catalog owns variants.
 *
 * @property string $sku
 * @property bool $active
 * @property Carbon $changed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['sku', 'active', 'changed_at'])]
#[WithoutIncrementing]
final class Sku extends Model
{
    /** @use HasFactory<SkuFactory> */
    use HasFactory;

    protected $primaryKey = 'sku';

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['active' => 'boolean', 'changed_at' => 'datetime'];
    }
}
