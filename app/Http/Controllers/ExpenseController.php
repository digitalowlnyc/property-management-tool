<?php

namespace App\Http\Controllers;

use App\Enums\AmountType;
use App\Enums\Frequency;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\Property;
use App\Models\PropertyTax;
use App\Support\PropertyFilter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(): Response
    {
        $currentYear = (int) now()->year;
        $selectedId = PropertyFilter::selectedId(request()->user());

        $properties = request()->user()->properties()
            ->when($selectedId, fn ($query, $id) => $query->whereKey($id))
            ->with(['incomes', 'expenses', 'propertyTaxes'])
            ->orderBy('name')
            ->get();

        $expenses = $properties->flatMap(function (Property $property) {
            $currentMonthlyRent = $property->currentMonthlyRent();

            return $property->expenses
                ->sortBy('label')
                ->values()
                ->map(fn (Expense $expense) => [
                    'id' => $expense->id,
                    'property_id' => $property->id,
                    'property_name' => $property->name,
                    'label' => $expense->label,
                    'frequency' => $expense->frequency->value,
                    'amount_type' => $expense->amount_type->value,
                    'amount' => (float) $expense->amount,
                    'rent_basis' => $expense->rent_basis !== null ? (float) $expense->rent_basis : null,
                    'monthly_amount' => round($expense->frequency->monthlyEquivalent(
                        $expense->occurrenceAmount($currentMonthlyRent)
                    ), 2),
                ]);
        })->values();

        // Current-year property tax per property, surfaced here as a line item
        // (or a prompt to enter it on the Property Taxes page).
        $propertyTaxes = $properties->map(function (Property $property) use ($currentYear) {
            $record = $property->propertyTaxes->firstWhere('year', $currentYear);
            $amount = $record !== null ? (float) $record->amount : null;

            return [
                'property_id' => $property->id,
                'property_name' => $property->name,
                'amount' => $amount,
                'estimated' => $record?->estimated ?? false,
                'bill_date' => $record?->bill_date?->toDateString(),
                'monthly_amount' => $amount !== null ? round($amount / 12, 2) : null,
                'accrued' => $record !== null ? $this->accruedTax($record) : null,
            ];
        })->values();

        return Inertia::render('expenses/index', [
            'expenses' => $expenses,
            'properties' => $properties->map(fn (Property $property) => [
                'id' => $property->id,
                'name' => $property->name,
                'current_monthly_rent' => round($property->currentMonthlyRent(), 2),
            ]),
            'propertyTaxes' => $propertyTaxes,
            'currentYear' => $currentYear,
            'frequencies' => Frequency::options(),
            'amountTypes' => AmountType::options(),
        ]);
    }

    /**
     * How much of a property tax has accrued so far, assuming it builds
     * linearly over the 12 months ending on its bill date. Null when no bill
     * date is set (nothing to accrue against).
     */
    private function accruedTax(PropertyTax $tax): ?float
    {
        if ($tax->bill_date === null) {
            return null;
        }

        $amount = (float) $tax->amount;
        $billDate = $tax->bill_date;
        $accrualStart = $billDate->subYear();
        $today = now()->startOfDay();

        if ($today->lessThanOrEqualTo($accrualStart)) {
            return 0.0;
        }

        if ($today->greaterThanOrEqualTo($billDate)) {
            return round($amount, 2);
        }

        $totalDays = $accrualStart->diffInDays($billDate);
        $elapsedDays = $accrualStart->diffInDays($today);

        return round($amount * ($elapsedDays / $totalDays), 2);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $property = Property::findOrFail($request->integer('property_id'));
        $this->authorize('update', $property);

        $property->expenses()->create($request->safe()->except('property_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense added.']);

        return back();
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense->property);

        $expense->update($request->safe()->except('property_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense updated.']);

        return back();
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense->property);

        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense deleted.']);

        return back();
    }
}
