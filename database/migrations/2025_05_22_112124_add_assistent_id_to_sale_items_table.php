<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'assistant_id')) {
                $table->unsignedBigInteger('assistant_id')->references('id')->on('users')->nullable()->after('employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'assistant_id')) {
                $table->dropForeign(['assistant_id']);
                $table->dropColumn('assistant_id');
            }
        });
    }
};
