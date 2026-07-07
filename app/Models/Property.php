<?php

namespace App\Models;

use App\Enums\IncomeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $address
 * @property string|null $notes
 */
#[Fillable(['name', 'address', 'notes'])]
class Property extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function propertyTaxes(): HasMany
    {
        return $this->hasMany(PropertyTax::class);
    }

    /**
     * The property tax amount recorded for a given year, if any.
     */
    public function taxForYear(int $year): float
    {
        $tax = $this->propertyTaxes->firstWhere('year', $year);

        return $tax !== null ? (float) $tax->amount : 0.0;
    }

    /**
     * The single rent income currently active for this property, if any.
     *
     * "Active" means the rent's date range covers today (open-ended dates
     * count as always active).
     */
    public function currentRent(?Carbon $on = null): ?Income
    {
        $on = ($on ?? Carbon::today())->toDateString();

        return $this->incomes
            ->where('type', IncomeType::Rent)
            ->first(function (Income $income) use ($on) {
                $startsOk = $income->start_date === null || $income->start_date->toDateString() <= $on;
                $endsOk = $income->end_date === null || $income->end_date->toDateString() >= $on;

                return $startsOk && $endsOk;
            });
    }

    /**
     * The current rent normalized to a monthly figure — the base used for
     * percentage-of-rent expenses when no explicit basis is given.
     */
    public function currentMonthlyRent(?Carbon $on = null): float
    {
        $rent = $this->currentRent($on);

        if ($rent === null) {
            return 0.0;
        }

        return $rent->frequency->monthlyEquivalent((float) $rent->amount);
    }
}
