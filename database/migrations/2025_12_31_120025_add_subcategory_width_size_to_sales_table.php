<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_category_id')->nullable()->after('category_id');
            $table->string('width')->nullable()->after('sub_category_id');
            $table->string('size')->nullable()->after('width');

            // Optional: if you have a sub_categories table, add a foreign key
            // $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['sub_category_id', 'width', 'size']);
            // If you added foreign key, drop it first
            // $table->dropForeign(['sub_category_id']);
        });
    }
};
