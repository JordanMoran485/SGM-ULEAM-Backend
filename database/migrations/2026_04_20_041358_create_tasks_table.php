<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();
        $table->enum('status', ['Pendiente', 'En Proceso', 'Completada'])->default('Pendiente');
        $table->enum('priority', ['Baja', 'Media', 'Alta'])->default('Media');
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('location')->nullable();
        $table->date('due_date')->nullable();
        $table->dateTime('start_at')->nullable();
        $table->dateTime('end_at')->nullable();
        $table->boolean('all_day')->default(true);
        $table->timestamps();
        $table->index('start_at');
        $table->index(['user_id', 'start_at']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
