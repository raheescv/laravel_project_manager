<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('product_offer_id')->nullable()->after('product_id');
            $table->foreign('product_offer_id')->references('id')->on('product_offers')->onDelete('cascade');
            $table->index('product_offer_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropForeign(['product_offer_id']);
            $table->dropIndex(['product_offer_id']);
            $table->dropColumn('product_offer_id');
        });
    }
};
