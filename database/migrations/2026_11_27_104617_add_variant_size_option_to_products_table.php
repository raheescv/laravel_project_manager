<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a variant family calls its second layer — the one stored in `size`.
 * NULL is "Size"; a drink pack names it "Flavour", a coffee "Weight". The
 * first layer's name lives in `variant_option`.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'variant_size_option')) {
                $table->string('variant_size_option', 40)->nullable()->after('variant_option');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'variant_size_option')) {
                $table->dropColumn('variant_size_option');
            }
        });
    }
};
