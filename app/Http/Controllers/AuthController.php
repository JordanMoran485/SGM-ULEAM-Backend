<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;



class AuthController extends Controller
{

    public function login(Request $request) {
    // 1. Validación de formato (usando tus mensajes de lang/es)
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    // 2. Verificar si el usuario existe por correo
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        // Retornamos 404 o 401 si el correo no está registrado
        return response()->json([
            'message' => 'Este correo no se encuentra registrado en el sistema.'
        ], 401);
    }

    // 3. Verificar si la contraseña es correcta
    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'La contraseña ingresada es incorrecta.'
        ], 401);
    }

    // 4. (Opcional) Verificar si el usuario está activo
    if (!$user->active_state) {
        return response()->json([
            'message' => 'Tu cuenta se encuentra desactivada. Contacta al administrador.'
        ], 403);
    }

    // 5. Todo bien: Generar Token
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Bienvenido, ' . $user->name,
        'user' => $user,
        'token' => $token
    ], 200);
}


    public function register(Request $request){

        
          $validateData = $request->validate([
            'name'=>'required|string|max:200',
            'lastname'=> 'required|string|max:200',
            'email'=> 'required|email|max:255|unique:users,email',
            'facultad'=> 'required|string|max:30',
            'carrera'=> 'required|string|max:30',
            'password'=> 'required|string|max:15',
            'active_state' => 'nullable|boolean',
        ]);

      

        $validateData['password']= Hash::make(value: $request->password);

        $user = User::create($validateData);

        $token = $user->createToken('myapptoken')->plainTextToken;

        return response([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user,
            'token' => $token
        ], 201);
    }
}
 
