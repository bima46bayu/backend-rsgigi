<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->enum('type', ['stock', 'non-stock']);
            $table->integer('min_stock');

            // Smart Alert State
            $table->enum('alert_status', ['normal','warning','critical'])
                ->default('normal');

            $table->timestamp('last_alert_sent_at')->nullable();

            $table->integer('version')->default(1);

            $table->timestamps();

            $table->unique(['location_id','name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};