<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPdfPathToAuditRequestsTable extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('signed_nda'); // Menambahkan kolom pdf_path
        });
    }

    /**
     * Batalkan migrasi.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->dropColumn('pdf_path'); // Menghapus kolom pdf_path
        });
    }
}
