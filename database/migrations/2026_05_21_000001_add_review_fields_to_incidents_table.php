<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->enum('review_status', ['Pendiente de revision', 'Aceptada', 'Rechazada'])
                ->default('Pendiente de revision')
                ->after('status');
            $table->text('review_notes')->nullable()->after('review_status');
            $table->timestamp('reviewed_at')->nullable()->after('review_notes');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_status', 'review_notes', 'reviewed_at']);
        });
    }
};
