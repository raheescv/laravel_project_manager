<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'whatsapp_mobile')) {
                $table->string('whatsapp_mobile')->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('accounts', 'dob')) {
                $table->date('dob')->nullable()->after('email');
            }
            if (! Schema::hasColumn('accounts', 'id_no')) {
                $table->string('id_no')->nullable()->after('dob');
            }

            if (! Schema::hasColumn('accounts', 'nationality')) {
                $table->string('nationality')->nullable()->after('id_no');
            }
            if (! Schema::hasColumn('accounts', 'company')) {
                $table->string('company')->nullable()->after('nationality');
            }

        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'whatsapp_mobile')) {
                $table->dropColumn('whatsapp_mobile');
            }
            if (Schema::hasColumn('accounts', 'dob')) {
                $table->dropColumn('dob');
            }
            if (Schema::hasColumn('accounts', 'id_no')) {
                $table->dropColumn('id_no');
            }
            if (Schema::hasColumn('accounts', 'nationality')) {
                $table->dropColumn('nationality');
            }
            if (Schema::hasColumn('accounts', 'company')) {
                $table->dropColumn('company');
            }
        });
    }
};
