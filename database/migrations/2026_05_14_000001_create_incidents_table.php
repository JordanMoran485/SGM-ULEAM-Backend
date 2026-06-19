<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['Pendiente', 'En Proceso', 'Completada'])->default('Pendiente');
            $table->enum('review_status', ['Pendiente de revision', 'Aceptada', 'Rechazada'])->default('Pendiente de revision');
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('priority', ['Baja', 'Media', 'Alta'])->default('Media');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('location');
            $table->string('image')->nullable();
            $table->string('image2')->nullable();
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('incident_id')->nullable()->after('id')->constrained('incidents')->nullOnDelete();
            $table->index('incident_id');
        });

        if (! Schema::hasTable('tasks')) {
            return;
        }

        $legacyIncidentsQuery = DB::table('tasks')
            ->join('users', 'users.id', '=', 'tasks.user_id')
            ->leftJoin('model_has_roles', function ($join): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereNull('tasks.start_at')
            ->where('roles.name', 'conserje')
            ->select([
                'tasks.id as task_id',
                'tasks.title',
                'tasks.description',
                'tasks.status',
                'tasks.priority',
                'tasks.user_id',
                'tasks.location',
                'tasks.created_at',
                'tasks.updated_at',
            ])
            ->when(
                Schema::hasColumn('tasks', 'image'),
                fn ($query) => $query->addSelect('tasks.image'),
                fn ($query) => $query->addSelect(DB::raw("NULL as image"))
            );

        $legacyIncidents = $legacyIncidentsQuery->get();

        foreach ($legacyIncidents as $legacyIncident) {
            $incidentId = DB::table('incidents')->insertGetId([
                'title' => $legacyIncident->title,
                'description' => $legacyIncident->description ?: '',
                'status' => $legacyIncident->status ?: 'Pendiente',
                'priority' => $legacyIncident->priority ?: 'Media',
                'user_id' => $legacyIncident->user_id,
                'location' => $legacyIncident->location ?: 'Sin ubicacion',
                'image' => $legacyIncident->image,
                'created_at' => $legacyIncident->created_at,
                'updated_at' => $legacyIncident->updated_at,
            ]);

            DB::table('tasks')
                ->where('id', $legacyIncident->task_id)
                ->update(['incident_id' => $incidentId]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('incident_id');
        });

        Schema::dropIfExists('incidents');
    }
};
