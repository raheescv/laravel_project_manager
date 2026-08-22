<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * The reference is now the row's own primary key in readable form
     * (VW-2026-0027), which cannot be known until the insert has happened. The
     * column therefore has to accept NULL for the moment between the insert and
     * the reference being written — the same NULL-means-not-yet idiom the table
     * already uses for active_slot_key.
     */
    public function up(): void
    {
        Schema::table('property_appointments', function (Blueprint $table): void {
            $table->string('reference_no', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('property_appointments', function (Blueprint $table): void {
            $table->string('reference_no', 30)->nullable(false)->change();
        });
    }
};
