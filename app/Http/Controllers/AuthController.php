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
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Este correo no se encuentra registrado en el sistema.'
        ], 401);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'La contraseña ingresada es incorrecta.'
        ], 401);
    }

    if (!$user->active_state) {
        return response()->json([
            'message' => 'Tu cuenta se encuentra desactivada. Contacta al administrador.'
        ], 403);
    }

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
            'carrera_id'=> 'required|exists:carreras,id',
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


    public function logout(Request $request) {
    // 1. Obtenemos el token que el usuario está usando actualmente y lo borramos
   if ($request->user()) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada'], 200);
    }
    return response()->json(['message' => 'No se encontró usuario'], 401);
}
}
 
