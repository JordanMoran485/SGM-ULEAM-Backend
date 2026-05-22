<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $task = Task::query()
            ->with('user:id,name,lastname')
            ->visibleTo(request()->user())
            ->latest()
            ->get()
            ->map(fn (Task $task): array => $this->serializeTask($task))
            ->values();

        return response()->json($task);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'status' => 'nullable|in:Pendiente,En Proceso,Completada,pending,in_progress,completed',
            'priority' => 'nullable|in:Baja,Media,Alta,Low,Medium,High',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $imageFile = $request->file('image') ?? $request->file('photo');
        $imagePath = $imageFile?->store('incidents', 'public');

        $task = Task::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'location' => $validatedData['location'],
            'status' => $this->normalizeStatus($validatedData['status'] ?? null),
            'priority' => $this->normalizePriority($validatedData['priority'] ?? null),
            'user_id' => $request->user()->getKey(),
            'image' => $imagePath,
            'all_day' => true,
        ]);

        $task->load('user:id,name,lastname');

        return response()->json([
            'message' => 'Incidencia creada correctamente.',
            'data' => $this->serializeTask($task),
        ], 201);
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
        $task = Task::query()
            ->with(['incident.tasks', 'user:id,name,lastname'])
            ->visibleTo($request->user())
            ->findOrFail($id);

        $validatedData = $request->validate([
            'status' => 'required|in:Pendiente,En Proceso,Completada,pending,in_progress,completed',
        ]);

        $task->update([
            'status' => $this->normalizeStatus($validatedData['status']),
        ]);

        $this->syncIncidentStatus($task->incident);

        $task->refresh()->load('user:id,name,lastname');

        return response()->json([
            'message' => 'Tarea actualizada correctamente.',
            'data' => $this->serializeTask($task),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
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

    private function serializeTask(Task $task): array
    {
        $data = $task->toArray();
        $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        $data['image_url'] = $task->image ? "{$baseUrl}/storage/{$task->image}" : null;

        return $data;
    }

    private function syncIncidentStatus(?Incident $incident): void
    {
        if (! $incident) {
            return;
        }

        $incident->loadMissing('tasks');
        $statuses = $incident->tasks->pluck('status');

        if ($statuses->isEmpty()) {
            return;
        }

        if ($statuses->every(fn (string $status): bool => $status === 'Completada')) {
            $incident->update(['status' => 'Completada']);

            return;
        }

        if ($statuses->contains('En Proceso') || $statuses->contains('Completada')) {
            $incident->update(['status' => 'En Proceso']);

            return;
        }

        $incident->update(['status' => 'Pendiente']);
    }
}
