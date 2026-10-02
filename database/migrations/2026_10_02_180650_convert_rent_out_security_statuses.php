<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Security deposits move to the old system's statuses. Every mapping keeps the
 * deposit's ledger posting unchanged (pending posted nothing, like submitted;
 * collected/adjusted posted the receipt only, like paid).
 */
return new class() extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $upMap = [
        'pending' => 'submitted',
        'collected' => 'paid',
        'adjusted' => 'paid',
    ];

    /**
     * @var array<string, string>
     */
    private array $downMap = [
        'submitted' => 'pending',
        'overdue' => 'pending',
        'deposited' => 'collected',
        'paid' => 'collected',
        'paid_released' => 'returned',
    ];

    public function up(): void
    {
        foreach ($this->upMap as $from => $to) {
            DB::table('rent_out_securities')->where('status', $from)->update(['status' => $to]);
        }

        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->string('status')->default('submitted')->change();
        });
    }

    public function down(): void
    {
        foreach ($this->downMap as $from => $to) {
            DB::table('rent_out_securities')->where('status', $from)->update(['status' => $to]);
        }

        Schema::table('rent_out_securities', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};
