<?php

namespace App\Models;

use App\Enums\AmountType;
use App\Enums\Frequency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $property_id
 * @property string $label
 * @property Frequency $frequency
 * @property AmountType $amount_type
 * @property string $amount
 * @property string|null $rent_basis
 */
#[Fillable(['label', 'frequency', 'amount_type', 'amount', 'rent_basis'])]
class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'frequency' => Frequency::class,
            'amount_type' => AmountType::class,
            'amount' => 'decimal:2',
            'rent_basis' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Resolve this expense to a per-occurrence dollar amount.
     *
     * Fixed expenses use their amount directly. Rent-based expenses (percentage
     * or months-of-rent) apply to an explicit rent basis, or the property's
     * current monthly rent when no basis is set.
     */
    public function occurrenceAmount(?float $currentMonthlyRent = null): float
    {
        if ($this->amount_type === AmountType::Fixed) {
            return (float) $this->amount;
        }

        $basis = $this->rent_basis !== null
            ? (float) $this->rent_basis
            : (float) ($currentMonthlyRent ?? $this->property->currentMonthlyRent());

        return match ($this->amount_type) {
            AmountType::Percentage => ((float) $this->amount / 100) * $basis,
            AmountType::MonthsOfRent => (float) $this->amount * $basis,
            default => 0.0,
        };
    }
}
