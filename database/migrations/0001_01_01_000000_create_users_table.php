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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->nullable();  // Role column (e.g., admin, user, etc.)
            $table->string('status')->nullable();  // User status (e.g., active, inactive)
            $table->string('verifikasi')->nullable();  // Verification status

            // 2FA columns
            $table->string('google2fa_secret')->nullable(); // Column to store 2FA secret
            $table->boolean('is_2fa_enabled')->default(false); // Flag to indicate if 2FA is enabled

            // Adding auditor_id and auditee_id to users table
            $table->unsignedBigInteger('auditor_id')->nullable();  // Kolom untuk menyimpan ID Auditor
            $table->unsignedBigInteger('auditee_id')->nullable();  // Kolom untuk menyimpan ID Auditee

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
