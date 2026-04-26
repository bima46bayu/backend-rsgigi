<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_stocks', function (Blueprint $table) {

            $table->unsignedBigInteger('location_id')
                ->nullable()
                ->after('item_id');

        });

        // isi default location untuk data lama
        DB::table('item_stocks')->update([
            'location_id' => 1
        ]);

        Schema::table('item_stocks', function (Blueprint $table) {

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->cascadeOnDelete();

            $table->index(['item_id','location_id']);
        });
    }

    public function down(): void
    {
        Schema::table('item_stocks', function (Blueprint $table) {

            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');

        });
    }
};