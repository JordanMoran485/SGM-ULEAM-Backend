<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     */

    public function index()
    {
        //
        $users = User::all();
        return response()->json($users, 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validateData = $request->validate([

            'name'=>'required|string|max:15',
            'lastname'=> 'required|string|max:15',
            'email'=> [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'regex:/^[A-Za-z0-9._%+-]+@live\.uleam\.edu\.ec$/i',
            ],
            'carrera_id'=> 'required|exists:carreras,id',
            'password'=> 'required|string|max:15',
            'active_state'=> 'required|boolean',
        ]);
        

        $user = User::create($validateData);
        return response()->json($user,200);
    }

    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $user = $request->user();

        if (! $request->hasFile('image')) {
            return response()->json([
                'message' => 'No se recibió ninguna imagen.',
            ], 422);
        }

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('image')->store('profiles', 'public');

        $user->profile_photo_path = $path;
        $user->save();
        $user->refresh()->load(['carrera.facultad', 'roles']);

        return response()->json([
            'message' => 'Imagen actualizada',
            'user' => $user,
            'url' => $user->profile_photo_url,
        ]);
    }
}
