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
    Schema::table('audit_requests', function (Blueprint $table) {
        $table->text('additional_questions')->nullable();
    });
}

public function down()
{
    Schema::table('audit_requests', function (Blueprint $table) {
        $table->dropColumn('additional_questions');
    });
}

};
