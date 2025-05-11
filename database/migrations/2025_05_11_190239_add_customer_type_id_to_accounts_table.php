<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'customer_type_id')) {
                $table->unsignedBigInteger('customer_type_id')->nullable()->references('id')->on('customer_types')->after('account_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'customer_type_id')) {
                $table->dropColumn('customer_type_id');
            }
        });
    }
};
