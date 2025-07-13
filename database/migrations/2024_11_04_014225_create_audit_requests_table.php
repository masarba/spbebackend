<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // database/migrations/xxxx_xx_xx_create_audit_requests_table.php
Schema::create('audit_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('auditor_id')->nullable()->constrained('users'); // Untuk menyimpan auditor yang akan menangani audit ini
    $table->foreignId('auditee_id')->constrained('users'); // Auditee yang mengajukan audit
    $table->enum('status', ['pending', 'approved', 'rejected', 'answered'])->default('pending'); // Status audit
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_requests');
    }
};
