<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Display the account settings page.
     */
    public function show()
    {
        return view('account.show');
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        $profilePhotoUrl = null;

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            
            // Validate file was uploaded successfully
            if ($file->isValid()) {
                // Delete old photo if exists
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                // Generate unique filename
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Store new photo
                $photoPath = $file->storeAs('profile-photos', $filename, 'public');
                
                if ($photoPath) {
                    $user->profile_photo = $photoPath;
                    $profilePhotoUrl = asset('storage/' . $photoPath);
                    
                    // Log successful upload for debugging
                    \Log::info('Profile photo uploaded successfully', [
                        'user_id' => $user->id,
                        'file_path' => $photoPath,
                        'file_size' => $file->getSize(),
                        'original_name' => $file->getClientOriginalName()
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al guardar la foto de perfil. Inténtelo de nuevo.'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en la subida del archivo. Verifique que el archivo sea válido.'
                ]);
            }
        }

        // Update user data
        $user->name = $request->first_name . ' ' . $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->position = $request->position;
        $user->department = $request->department;
        $user->bio = $request->bio;

        // Save user data
        if ($user->save()) {
            // Log successful profile update
            \Log::info('Profile updated successfully', [
                'user_id' => $user->id,
                'has_photo' => !is_null($user->profile_photo),
                'photo_path' => $user->profile_photo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'profile_photo_url' => $profilePhotoUrl ?: ($user->profile_photo ? asset('storage/' . $user->profile_photo) : null),
                'user_data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'position' => $user->position,
                    'department' => $user->department,
                    'bio' => $user->bio,
                    'profile_photo' => $user->profile_photo
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los datos del perfil. Inténtelo de nuevo.'
            ]);
        }
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta'
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }

    /**
     * Update the user's notification preferences.
     */
    public function updateNotifications(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'system_updates' => 'boolean',
        ]);

        // Update notification preferences
        $user->email_notifications = $request->input('email_notifications', false);
        $user->push_notifications = $request->input('push_notifications', false);
        $user->system_updates = $request->input('system_updates', false);

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Preferencias de notificaciones actualizadas'
        ]);
    }

    /**
     * Update the user's application preferences.
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'language' => 'required|string|in:es,en',
            'timezone' => 'required|string',
            'dark_mode' => 'boolean',
        ]);

        // Update preferences
        $user->language = $request->language;
        $user->timezone = $request->timezone;
        $user->dark_mode = $request->input('dark_mode', false);

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Preferencias actualizadas exitosamente'
        ]);
    }

    /**
     * Remove the user's profile photo.
     */
    public function removeProfilePhoto()
    {
        $user = Auth::user();

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->profile_photo = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil eliminada exitosamente'
        ]);
    }
}
