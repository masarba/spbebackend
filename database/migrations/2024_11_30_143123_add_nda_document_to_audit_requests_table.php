<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNdaDocumentToAuditRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->string('nda_document')->nullable()->after('status'); // Untuk menyimpan file NDA yang diajukan
        });
    }

    public function down()
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->dropColumn('nda_document');
        });
    }
}
