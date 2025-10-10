<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pricing;
use App\Models\Solicitation;

class PricingController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();
        $pricing = Pricing::create($data);

        // link back to solicitation
        if ($request->filled('solicitation_id')) {
            Solicitation::where('id', $request->solicitation_id)
                ->update([
                    'pricing_id' => $pricing->id,
                    'status'     => 'FINALIZED'
                ]);
        }
        return response()->json($pricing, 201);
    }

    public function update(Request $request, $id)
    {
        $pricing = Pricing::findOrFail($id);
        $pricing->update($request->all());
        return response()->json($pricing);
    }
}
