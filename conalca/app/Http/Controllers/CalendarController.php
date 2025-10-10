<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $events = [];
        $search = $request->get('search');
        
        // Aplicar filtro de búsqueda si existe
        $query = Appointment::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('client', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }
        
        $appointments = $query->get();

        if ($appointments->count()) {
            foreach ($appointments as $appointment) {
                $color = "";
                if($appointment->appointment_type == 'yellow'){
                    $color = "#ff7c32";
                }
                if($appointment->appointment_type == 'blue'){
                    $color = "#67AAFF";
                }
                if($appointment->appointment_type == 'red'){
                    $color = "#FF5656";
                }
                if($appointment->appointment_type == 'green'){
                    $color = "#04760B";
                }

                // Preparar título según el tipo
                $title = $appointment->title;
                if ($appointment->item_type === 'task') {
                    $title = '📋 ' . $title; // Añadir ícono para tareas
                    if ($appointment->completed) {
                        $title = '✅ ' . $appointment->title; // Ícono para tareas completadas
                    }
                }

                $events[] = [
                    'id' => $appointment->id,
                    'title' => $title,
                    'client' => $appointment->client,
                    'start' => $appointment->start_date . ' ' . $appointment->start_time,
                    'end' => $appointment->end_date . ' ' . $appointment->end_time,
                    'color' => $color,
                    'notes' => $appointment->notes,
                    'item_type' => $appointment->item_type ?? 'event',
                    'priority' => $appointment->priority ?? null,
                    'completed' => $appointment->completed ?? false,
                ];
            }
        }

        return view('calendar.show', compact('events', 'search'));
    }

    public function store(Request $request) {
        // Validación diferente según el tipo de item
        if ($request->item_type === 'event') {
            $this->validate($request, [
                'title' => 'required|string|max:255',
                'client' => 'required|string|max:255',
                'start_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'end_time' => 'nullable|date_format:H:i',
                'appointment_type' => 'required|in:blue,green,yellow,red',
                'notes' => 'nullable|string',
                'item_type' => 'required|in:event,task'
            ]);
        } else {
            $this->validate($request, [
                'title' => 'required|string|max:255',
                'start_date' => 'required|date', // Fecha límite para tareas
                'start_time' => 'nullable|date_format:H:i', // Hora límite opcional
                'appointment_type' => 'required|in:blue,green,yellow,red',
                'priority' => 'nullable|in:low,medium,high',
                'completed' => 'nullable|boolean',
                'notes' => 'nullable|string',
                'item_type' => 'required|in:event,task'
            ]);
        }

        // Crear el registro con los datos apropiados
        $appointmentData = [
            'title' => $request->title,
            'start_date' => $request->start_date,
            'start_time' => $request->start_time ?? '00:00:00',
            'appointment_type' => $request->appointment_type,
            'notes' => $request->notes,
            'notify' => $request->notify ?? 0,
            'item_type' => $request->item_type,
        ];

        if ($request->item_type === 'event') {
            // Datos específicos para eventos
            $appointmentData['client'] = $request->client;
            $appointmentData['end_date'] = $request->end_date ?? $request->start_date;
            $appointmentData['end_time'] = $request->end_time ?? $request->start_time;
        } else {
            // Datos específicos para tareas
            $appointmentData['client'] = 'Tarea personal'; // Valor por defecto
            $appointmentData['end_date'] = $request->start_date; // Fecha límite
            $appointmentData['end_time'] = $request->start_time ?? '23:59:59';
            $appointmentData['priority'] = $request->priority ?? 'medium';
            $appointmentData['completed'] = $request->has('completed') ? 1 : 0;
        }

        Appointment::create($appointmentData);

        return redirect()->route('calendar.show')->with('success', 
            $request->item_type === 'event' ? 'Evento creado correctamente' : 'Tarea creada correctamente'
        );
    }

    public function update(Request $request) {
        $existingAppointment = Appointment::where('id', (int)$request->edit_event_id)->first();

        if($existingAppointment) {
            Appointment::where('id', (int)$request->edit_event_id)->update([
                'title' => $request->edit_title,
                'client' => $existingAppointment->client,
                'start_date' => $request->edit_start_date,
                'end_date' => $request->edit_end_date,
                'start_time' => $request->edit_start_time,
                'end_time' => $request->edit_end_time,
                'appointment_type' => $request->edit_appointment_type,
                'notes' => $request->edit_notes
            ]);
            
            $itemType = $existingAppointment->item_type ?? 'event';
            $message = $itemType === 'event' ? 'Evento actualizado correctamente' : 'Tarea actualizada correctamente';
            return redirect()->route('calendar.show')->with('success', $message);
        }

        return redirect()->route('calendar.show')->with('error', 'No se pudo actualizar el elemento');
    }

    public function destroy(Request $request) {
        $appointment = Appointment::where('id', (int)$request->delete_event_id)->first();
        
        if ($appointment) {
            $itemType = $appointment->item_type ?? 'event';
            $appointment->delete();
            
            $message = $itemType === 'event' ? 'Evento eliminado correctamente' : 'Tarea eliminada correctamente';
            return redirect()->route('calendar.show')->with('success', $message);
        }
        
        return redirect()->route('calendar.show')->with('error', 'No se pudo eliminar el elemento');
    }
    
    // Método para búsqueda AJAX (opcional para futuras mejoras)
    public function search(Request $request)
    {
        $search = $request->get('q');
        $results = [];
        
        if ($search && strlen($search) >= 2) {
            $appointments = Appointment::where(function($query) use ($search) {
                $query->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('client', 'LIKE', "%{$search}%")
                      ->orWhere('notes', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get();
            
            foreach ($appointments as $appointment) {
                $results[] = [
                    'id' => $appointment->id,
                    'title' => $appointment->title,
                    'client' => $appointment->client,
                    'type' => $appointment->item_type ?? 'event',
                    'date' => $appointment->start_date,
                    'color' => $appointment->appointment_type
                ];
            }
        }
        
        return response()->json($results);
    }
}
