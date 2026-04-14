<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('rent_out_services', function (Blueprint $table) {
            if (! Schema::hasColumn('rent_out_services', 'start_date')) {
                $table->date('start_date')->nullable()->after('description');
            }
            if (! Schema::hasColumn('rent_out_services', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn('rent_out_services', 'no_of_days')) {
                $table->unsignedInteger('no_of_days')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('rent_out_services', 'no_of_months')) {
                $table->unsignedInteger('no_of_months')->nullable()->after('no_of_days');
            }
            if (! Schema::hasColumn('rent_out_services', 'unit_size')) {
                $table->decimal('unit_size', 12, 2)->nullable()->after('no_of_months');
            }
            if (! Schema::hasColumn('rent_out_services', 'per_square_meter_price')) {
                $table->decimal('per_square_meter_price', 12, 2)->nullable()->after('unit_size');
            }
            if (! Schema::hasColumn('rent_out_services', 'per_day_price')) {
                $table->decimal('per_day_price', 12, 2)->nullable()->after('per_square_meter_price');
            }
            if (! Schema::hasColumn('rent_out_services', 'reason')) {
                $table->string('reason')->nullable()->after('per_day_price');
            }
            if (! Schema::hasColumn('rent_out_services', 'remark')) {
                $table->string('remark')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rent_out_services', function (Blueprint $table) {
            foreach ([
                'start_date',
                'end_date',
                'no_of_days',
                'no_of_months',
                'unit_size',
                'per_square_meter_price',
                'per_day_price',
                'reason',
                'remark',
            ] as $column) {
                if (Schema::hasColumn('rent_out_services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
