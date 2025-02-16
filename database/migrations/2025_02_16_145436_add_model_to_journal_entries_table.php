<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_entries', 'model')) {
                $table->string('model', 50)->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('journal_entries', 'model_id')) {
                $table->unsignedBigInteger('model_id')->nullable()->after('model');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'model')) {
                $table->dropColumn('model');
            }
            if (Schema::hasColumn('journal_entries', 'model_id')) {
                $table->dropColumn('model_id');
            }
        });
    }
};
