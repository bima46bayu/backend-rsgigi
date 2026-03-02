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
        Schema::create('records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('treatment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('patient_name')->nullable();

            $table->enum('status', ['draft','completed','cancelled'])
                ->default('draft');

            $table->timestamp('performed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
