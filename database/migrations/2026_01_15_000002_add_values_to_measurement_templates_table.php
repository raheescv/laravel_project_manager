<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->text('values')->nullable()->after('multiple');
        });
    }

    public function down()
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->dropColumn('values');
        });
    }
};
