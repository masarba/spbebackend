<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyAnswerColumnInAuditRequestsAndQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Menghapus kolom 'answer' dari tabel 'audit_requests'
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->dropColumn('answer');
        });

        // Menambahkan kolom 'answer' ke tabel 'questions'
        Schema::table('questions', function (Blueprint $table) {
            $table->integer('answer')->nullable();  // Tipe data sesuai kebutuhan Anda
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Mengembalikan kolom 'answer' ke tabel 'audit_requests'
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->integer('answer')->nullable();  // Tipe data sesuai kebutuhan Anda
        });

        // Menghapus kolom 'answer' dari tabel 'questions'
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('answer');
        });
    }
}
