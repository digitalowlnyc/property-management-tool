<?php

namespace App\Http\Controllers;

use App\Services\CashflowService;
use App\Support\PropertyFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashflowController extends Controller
{
    public function index(Request $request, CashflowService $cashflow): Response
    {
        return Inertia::render('cashflow/index', [
            'cashflow' => $cashflow->forUser(
                $request->user(),
                PropertyFilter::selectedId($request->user()),
            ),
        ]);
    }
}
