<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignedNdaToAuditRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->string('signed_nda')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->dropColumn('signed_nda');
        });
    }
}
