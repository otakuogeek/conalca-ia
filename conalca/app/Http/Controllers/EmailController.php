<?php

namespace App\Http\Controllers;

use App\Models\Email;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'files.*' => 'file|max:2048',
        ]);

        $filePaths = [];
        $uploadPath = public_path('uploads/');
        if($request->file('files')){
            foreach ($request->file('files') as $file) {
                // Obtener el nombre original del archivo
                $fileName = time() . '_' . $file->getClientOriginalName();

                // Mover el archivo a la ruta especificada
                $file->move($uploadPath, $fileName);

                // Guardar la ruta del archivo en el array
                $filePaths[] = 'uploads/' . $fileName;
            }
        }

        // Guardar los detalles del correo en la base de datos
        $users = $request->to_users;
        foreach ($users as $key => $user) {
            Email::create([
                'from_user' => auth()->id(),
                'to_user' => $user,
                'subject' => $request->subject,
                'description' => $request->description,
                'files' => json_encode($filePaths),
                'status' => 'no-read',
            ]);
        }


        return redirect()->back()->with('success', 'Correo enviado con éxito');
    }

    public function reply(Request $request){
        $email_id = $request->main_email_id;
        $email = Email::find($email_id);

        $filePaths = [];
        $uploadPath = public_path('uploads/');
        if($request->file('files')){
            foreach ($request->file('files') as $file) {
                // Obtener el nombre original del archivo
                $fileName = time() . '_' . $file->getClientOriginalName();

                // Mover el archivo a la ruta especificada
                $file->move($uploadPath, $fileName);

                // Guardar la ruta del archivo en el array
                $filePaths[] = 'uploads/' . $fileName;
            }
        }

        Email::create([
            'from_user' => auth()->id(),
            'to_user' => $request->to_user,
            'subject' => 'RE:'.$email->subject,
            'description' => $request->description,
            'files' => json_encode($filePaths),
            'status' => 'no-read',
            'parent_id' => $email->id
        ]);

        return redirect()->back()->with('success', 'Correo enviado con éxito');
    }
}
