<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('rent_out_payments', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('date');
            $table->date('paid_date')->nullable()->after('due_date');
            $table->date('cheque_date')->nullable()->after('paid_date');
            $table->string('cheque_no')->nullable()->after('cheque_date');
            $table->string('bank_name')->nullable()->after('cheque_no');
            $table->string('reason')->nullable()->after('remark');
            $table->string('model', 50)->nullable()->after('source_id');
            $table->unsignedBigInteger('model_id')->nullable()->after('model');
            $table->unsignedBigInteger('journal_id')->nullable()->after('model_id');
            $table->unsignedBigInteger('journal_entry_id')->nullable()->after('journal_id');

            $table->index(['tenant_id', 'model', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rent_out_payments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'model', 'model_id']);
            $table->dropColumn([
                'due_date', 'paid_date', 'cheque_date', 'cheque_no', 'bank_name',
                'reason', 'model', 'model_id', 'journal_id', 'journal_entry_id',
            ]);
        });
    }
};
