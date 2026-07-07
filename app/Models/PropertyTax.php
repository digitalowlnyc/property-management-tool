<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $property_id
 * @property int $year
 * @property string $amount
 * @property bool $estimated
 * @property \Illuminate\Support\Carbon|null $bill_date
 */
#[Fillable(['year', 'amount', 'estimated', 'bill_date'])]
class PropertyTax extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyTaxFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount' => 'decimal:2',
            'estimated' => 'boolean',
            'bill_date' => 'date',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
