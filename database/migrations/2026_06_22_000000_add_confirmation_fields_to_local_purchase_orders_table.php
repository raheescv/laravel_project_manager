<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('local_purchase_orders', function (Blueprint $table) {
            $table->foreignId('confirmation_by')->nullable()->after('decision_note')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmation_at')->nullable()->after('confirmation_by');
            $table->text('confirmation_note')->nullable()->after('confirmation_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmation_by');
            $table->dropColumn(['confirmation_at', 'confirmation_note']);
        });
    }
};
