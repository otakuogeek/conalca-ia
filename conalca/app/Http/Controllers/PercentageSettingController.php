<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PercentageSetting;

class PercentageSettingController extends Controller
{
    public function index()
    {
        $setting = PercentageSetting::firstOrFail();
        return view('percentage-settings.index', compact('setting'));
    }

    public function update(Request $request, PercentageSetting $percentageSetting)
    {
        $validated = $request->validate([
            'min_percentage' => 'required|numeric|min:0|max:100',
            'avg_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'required|numeric|min:0|max:100',
            'use_custom_percentages' => 'nullable|boolean',
        ]);

        $percentageSetting->update([
            'min_percentage' => $validated['min_percentage'],
            'avg_percentage' => $validated['avg_percentage'],
            'max_percentage' => $validated['max_percentage'],
            'use_custom_percentages' => $request->boolean('use_custom_percentages'),
        ]);

        return redirect()->route('percentage-settings.index')->with('status', 'Percentages updated!');
    }
}
