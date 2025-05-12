<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'rating')) {
                $table->integer('rating')->nullable()->after('address');
            }
            if (! Schema::hasColumn('sales', 'feedback_type')) {
                $table->enum('feedback_type', array_keys(feedbackTypes()))->nullable()->after('rating');
            }
            if (! Schema::hasColumn('sales', 'feedback')) {
                $table->text('feedback')->nullable()->after('feedback_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'rating')) {
                $table->dropColumn('rating');
            }
            if (Schema::hasColumn('sales', 'feedback_type')) {
                $table->dropColumn('feedback_type');
            }
            if (Schema::hasColumn('sales', 'feedback')) {
                $table->dropColumn('feedback');
            }
        });
    }
};
