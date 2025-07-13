<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('audits', function (Blueprint $table) {
            // Jika kolom audit_id belum ada, tambahkan kolom ini
            if (!Schema::hasColumn('audits', 'audit_id')) {
                $table->string('audit_id')->default('default_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('audits', function (Blueprint $table) {
            // Menghapus kolom audit_id jika ada
            $table->dropColumn('audit_id');
        });
    }
};


