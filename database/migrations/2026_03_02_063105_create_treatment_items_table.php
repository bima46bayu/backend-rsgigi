<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('treatment_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('treatment_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('item_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->integer('quantity');

            $table->timestamps();

            $table->unique(['treatment_id','item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_items');
    }
};
