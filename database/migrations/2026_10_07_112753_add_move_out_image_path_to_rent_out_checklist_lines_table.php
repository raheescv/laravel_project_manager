<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * A line carried one photo for both phases, so a move-out photo overwrote the
     * move-in evidence it is meant to be compared against. `image_path` stays the
     * move-in photo; this column holds the move-out one.
     */
    public function up(): void
    {
        Schema::table('rent_out_checklist_lines', function (Blueprint $table) {
            $table->string('move_out_image_path')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('rent_out_checklist_lines', function (Blueprint $table) {
            $table->dropColumn('move_out_image_path');
        });
    }
};
