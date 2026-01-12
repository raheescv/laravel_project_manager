<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customer_measurements', function (Blueprint $table) {

            if (!Schema::hasColumn('customer_measurements', 'sub_category_id')) {
                $table->unsignedBigInteger('sub_category_id')->nullable()->after('category_id');
            }

            if (!Schema::hasColumn('customer_measurements', 'size')) {
                $table->string('size')->nullable()->after('sub_category_id');
            }

            if (!Schema::hasColumn('customer_measurements', 'width')) {
                $table->string('width')->nullable()->after('size');
            }

        });
    }

    public function down()
    {
        Schema::table('customer_measurements', function (Blueprint $table) {

            if (Schema::hasColumn('customer_measurements', 'width')) {
                $table->dropColumn('width');
            }

            if (Schema::hasColumn('customer_measurements', 'size')) {
                $table->dropColumn('size');
            }

            if (Schema::hasColumn('customer_measurements', 'sub_category_id')) {
                $table->dropColumn('sub_category_id');
            }

        });
    }
};
