<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Agreement date and rent details printed on the Exclusive Leasing
     * Brokerage Agreement of a sale (lease) agreement.
     */
    public function up(): void
    {
        Schema::table('rent_outs', function (Blueprint $table): void {
            if (! Schema::hasColumn('rent_outs', 'broker_agreement_date')) {
                $table->date('broker_agreement_date')->nullable()->after('free_month');
            }
            if (! Schema::hasColumn('rent_outs', 'asking_rent')) {
                $table->decimal('asking_rent', 16, 2)->nullable()->after('broker_agreement_date');
            }
            if (! Schema::hasColumn('rent_outs', 'minimum_rent')) {
                $table->decimal('minimum_rent', 16, 2)->nullable()->after('asking_rent');
            }
            if (! Schema::hasColumn('rent_outs', 'available_from')) {
                $table->date('available_from')->nullable()->after('minimum_rent');
            }
            if (! Schema::hasColumn('rent_outs', 'launch_incentive_en')) {
                $table->string('launch_incentive_en')->nullable()->after('available_from');
            }
            if (! Schema::hasColumn('rent_outs', 'launch_incentive_ar')) {
                $table->string('launch_incentive_ar')->nullable()->after('launch_incentive_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rent_outs', function (Blueprint $table): void {
            foreach (['launch_incentive_ar', 'launch_incentive_en', 'available_from', 'minimum_rent', 'asking_rent', 'broker_agreement_date'] as $column) {
                if (Schema::hasColumn('rent_outs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
