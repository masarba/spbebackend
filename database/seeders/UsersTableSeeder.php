<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Use the User model
use Spatie\Permission\Models\Role; // Use the Role model for Spatie roles
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create the 'auditor' and 'auditee' roles
        Role::firstOrCreate(['name' => 'auditor']); // Admin as auditor
        Role::firstOrCreate(['name' => 'auditee']); // PSE as auditee

        // Create an auditor (admin)
        $auditor = User::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'verifikasi' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create an auditee (PSE)
        $auditee = User::create([
            'username' => 'pse',
            'email' => 'pse@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'verifikasi' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign roles using Spatie's assignRole method
        $auditor->assignRole('auditor');
        $auditee->assignRole('auditee');
    }
}
