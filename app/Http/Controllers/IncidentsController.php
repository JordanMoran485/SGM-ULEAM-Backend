<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Incidents;

class IncidentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index() {
    $incidents = Incidents::latest()->get()->map(function($item) {
        // Si hay imagen, creamos la URL completa, si no, null
        $item->image_url = $item->image ? asset('storage/' . $item->image) : null;
        return $item;
    });
    return response()->json($incidents);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $validateData = $request->validate([
            'title'=> 'required|string|max:30',
            'description'=> 'required|string',
           
            'location'=> 'required|string|max:30',
            
        ]);
        

        $incident = Incidents::create($validateData);
        return response()->json($incident, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
