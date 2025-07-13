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
        $table->string('question')->after('audit_id'); // Atur posisi sesuai kebutuhan
    });
}


    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('audits', function (Blueprint $table) {
        $table->dropColumn('question');
    });
}

};
