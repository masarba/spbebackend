<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    public function run()
    {
        DB::table('questions')->insert([
            'question' => 'Apa tujuan dari audit ini?',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
