<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile PIN login had no way to find a user other than bcrypt-checking the PIN
 * against every active user on the tenant, one at a time. At BCRYPT_ROUNDS=12
 * that is ~266ms per user, so a tenant with 149 PIN users spent ~40 seconds on
 * every sign-in.
 *
 * `pin_lookup` is a deterministic keyed digest of the PIN (HMAC-SHA256 under the
 * app key — see User::pinLookup), so the candidate can be found with one indexed
 * query and verified with a single bcrypt check. It is a lookup accelerator, not
 * a credential: the bcrypt hash in `pin` is still what authenticates.
 *
 * Existing rows stay NULL — a bcrypt hash is one-way, so the digest cannot be
 * backfilled. LoginAction migrates each user the first time they sign in.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_lookup', 64)->nullable()->after('pin');
            $table->index(['tenant_id', 'pin_lookup'], 'users_tenant_pin_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_pin_lookup_index');
            $table->dropColumn('pin_lookup');
        });
    }
};
