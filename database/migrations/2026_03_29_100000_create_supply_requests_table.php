<?php

use App\Enums\SupplyRequest\SupplyRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('supply_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->date('date');
            $table->string('order_no')->nullable();
            $table->string('contact_person')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->enum('type', ['Add', 'Return'])->default('Add');
            $table->decimal('total', 16, 2)->default(0);
            $table->decimal('other_charges', 16, 2)->default(0);
            $table->decimal('grand_total', 16, 2)->default(0);
            $table->foreignId('payment_mode_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->enum('status', array_keys(SupplyRequestStatus::values()))->default(SupplyRequestStatus::REQUIREMENT->value);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->unsignedBigInteger('accounted_by')->nullable();
            $table->datetime('accounted_at')->nullable();
            $table->unsignedBigInteger('final_approved_by')->nullable();
            $table->datetime('final_approved_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('set null');
            $table->index(['tenant_id', 'branch_id']);
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_requests');
    }
};
