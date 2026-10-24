<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * `pin` moves from a bcrypt hash (plus the `pin_lookup` HMAC that made login fast)
 * to the PIN encrypted under the app key — one column that login can match by
 * decrypting and the employee form can show (see User::pin()).
 *
 * A bcrypt hash can't be decrypted, but a `pin_lookup` digest can be matched: PINs
 * are at most 6 digits, so every candidate is HMAC'd under the same app key and
 * each hit is re-stored encrypted. Rows without a digest (or digests written under
 * an older APP_KEY) keep their hash and are converted on their next sign-in.
 */
return new class() extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'pin_lookup')) {
            $this->encryptRecoverablePins();

            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_tenant_pin_lookup_index');
                $table->dropColumn('pin_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_lookup', 64)->nullable()->after('pin');
            $table->index(['tenant_id', 'pin_lookup'], 'users_tenant_pin_lookup_index');
        });

        DB::table('users')->whereNotNull('pin')->orderBy('id')->each(function (object $user): void {
            if (filled(password_get_info($user->pin)['algo'])) {
                return;
            }
            try {
                $pin = Crypt::decryptString($user->pin);
            } catch (DecryptException) {
                return;
            }
            DB::table('users')->where('id', $user->id)->update([
                'pin' => Hash::make($pin),
                'pin_lookup' => hash_hmac('sha256', $pin, (string) config('app.key')),
            ]);
        });
    }

    private function encryptRecoverablePins(): void
    {
        $userIdsByLookup = DB::table('users')
            ->whereNotNull('pin')
            ->whereNotNull('pin_lookup')
            ->get(['id', 'pin_lookup'])
            ->groupBy('pin_lookup')
            ->map(fn ($users) => $users->pluck('id')->all())
            ->all();

        $key = (string) config('app.key');
        for ($length = 1; $length <= 6 && $userIdsByLookup; $length++) {
            for ($number = 0; $number < 10 ** $length && $userIdsByLookup; $number++) {
                $pin = str_pad((string) $number, $length, '0', STR_PAD_LEFT);
                $lookup = hash_hmac('sha256', $pin, $key);
                if (! isset($userIdsByLookup[$lookup])) {
                    continue;
                }
                foreach ($userIdsByLookup[$lookup] as $userId) {
                    DB::table('users')->where('id', $userId)->update(['pin' => Crypt::encryptString($pin)]);
                }
                unset($userIdsByLookup[$lookup]);
            }
        }
    }
};
