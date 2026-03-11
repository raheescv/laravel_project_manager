<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->foreignId('property_group_id')->constrained('property_groups')->cascadeOnDelete();
            $table->foreignId('property_building_id')->constrained('property_buildings')->cascadeOnDelete();
            $table->foreignId('property_type_id')->nullable()->constrained('property_types')->nullOnDelete();
            $table->string('name');
            $table->string('number')->nullable();
            $table->string('code')->nullable();
            $table->string('unit_no')->nullable();
            $table->string('floor')->nullable();
            $table->integer('rooms')->nullable();
            $table->integer('kitchen')->nullable();
            $table->integer('toilet')->nullable();
            $table->integer('hall')->nullable();
            $table->decimal('size', 10, 2)->nullable();
            $table->decimal('rent', 16, 2)->default(0);
            $table->string('ownership')->nullable();
            $table->string('electricity')->nullable();
            $table->string('kahramaa')->nullable();
            $table->string('parking')->nullable();
            $table->string('furniture')->nullable();
            $table->string('status')->default('vacant'); // vacant, occupied, booked, sold
            $table->string('availability_status')->default('available'); // available, sold
            $table->string('flag')->default('active'); // active, disabled
            $table->text('remark')->nullable();
            $table->string('floor_plan')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'property_building_id']);
            $table->index(['tenant_id', 'property_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
