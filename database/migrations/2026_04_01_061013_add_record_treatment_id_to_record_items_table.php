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
        Schema::table('record_items', function (Blueprint $table) {
            $table->foreignId('record_treatment_id')->nullable()->constrained('record_treatments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('record_items', function (Blueprint $table) {
            $table->dropForeign(['record_treatment_id']);
            $table->dropColumn('record_treatment_id');
        });
    }
};
