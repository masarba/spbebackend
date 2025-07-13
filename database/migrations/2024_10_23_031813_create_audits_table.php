<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditsTable extends Migration
{
    public function up()
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->nullable(); // Optional: if you want to categorize audits
            $table->float('score')->nullable();
            $table->string('status')->default('pending'); // Status can be 'pending', 'completed', etc.
            $table->string('file')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn('file');
        });

    }
}

