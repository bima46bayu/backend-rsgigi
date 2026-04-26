<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {

            // hapus foreign key dulu
            $table->dropForeign(['treatment_id']);

            // baru hapus kolom
            $table->dropColumn('treatment_id');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {

            $table->foreignId('treatment_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }
};
