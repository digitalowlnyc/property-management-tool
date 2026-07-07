<?php

namespace App\Models;

use App\Enums\Frequency;
use App\Enums\IncomeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $property_id
 * @property IncomeType $type
 * @property string|null $label
 * @property string $amount
 * @property Frequency $frequency
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 */
#[Fillable(['type', 'label', 'amount', 'frequency', 'start_date', 'end_date'])]
class Income extends Model
{
    /** @use HasFactory<\Database\Factories\IncomeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => IncomeType::class,
            'frequency' => Frequency::class,
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
