<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'document_file')) {
                $table->string('document_file')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'document_file_name')) {
                $table->string('document_file_name')->nullable()->after('document_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'document_file')) {
                $table->dropColumn('document_file');
            }
            if (Schema::hasColumn('products', 'document_file_name')) {
                $table->dropColumn('document_file_name');
            }
        });
    }
};
