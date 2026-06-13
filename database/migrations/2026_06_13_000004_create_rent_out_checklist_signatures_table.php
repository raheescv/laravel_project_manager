<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_checklist_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            $table->string('phase');
            $table->string('role');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['rent_out_id', 'phase', 'role'], 'roc_sig_rent_out_phase_role_uq');
            $table->index(['tenant_id', 'rent_out_id'], 'roc_sig_tenant_ren_tout_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_checklist_signatures');
    }
};
