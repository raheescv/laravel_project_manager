<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_checkouts', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('currency')->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 24)->nullable()->after('coupon_id');
            $table->decimal('coupon_discount', 16, 2)->default(0)->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_checkouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'coupon_discount']);
        });
    }
};
