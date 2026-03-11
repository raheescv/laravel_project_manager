<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_buildings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('property_group_id')->constrained('property_groups')->cascadeOnDelete();
            $table->string('name');
            $table->string('arabic_name')->nullable();
            $table->date('created_date')->nullable();
            $table->string('reference_code')->nullable();
            $table->string('building_no')->nullable();
            $table->string('location')->nullable();
            $table->integer('floors')->nullable();
            $table->decimal('investment', 16, 2)->nullable();
            $table->string('electricity')->nullable();
            $table->string('road')->nullable();
            $table->string('landmark')->nullable();
            $table->decimal('amount', 16, 2)->nullable();
            $table->string('ownership')->default('own'); // own, lease, rent
            $table->string('status')->default('active'); // active, inactive
            $table->unsignedBigInteger('account_id')->nullable(); // FK to accounts table (owner)
            $table->text('remark')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'property_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_buildings');
    }
};
