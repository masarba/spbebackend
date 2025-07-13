<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataPseTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('data_pses')->insert([
            [
                'pse_name' => 'Kementerian Dalam Negeri',
                'acronym' => 'Kemendagri',
                'email_pse' => 'pusdatin@kemendagri.go.id',
                'phone_pse' => '(021) 3450038',
                'address' => 'Jl. Medan Merdeka Utara No. 7, Jakarta Pusat 10110',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'pse_name' => 'Kementerian Komunikasi dan Informatika',
                'acronym' => 'Kemenkominfo',
                'email_pse' => 'humas@mail.kominfo.go.id',
                'phone_pse' => '(021) 3452841',
                'address' => 'Jl. Medan Merdeka Barat no. 9, Jakarta 10110',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
