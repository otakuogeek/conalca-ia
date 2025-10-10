<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\ClientFile;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Log all request data for debugging
            \Log::info('Document upload request data:', [
                'client_id' => $request->input('client_id'),
                'category' => $request->input('category'),
                'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
                'all_inputs' => $request->all()
            ]);

            // Validate the request - note that HTML uses files[] array notation
            $validatedData = $request->validate([
                'client_id' => 'required|exists:clients,id',
                'files' => 'required|array',
                'files.*' => 'required|file|max:10240', // 10MB max per file
                'category' => 'required|in:general,facturas,transito'
            ]);

            // Define the path where files will be uploaded
            $path = public_path('uploads/');
            
            // Create the uploads directory if it doesn't exist
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            // Get the files from the request
            $files = $request->file('files');  // 'files' matches the name in the form
            $customNames = $request->input('custom_names', []);
            $category = $request->input('category', 'general'); // Default to 'general'

            // Check if any files were uploaded
            if (!empty($files)) {
                foreach ($files as $index => $file) {
                    // Get the original name of the file
                    $originalFileName = $file->getClientOriginalName();
                    
                    // Get file size BEFORE moving the file
                    $fileSize = $file->getSize();
                    
                    // Generate a unique file name to avoid conflicts
                    $uniqueFileName = time() . '_' . $index . '_' . $originalFileName;

                    // Move the file to the specified path with unique name
                    $file->move($path, $uniqueFileName);

                    // Get custom name or use original name as fallback
                    $customName = isset($customNames[$index]) && !empty($customNames[$index]) 
                        ? $customNames[$index] 
                        : pathinfo($originalFileName, PATHINFO_FILENAME);

                    // Create a new instance of ClientFile and save the record
                    $clientFile = new ClientFile([
                        'client_id' => $request->client_id,
                        'name' => $customName,
                        'file_path' => $uniqueFileName, // Guardamos también la ruta del archivo físico
                        'category' => $category,
                        'size' => $fileSize // Use the size we got before moving
                    ]);
                    $clientFile->save();
                }
            }

            // Always return JSON response for AJAX requests
            return response()->json([
                'success' => true, 
                'message' => 'Documentos subidos exitosamente',
                'uploaded_files' => count($files ?? [])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in document upload:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error uploading documents: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $query = Client::with('assignedUsers');
        
        // Role-based filtering
        if (auth()->user()->hasRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
            // Super admin and commercial manager see all clients
        } else {
            // Regular users see only assigned clients
            $query->whereHas('assignedUsers', function($q) {
                $q->where('user_id', auth()->id());
            });
        }
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('cliente', 'LIKE', "%{$search}%")
                  ->orWhere('documento', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('telefono', 'LIKE', "%{$search}%")
                  ->orWhere('direccion', 'LIKE', "%{$search}%")
                  ->orWhere('ciudad', 'LIKE', "%{$search}%");
            });
        }
        
        // Pagination
        $clients = $query->paginate(15);
        
        // Handle AJAX requests
        if ($request->ajax()) {
            return view('documents.components.documentsTable', compact('clients'))->render();
        }

        return view('documents.show', compact('clients'));
    }

    public function getClientFiles($clientId)
    {
        try {
            $files = ClientFile::where('client_id', $clientId)->get();
            return response()->json($files);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener archivos'], 500);
        }
    }
    
    /**
     * Get client details for document management
     */
    public function getClientDetails($id)
    {
        try {
            $client = Client::findOrFail($id);
            
            // Log para debugging
            \Log::info('Client details requested', [
                'client_id' => $id,
                'client_data' => $client->toArray()
            ]);
            
            return response()->json($client);
        } catch (\Exception $e) {
            \Log::error('Error getting client details', [
                'client_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }
    }

    public function getClientFilesByCategory($id, $category)
    {
        try {
            $client = Client::findOrFail($id);
            
            // Get files for this client and category
            $files = ClientFile::where('client_id', $id)
                               ->where('category', $category)
                               ->orderBy('created_at', 'desc')
                               ->get();
            
            return response()->json($files);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar archivos'], 500);
        }
    }
}
