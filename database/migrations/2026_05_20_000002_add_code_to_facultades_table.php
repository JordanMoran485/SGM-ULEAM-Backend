<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facultades', function (Blueprint $table): void {
            $table->string('code')->nullable()->after('id');
        });

        $facultades = DB::table('facultades')->select('id')->orderBy('id')->get();

        foreach ($facultades as $index => $facultad) {
            DB::table('facultades')
                ->where('id', $facultad->id)
                ->update(['code' => 'F-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)]);
        }

        Schema::table('facultades', function (Blueprint $table): void {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('facultades', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
