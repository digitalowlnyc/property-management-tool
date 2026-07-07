<?php

namespace App\Http\Controllers;

use App\Enums\AmountType;
use App\Enums\Frequency;
use App\Http\Requests\PropertyRequest;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(): Response
    {
        // The Properties tab always lists every property; the active one is
        // indicated in the UI via the shared property filter.
        $properties = request()->user()->properties()
            ->withCount(['incomes', 'expenses'])
            ->orderBy('name')
            ->get()
            ->map(fn (Property $property) => [
                'id' => $property->id,
                'name' => $property->name,
                'address' => $property->address,
                'incomes_count' => $property->incomes_count,
                'expenses_count' => $property->expenses_count,
            ]);

        return Inertia::render('properties/index', [
            'properties' => $properties,
        ]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        $request->user()->properties()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Property added.']);

        return to_route('properties.index');
    }

    public function show(Property $property): Response
    {
        $this->authorize('view', $property);

        $property->load(['expenses', 'incomes']);
        $currentMonthlyRent = $property->currentMonthlyRent();

        return Inertia::render('properties/show', [
            'property' => [
                'id' => $property->id,
                'name' => $property->name,
                'address' => $property->address,
                'notes' => $property->notes,
                'current_monthly_rent' => round($currentMonthlyRent, 2),
            ],
            'expenses' => $property->expenses
                ->sortBy('label')
                ->values()
                ->map(fn ($expense) => [
                    'id' => $expense->id,
                    'label' => $expense->label,
                    'frequency' => $expense->frequency->value,
                    'amount_type' => $expense->amount_type->value,
                    'amount' => (float) $expense->amount,
                    'rent_basis' => $expense->rent_basis !== null ? (float) $expense->rent_basis : null,
                    'monthly_amount' => round($expense->frequency->monthlyEquivalent(
                        $expense->occurrenceAmount($currentMonthlyRent)
                    ), 2),
                ]),
            'frequencies' => Frequency::options(),
            'amountTypes' => AmountType::options(),
        ]);
    }

    public function update(PropertyRequest $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $property->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Property updated.']);

        return to_route('properties.show', $property);
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        $property->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Property deleted.']);

        return to_route('properties.index');
    }
}
