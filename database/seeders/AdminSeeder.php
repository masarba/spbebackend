<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@sisakambe.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
            'verifikasi' => 'verified'
        ]);

        $admin->assignRole('admin');
    }
} 