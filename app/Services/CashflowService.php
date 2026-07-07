<?php

namespace App\Services;

use App\Enums\Frequency;
use App\Enums\IncomeType;
use App\Models\Income;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;

class CashflowService
{
    /**
     * Cashflow summary for a single property.
     *
     * Expects the property's `incomes` and `expenses` relations to be loaded.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     current_monthly_rent: float,
     *     monthly_income: float,
     *     monthly_expenses: float,
     *     monthly_net: float,
     *     annual_income: float,
     *     annual_expenses: float,
     *     annual_net: float,
     * }
     */
    public function forProperty(Property $property): array
    {
        $currentMonthlyRent = $property->currentMonthlyRent();

        $monthlyIncome = 0.0;
        $annualIncome = 0.0;

        // Only income currently in effect counts toward the run-rate estimate.
        // Ended or not-yet-started entries (e.g. a prior tenant's lease) are
        // excluded so non-overlapping rents are never summed together.
        $today = Carbon::today();
        foreach ($property->incomes as $income) {
            if (! $this->activeOnDate($income, $today)) {
                continue;
            }

            $amount = (float) $income->amount;
            $monthlyIncome += $income->frequency->monthlyRunRate($amount);
            $annualIncome += $income->frequency->annualAmount($amount);
        }

        $monthlyExpenses = 0.0;
        $annualExpenses = 0.0;

        foreach ($property->expenses as $expense) {
            $base = $expense->occurrenceAmount($currentMonthlyRent);
            $monthlyExpenses += $expense->frequency->monthlyRunRate($base);
            $annualExpenses += $expense->frequency->annualAmount($base);
        }

        // Current-year property tax counts as a yearly expense (amortized monthly).
        $currentYearTax = $property->taxForYear((int) now()->year);
        $monthlyExpenses += $currentYearTax / 12;
        $annualExpenses += $currentYearTax;

        return [
            'id' => $property->id,
            'name' => $property->name,
            'current_monthly_rent' => round($currentMonthlyRent, 2),
            'monthly_income' => round($monthlyIncome, 2),
            'monthly_expenses' => round($monthlyExpenses, 2),
            'monthly_net' => round($monthlyIncome - $monthlyExpenses, 2),
            'annual_income' => round($annualIncome, 2),
            'annual_expenses' => round($annualExpenses, 2),
            'annual_net' => round($annualIncome - $annualExpenses, 2),
        ];
    }

    /**
     * Portfolio cashflow: a per-property breakdown plus summed totals.
     *
     * @return array{
     *     totals: array<string, float>,
     *     properties: array<int, array<string, mixed>>,
     * }
     */
    public function forUser(User $user, ?int $propertyId = null): array
    {
        $properties = $user->properties()
            ->when($propertyId, fn ($query, $id) => $query->whereKey($id))
            ->with(['incomes', 'expenses', 'propertyTaxes'])
            ->orderBy('name')
            ->get();

        $rows = $properties->map(fn (Property $property) => $this->forProperty($property))->all();

        $totals = [
            'monthly_income' => 0.0,
            'monthly_expenses' => 0.0,
            'monthly_net' => 0.0,
            'annual_income' => 0.0,
            'annual_expenses' => 0.0,
            'annual_net' => 0.0,
        ];

        foreach ($rows as $row) {
            foreach (array_keys($totals) as $key) {
                $totals[$key] += $row[$key];
            }
        }

        $totals = array_map(fn (float $value) => round($value, 2), $totals);

        return [
            'totals' => $totals,
            'properties' => $rows,
        ];
    }

    /**
     * Distinct years that have at least one rent entry, newest first.
     *
     * A rent's span runs from its start year to its end year (open-ended rents
     * run through the current year).
     *
     * @return array<int, int>
     */
    public function availableYears(User $user): array
    {
        $current = (int) now()->year;
        $years = collect();

        foreach ($user->properties as $property) {
            foreach ($property->incomes as $income) {
                if ($income->type !== IncomeType::Rent) {
                    continue;
                }

                $start = $income->start_date?->year ?? $income->end_date?->year ?? $current;
                $end = $income->end_date?->year ?? $current;

                for ($y = min($start, $end); $y <= max($start, $end); $y++) {
                    $years->push($y);
                }
            }
        }

        return $years->unique()->sortDesc()->values()->all();
    }

    /**
     * Actual cashflow for a specific year across the user's properties.
     *
     * @return array{totals: array<string, float>, properties: array<int, array<string, mixed>>}
     */
    public function actualForUser(User $user, int $year): array
    {
        $rows = $user->properties
            ->sortBy('name')
            ->map(fn (Property $property) => $this->actualForProperty($property, $year))
            ->values()
            ->all();

        $totals = ['income' => 0.0, 'expenses' => 0.0, 'net' => 0.0];
        foreach ($rows as $row) {
            $totals['income'] += $row['income_total'];
            $totals['expenses'] += $row['expense_total'];
            $totals['net'] += $row['net'];
        }
        $totals = array_map(fn (float $value) => round($value, 2), $totals);

        return ['totals' => $totals, 'properties' => $rows];
    }

    /**
     * Actual cashflow for one property in a given year.
     *
     * Rent is counted month-by-month, so months with no active rent period
     * (vacancies and gaps between leases) collect nothing. Expenses are listed
     * individually at their annual cost, plus that year's property tax.
     *
     * @return array<string, mixed>
     */
    public function actualForProperty(Property $property, int $year): array
    {
        $rentEntries = $property->incomes->filter(fn (Income $i) => $i->type === IncomeType::Rent);

        $daysInYear = Carbon::create($year, 1, 1)->isLeapYear() ? 366 : 365;
        $actualRent = 0.0;
        $monthlyRentForYear = 0.0;

        // Walk every day so partial months (leases ending mid-month, gaps
        // between tenants) are counted and rent is prorated by day.
        $fullOccupiedMonths = 0;
        $fullVacantMonths = 0;
        $leftoverOccupiedDays = 0;
        $leftoverVacantDays = 0;
        $occupiedDays = 0;

        for ($month = 1; $month <= 12; $month++) {
            $daysInMonth = (int) Carbon::create($year, $month, 1)->daysInMonth;
            $occupiedInMonth = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::create($year, $month, $day)->startOfDay();
                $active = $rentEntries->first(fn (Income $i) => $this->activeOnDate($i, $date));

                if ($active !== null) {
                    $occupiedInMonth++;
                    $monthlyRent = $active->frequency->monthlyEquivalent((float) $active->amount);
                    $actualRent += $monthlyRent / $daysInMonth;
                    $monthlyRentForYear = $monthlyRent;
                }
            }

            $occupiedDays += $occupiedInMonth;

            if ($occupiedInMonth === $daysInMonth) {
                $fullOccupiedMonths++;
            } elseif ($occupiedInMonth === 0) {
                $fullVacantMonths++;
            } else {
                $leftoverOccupiedDays += $occupiedInMonth;
                $leftoverVacantDays += $daysInMonth - $occupiedInMonth;
            }
        }

        $vacantDays = $daysInYear - $occupiedDays;
        $occupiedLabel = $this->describeDuration($fullOccupiedMonths, $leftoverOccupiedDays);
        $vacantLabel = $this->describeDuration($fullVacantMonths, $leftoverVacantDays);

        // Income line items.
        $incomeLines = [[
            'label' => 'Rent',
            'detail' => "{$occupiedLabel} occupied"
                .($vacantDays > 0 ? " · {$vacantLabel} vacant" : ''),
            'amount' => round($actualRent, 2),
        ]];

        foreach ($property->incomes as $income) {
            if ($income->type === IncomeType::Rent) {
                continue;
            }

            $amount = $this->otherIncomeForYear($income, $year);
            if ($amount <= 0) {
                continue;
            }

            $incomeLines[] = [
                'label' => $income->label ?: 'Other income',
                'detail' => $income->frequency->label(),
                'amount' => round($amount, 2),
            ];
        }

        $incomeTotal = array_sum(array_column($incomeLines, 'amount'));

        // Expense line items (each expense at its annual cost for the year).
        $expenseLines = [];
        foreach ($property->expenses->sortBy('label') as $expense) {
            $annual = $expense->frequency->annualAmount($expense->occurrenceAmount($monthlyRentForYear));
            $expenseLines[] = [
                'label' => $expense->label,
                'detail' => $expense->frequency->label(),
                'amount' => round($annual, 2),
            ];
        }

        // Property tax recorded for this specific year.
        $taxRecord = $property->propertyTaxes->firstWhere('year', $year);
        $expenseLines[] = [
            'label' => $taxRecord?->estimated ? 'Property Tax - Estimated' : 'Property tax',
            'detail' => (string) $year,
            'amount' => round($taxRecord !== null ? (float) $taxRecord->amount : 0.0, 2),
        ];

        $expenseTotal = array_sum(array_column($expenseLines, 'amount'));

        return [
            'id' => $property->id,
            'name' => $property->name,
            'occupied_label' => $occupiedLabel,
            'vacant_label' => $vacantLabel,
            'occupied_days' => $occupiedDays,
            'vacant_days' => $vacantDays,
            'income_lines' => $incomeLines,
            'expense_lines' => $expenseLines,
            'income_total' => round($incomeTotal, 2),
            'expense_total' => round($expenseTotal, 2),
            'net' => round($incomeTotal - $expenseTotal, 2),
        ];
    }

    /**
     * Whether an income's date range covers the given day (inclusive).
     */
    private function activeOnDate(Income $income, Carbon $date): bool
    {
        $startsOk = $income->start_date === null || $income->start_date->lessThanOrEqualTo($date);
        $endsOk = $income->end_date === null || $income->end_date->greaterThanOrEqualTo($date);

        return $startsOk && $endsOk;
    }

    /**
     * Render whole months plus leftover days as e.g. "11 months, 2 weeks".
     */
    private function describeDuration(int $months, int $days): string
    {
        $parts = [];

        if ($months > 0) {
            $parts[] = $months.' month'.($months === 1 ? '' : 's');
        }

        $weeks = intdiv($days, 7);
        $remainderDays = $days % 7;

        if ($weeks > 0) {
            $parts[] = $weeks.' week'.($weeks === 1 ? '' : 's');
        }
        if ($remainderDays > 0) {
            $parts[] = $remainderDays.' day'.($remainderDays === 1 ? '' : 's');
        }

        return $parts === [] ? '0 days' : implode(', ', $parts);
    }

    /**
     * Whether an income's date range overlaps the given month.
     */
    private function activeInMonth(Income $income, int $year, int $month): bool
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();

        $startsOk = $income->start_date === null || $income->start_date->lessThanOrEqualTo($monthEnd);
        $endsOk = $income->end_date === null || $income->end_date->greaterThanOrEqualTo($monthStart);

        return $startsOk && $endsOk;
    }

    /**
     * Actual amount collected from a non-rent income in a given year.
     */
    private function otherIncomeForYear(Income $income, int $year): float
    {
        if ($income->frequency === Frequency::OneTime) {
            $onYear = $income->start_date?->year;

            return ($onYear === null || $onYear === $year) ? (float) $income->amount : 0.0;
        }

        $activeMonths = 0;
        for ($month = 1; $month <= 12; $month++) {
            if ($this->activeInMonth($income, $year, $month)) {
                $activeMonths++;
            }
        }

        return $activeMonths * $income->frequency->monthlyEquivalent((float) $income->amount);
    }
}
