<?php

namespace App\Http\Controllers;

use App\Exports\SimplePricingTemplateExport;
use App\Models\Pricing;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PricingExportController extends Controller
{
    public function exportTemplate(Request $request)
    {
        // Get pricing type from request or session
        $pricingType = $request->get('type') ?? session('export_pricing_type');
        
        if (empty($pricingType)) {
            return response()->json(['error' => 'Tipo de pricing requerido'], 400);
        }

        try {
            $filename = 'template_' . $pricingType . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Get existing data for this pricing type to use as template
            $existingData = Pricing::where('type_pricing', $pricingType)->get();
            
            $export = new SimplePricingTemplateExport($pricingType, $existingData);
            
            // Clear the session
            session()->forget('export_pricing_type');
            
            return Excel::download($export, $filename);
            
        } catch (\Exception $e) {
            \Log::error('Export Controller Error', [
                'error' => $e->getMessage(),
                'pricing_type' => $pricingType,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Error al generar el template: ' . $e->getMessage()], 500);
        }
    }
}
