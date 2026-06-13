<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('rent_outs', function (Blueprint $table) {
            $table->date('actual_move_in_date')->nullable()->after('vacate_date');
            $table->date('actual_move_out_date')->nullable()->after('actual_move_in_date');
            $table->unsignedBigInteger('facility_coordinator_id')->nullable()->after('actual_move_out_date');
            $table->unsignedBigInteger('leasing_coordinator_id')->nullable()->after('facility_coordinator_id');
            $table->text('move_in_remarks')->nullable()->after('leasing_coordinator_id');
            $table->text('move_out_remarks')->nullable()->after('move_in_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('rent_outs', function (Blueprint $table) {
            $table->dropColumn([
                'actual_move_in_date',
                'actual_move_out_date',
                'facility_coordinator_id',
                'leasing_coordinator_id',
                'move_in_remarks',
                'move_out_remarks',
            ]);
        });
    }
};
