<?php

namespace App\Http\Controllers;

use App\Models\TaraSetting;
use Illuminate\Http\Request;

class TaraSettingController extends Controller
{
    public function index()
    {
        $setting = TaraSetting::getInstance();
        return view('tara-settings.index', compact('setting'));
    }

    public function update(Request $request, TaraSetting $taraSetting)
    {
        $validated = $request->validate([
            'tara_contenedor_20' => 'required|numeric|min:0|max:99999',
            'tara_contenedor_40' => 'required|numeric|min:0|max:99999',
        ]);

        $taraSetting->update($validated);

        return redirect()->route('tara-settings.index')
            ->with('status', 'Valores de tara actualizados correctamente.');
    }
}
