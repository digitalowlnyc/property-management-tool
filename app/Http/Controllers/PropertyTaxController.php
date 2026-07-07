<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyTaxRequest;
use App\Models\Property;
use App\Models\PropertyTax;
use App\Support\PropertyFilter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyTaxController extends Controller
{
    public function index(): Response
    {
        $selectedId = PropertyFilter::selectedId(request()->user());

        $properties = request()->user()->properties()
            ->when($selectedId, fn ($query, $id) => $query->whereKey($id))
            ->with(['propertyTaxes' => fn ($query) => $query->orderBy('year')])
            ->orderBy('name')
            ->get();

        $data = $properties->map(function (Property $property) {
            $previous = null;

            $taxes = $property->propertyTaxes->map(function (PropertyTax $tax) use (&$previous) {
                $amount = (float) $tax->amount;

                // Year-over-year change vs the previous recorded year.
                $changeAbs = $previous !== null ? round($amount - $previous, 2) : null;
                $changePct = ($previous !== null && $previous != 0.0)
                    ? round((($amount - $previous) / $previous) * 100, 2)
                    : null;

                $previous = $amount;

                return [
                    'id' => $tax->id,
                    'year' => $tax->year,
                    'amount' => $amount,
                    'estimated' => $tax->estimated,
                    'bill_date' => $tax->bill_date?->toDateString(),
                    'change_abs' => $changeAbs,
                    'change_pct' => $changePct,
                ];
            })->values();

            return [
                'id' => $property->id,
                'name' => $property->name,
                'taxes' => $taxes,
            ];
        });

        return Inertia::render('property-taxes/index', [
            'properties' => $data,
            'currentYear' => (int) now()->year,
        ]);
    }

    public function store(PropertyTaxRequest $request): RedirectResponse
    {
        $property = Property::findOrFail($request->integer('property_id'));
        $this->authorize('update', $property);

        $property->propertyTaxes()->create($request->safe()->except('property_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tax record added.']);

        return to_route('property-taxes.index');
    }

    public function update(PropertyTaxRequest $request, PropertyTax $propertyTax): RedirectResponse
    {
        $this->authorize('update', $propertyTax->property);

        $propertyTax->update($request->safe()->except('property_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tax record updated.']);

        return to_route('property-taxes.index');
    }

    public function destroy(PropertyTax $propertyTax): RedirectResponse
    {
        $this->authorize('update', $propertyTax->property);

        $propertyTax->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tax record deleted.']);

        return to_route('property-taxes.index');
    }
}
