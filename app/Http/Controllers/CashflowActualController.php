<?php

namespace App\Http\Controllers;

use App\Services\CashflowService;
use App\Support\PropertyFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashflowActualController extends Controller
{
    public function index(Request $request, CashflowService $cashflow): Response
    {
        $user = $request->user();
        $selectedId = PropertyFilter::selectedId($user);

        // Load only the selected property (or all) with the relations the
        // actual-cashflow calculations need.
        $properties = $user->properties()
            ->when($selectedId, fn ($query, $id) => $query->whereKey($id))
            ->with(['incomes', 'expenses', 'propertyTaxes'])
            ->orderBy('name')
            ->get();
        $user->setRelation('properties', $properties);

        $years = $cashflow->availableYears($user);

        // Default to the current year when available, otherwise the latest year.
        $year = $request->integer('year');
        if (! in_array($year, $years, true)) {
            $currentYear = (int) now()->year;
            $year = in_array($currentYear, $years, true) ? $currentYear : ($years[0] ?? $currentYear);
        }

        return Inertia::render('cashflow-actual/index', [
            'year' => $year,
            'availableYears' => $years,
            ...$cashflow->actualForUser($user, $year),
        ]);
    }
}
