<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dateTime('start_at')->nullable()->after('due_date');
            $table->dateTime('end_at')->nullable()->after('start_at');
            $table->boolean('all_day')->default(true)->after('location');

            $table->index('start_at');
            $table->index(['user_id', 'start_at']);
        });

        $dateTimeExpression = DB::getDriverName() === 'sqlite'
            ? fn (string $time): string => "due_date || ' {$time}'"
            : fn (string $time): string => "CONCAT(due_date, ' {$time}')";

        DB::statement("
            UPDATE tasks
            SET
                start_at = CASE
                    WHEN due_date IS NOT NULL THEN {$dateTimeExpression('00:00:00')}
                    ELSE start_at
                END,
                end_at = CASE
                    WHEN due_date IS NOT NULL THEN {$dateTimeExpression('23:59:59')}
                    ELSE end_at
                END,
                all_day = 1
            WHERE due_date IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'start_at']);
            $table->dropIndex(['start_at']);
            $table->dropColumn(['start_at', 'end_at', 'all_day']);
        });
    }
};
