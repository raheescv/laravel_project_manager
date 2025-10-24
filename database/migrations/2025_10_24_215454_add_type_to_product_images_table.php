<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'method')) {
                $table->enum('method', ['normal', 'angle'])->default('normal')->after('product_id');
            }
            if (! Schema::hasColumn('product_images', 'degree')) {
                $table->integer('degree')->nullable()->after('method');
            }
            if (! Schema::hasColumn('product_images', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('degree');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'method')) {
                $table->dropColumn('method');
            }
            if (Schema::hasColumn('product_images', 'degree')) {
                $table->dropColumn('degree');
            }
            if (Schema::hasColumn('product_images', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
