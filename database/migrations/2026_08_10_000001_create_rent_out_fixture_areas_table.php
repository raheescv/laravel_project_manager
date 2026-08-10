<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rent_out_fixture_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('rent_out_id')->constrained('rent_outs')->cascadeOnDelete();
            // The checklist category this block belongs to. Stored as text rather than a
            // foreign key because an area can be added by hand for a room that has no
            // inventory items of its own (see ChecklistTab::addFixtureArea).
            $table->string('category');
            $table->unsignedInteger('sort_order')->default(0);
            // Owner acceptance is per area — one signature covers that area's entries.
            $table->string('owner_name')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('owner_signature_path')->nullable();
            $table->timestamp('owner_signed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rent_out_id']);
            // One block per area per rent-out.
            $table->unique(['rent_out_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_out_fixture_areas');
    }
};
