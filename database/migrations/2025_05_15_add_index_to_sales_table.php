<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sales') && ! Schema::hasIndex('sales', 'sale_date_branch_id_status_index')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->index(['date', 'branch_id', 'status'], 'sale_date_branch_id_status_index');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('sales') && Schema::hasIndex('sales', 'sale_date_branch_id_status_index')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropIndex('sale_date_branch_id_status_index');
            });
        }
    }
};
