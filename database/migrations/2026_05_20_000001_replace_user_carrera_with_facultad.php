<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'facultad_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('facultad_id')->nullable()->after('lastname')->constrained('facultades')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('users', 'carrera_id')) {
            DB::statement('
                UPDATE users
                SET facultad_id = (
                    SELECT carreras.facultad_id
                    FROM carreras
                    WHERE carreras.id = users.carrera_id
                )
                WHERE carrera_id IS NOT NULL
                  AND facultad_id IS NULL
            ');

            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('carrera_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'carrera_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('carrera_id')->nullable()->after('lastname')->constrained('carreras')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('users', 'facultad_id')) {
            DB::statement('
                UPDATE users
                SET carrera_id = (
                    SELECT carreras.id
                    FROM carreras
                    WHERE carreras.facultad_id = users.facultad_id
                    ORDER BY carreras.id
                    LIMIT 1
                )
                WHERE facultad_id IS NOT NULL
                  AND carrera_id IS NULL
            ');

            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('facultad_id');
            });
        }
    }
};
