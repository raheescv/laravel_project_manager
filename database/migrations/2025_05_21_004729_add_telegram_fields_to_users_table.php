<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_telegram_enabled')) {
                $table->boolean('is_telegram_enabled')->default(false)->after('is_whatsapp_enabled');
            }
            if (! Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->string('telegram_chat_id')->nullable()->after('is_telegram_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_telegram_enabled')) {
                $table->dropColumn(['is_telegram_enabled']);
            }
            if (Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->dropColumn(['telegram_chat_id']);
            }
        });
    }
};
