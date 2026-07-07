<?php

namespace App\Http\Controllers;

use App\Enums\Frequency;
use App\Enums\IncomeType;
use App\Http\Requests\IncomeRequest;
use App\Models\Income;
use App\Models\Property;
use App\Support\PropertyFilter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncomeController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        $selectedId = PropertyFilter::selectedId($user);

        $properties = $user->properties()
            ->when($selectedId, fn ($query, $id) => $query->whereKey($id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $incomes = Income::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->with('property:id,name')
            ->orderByDesc('start_date')
            ->orderBy('label')
            ->get()
            ->map(fn (Income $income) => [
                'id' => $income->id,
                'property_id' => $income->property_id,
                'property_name' => $income->property->name,
                'type' => $income->type->value,
                'label' => $income->label,
                'amount' => (float) $income->amount,
                'frequency' => $income->frequency->value,
                'start_date' => $income->start_date?->toDateString(),
                'end_date' => $income->end_date?->toDateString(),
            ]);

        return Inertia::render('income/index', [
            'incomes' => $incomes,
            'properties' => $properties,
            'incomeTypes' => IncomeType::options(),
            'frequencies' => Frequency::options(),
        ]);
    }

    public function store(IncomeRequest $request): RedirectResponse
    {
        $property = Property::findOrFail($request->integer('property_id'));
        $this->authorize('update', $property);

        $property->incomes()->create($request->safe()->except('property_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income added.']);

        return to_route('income.index');
    }

    public function update(IncomeRequest $request, Income $income): RedirectResponse
    {
        $this->authorize('update', $income->property);

        $income->update($request->safe()->except('property_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income updated.']);

        return to_route('income.index');
    }

    public function destroy(Income $income): RedirectResponse
    {
        $this->authorize('update', $income->property);

        $income->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income deleted.']);

        return to_route('income.index');
    }
}
