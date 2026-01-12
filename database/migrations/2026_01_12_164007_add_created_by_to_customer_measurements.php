<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByToCustomerMeasurements extends Migration
{
    public function up()
    {
        Schema::table('customer_measurements', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_measurements', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('value');
            }
        });
    }

    public function down()
    {
        Schema::table('customer_measurements', function (Blueprint $table) {
            if (Schema::hasColumn('customer_measurements', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
}