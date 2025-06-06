<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    public function up(): void
    {
        DB::table('configurations')->insertOrIgnore([
            'key' => 'theme_settings',
            'value' => json_encode([
                'layout' => 'fluid',
                'transition' => 'out-quart',
                'header' => ['sticky' => false],
                'navigation' => ['sticky' => false, 'profileWidget' => true, 'mode' => 'maxi'],
                'sidebar' => [
                    'disableBackdrop' => false,
                    'staticPosition' => false,
                    'stuck' => false,
                    'unite' => false,
                    'pinned' => false,
                ],
                'color' => [
                    'scheme' => 'gray',
                    'mode' => '',
                    'darkMode' => false,
                ],
                'misc' => [
                    'fontSize' => 16,
                    'bodyScrollbar' => false,
                    'sidebarsScrollbar' => false,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('configurations')->where('key', 'theme_settings')->delete();
    }
};
