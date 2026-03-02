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
        Schema::create('item_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('item_stock_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type', ['in','out']);
            $table->integer('quantity');

            $table->string('reference_type')->nullable(); 
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->timestamps();

            $table->index(['item_id','created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_transactions');
    }
};
