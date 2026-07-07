<?php

namespace App\Http\Controllers;

use App\Support\PropertyFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SelectedPropertyController extends Controller
{
    /**
     * Set the globally selected property (null = all properties).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => [
                'nullable',
                Rule::exists('properties', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        session([PropertyFilter::SESSION_KEY => $validated['property_id'] ?? null]);

        return back();
    }
}
