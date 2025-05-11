<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        if (Schema::hasTable('journals') && ! Schema::hasIndex('journals', 'date_branch_id_index')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->index(['date', 'branch_id'], 'date_branch_id_index');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('journals') && Schema::hasIndex('journals', 'date_branch_id_index')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropIndex('date_branch_id_index');
            });
        }
    }
};
