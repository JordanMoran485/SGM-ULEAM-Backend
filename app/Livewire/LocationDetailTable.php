<?php

namespace App\Livewire;

use App\Models\Facultad;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LocationDetailTable extends Component
{
    public $facultadId   = null;
    public $tipoConserje = null;
    public $conserjeId   = null;

    public function updatedFacultadId(): void
    {
        $this->conserjeId = null;
    }

    public function updatedTipoConserje(): void
    {
        $this->conserjeId = null;
    }

    public function resetFilters(): void
    {
        $this->facultadId   = null;
        $this->tipoConserje = null;
        $this->conserjeId   = null;
    }

    public function render()
    {
        $user = auth()->user();

        $locations = Task::query()
            ->visibleTo($user)
            ->when($this->facultadId, fn ($q) => $q->whereHas(
                'user', fn ($uq) => $uq->where('facultad_id', $this->facultadId)
            ))
            ->when($this->conserjeId, fn ($q) => $q->where('user_id', $this->conserjeId))
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->select(
                'location',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT due_date) as cleaning_days'),
                DB::raw('MAX(due_date) as last_cleaning_date')
            )
            ->groupBy('location')
            ->orderByDesc('total')
            ->orderBy('location')
            ->get();

        $facultades = Facultad::orderBy('name')->get();

        $tipoOptions = $user->isSupervisor()
            ? ['uleam' => 'Uleam']
            : User::conserjeTypeOptions();

        // Si hay facultad seleccionada, usar el tipo elegido o Uleam por defecto
        // (los EP que aparecen en la facultad se excluyen así)
        $conserjes = $this->facultadId
            ? User::queryConserjes($user, $this->tipoConserje ?? 'uleam')
                ->where('facultad_id', $this->facultadId)
                ->orderBy('name')
                ->get()
            : collect();

        return view('livewire.location-detail-table', compact(
            'locations', 'facultades', 'tipoOptions', 'conserjes'
        ));
    }
}
