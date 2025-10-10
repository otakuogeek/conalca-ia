<?php

namespace App\Http\Controllers;

use App\Models\DataColumn;
use Illuminate\Http\Request;

class DataColumnController extends Controller
{
    public function getData($id, $column)
    {
        $record = DataColumn::where('id_target', $id)
            ->where('column', $column)
            ->first();

        if (!$record) {
            return null;
        }

        return $record;
    }

    public function getMultipleData(Request $request)
    {
        // Validamos que la petición tenga el formato correcto
        $data = $request->validate([
            'queries' => 'required|array',
            'queries.*.id_target' => 'required|integer',
            'queries.*.column' => 'required|string',
        ]);

        // Buscamos los registros en la base de datos
        $records = DataColumn::whereIn('id_target', collect($data['queries'])->pluck('id_target'))
            ->whereIn('column', collect($data['queries'])->pluck('column'))
            ->get();

        // Formateamos la respuesta en un JSON con los valores encontrados
        $response = collect($data['queries'])->map(function ($query) use ($records) {
            $record = $records->where('id_target', $query['id_target'])
                ->where('column', $query['column'])
                ->first();

            return [
                'id_target' => $query['id_target'],
                'column' => $query['column'],
                'value' => $record ? $record->value : null,
            ];
        });

        return response()->json($response);
    }
}
