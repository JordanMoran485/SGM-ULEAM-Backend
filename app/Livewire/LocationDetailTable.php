<?php

namespace App\Livewire;

use App\Models\Facultad;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LocationDetailTable extends Component
{
    public ?int $facultadId = null;
    public ?int $conserjeId = null;

    public function resetFilters(): void
    {
        $this->facultadId = null;
        $this->conserjeId = null;
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
        $conserjes  = User::queryConserjes($user)->orderBy('name')->get();

        return view('livewire.location-detail-table', compact('locations', 'facultades', 'conserjes'));
    }
}
