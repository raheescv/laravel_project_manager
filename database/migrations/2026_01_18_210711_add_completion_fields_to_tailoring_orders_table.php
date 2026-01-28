<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('tailoring_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('rack_id')->nullable()->after('notes');
            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('set null');
            $table->unsignedBigInteger('cutter_id')->nullable()->after('rack_id');
            $table->foreign('cutter_id')->references('id')->on('users')->onDelete('set null');
            $table->date('completion_date')->nullable()->after('cutter_id');
            $table->enum('completion_status', ['pending', 'in_progress', 'completed', 'delivered'])->nullable()->after('completion_date');
        });
    }

    public function down(): void
    {
        Schema::table('tailoring_orders', function (Blueprint $table) {
            $table->dropForeign(['rack_id']);
            $table->dropForeign(['cutter_id']);
            $table->dropColumn(['rack_id', 'cutter_id', 'completion_date', 'completion_status']);
        });
    }
};
