<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('project_contract_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('project_contract_id')->constrained('project_contracts')->cascadeOnDelete();

            $table->string('document_type', 60)->nullable();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'project_contract_id'], 'pc_documents_contract_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contract_documents');
    }
};
