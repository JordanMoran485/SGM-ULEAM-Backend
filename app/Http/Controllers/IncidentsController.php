<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class IncidentsController extends Controller
{
    public function index(Request $request)
    {
        $incidents = Incident::query()
            ->with([
                'user:id,name,lastname',
                'tasks.user:id,name,lastname',
                'reviewer:id,name,lastname',
            ])
            ->visibleTo($request->user())
            ->latest()
            ->get()
            ->map(fn (Incident $incident): array => $this->serializeIncident($incident))
            ->values();

        return response()->json($incidents);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'status' => 'nullable|in:Pendiente,En Proceso,Completada,pending,in_progress,completed',
            'priority' => 'nullable|in:Baja,Media,Alta,Low,Medium,High',
            'image'    => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'photo'    => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $imageFile = $request->file('image') ?? $request->file('photo');
        $imagePath = $imageFile?->store('incidents', 'public');

        $incident = Incident::create([
            'title'       => $validatedData['title'],
            'description' => $validatedData['description'],
            'location'    => $validatedData['location'],
            'status'      => $this->normalizeStatus($validatedData['status'] ?? null),
            'priority'    => $this->normalizePriority($validatedData['priority'] ?? null),
            'user_id'     => $request->user()->getKey(),
            'image'       => $imagePath,
        ]);

        $incident->load([
            'user:id,name,lastname',
            'tasks.user:id,name,lastname',
            'reviewer:id,name,lastname',
        ]);

        return response()->json([
            'message' => 'Incidencia creada correctamente.',
            'data' => $this->serializeIncident($incident),
        ], 201);
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'En Proceso', 'in_progress' => 'En Proceso',
            'Completada', 'completed' => 'Completada',
            default => 'Pendiente',
        };
    }

    private function normalizePriority(?string $priority): string
    {
        return match ($priority) {
            'Alta', 'High' => 'Alta',
            'Baja', 'Low' => 'Baja',
            default => 'Media',
        };
    }

    private function serializeIncident(Incident $incident): array
    {
        $data = $incident->toArray();
        $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        $data['image_url'] = $incident->image ? "{$baseUrl}/storage/{$incident->image}" : null;

        return $data;
    }
}
