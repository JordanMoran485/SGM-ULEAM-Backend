<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


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

            'name'=>'required|string|max:30',
            'lastname'=> 'required|string|max:30',
            'email'=> 'required|email|max:255|unique:users,email',
            'carrera_id'=> 'required|exists:carreras,id',
            'password'=> 'required|string|max:30',
            'active_state'=> 'required|boolean',
        ]);
        

        $user = User::create($validateData);
        return response()->json($user,200);
    }

    public function updateProfileImage(Request $request)
{
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $user = $request->user();

    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('profiles', 'public');
        
        $user->profile_photo_path = $path;
        $user->save();

        return response()->json([
            'message' => 'Imagen actualizada',
            'url' => asset('storage/' . $path) 
        ]);
    }
}
}