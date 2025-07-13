<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        // Create permissions
        $permissions = [
            'manage-users',
            'manage-audits',
            'manage-settings',
            'view-dashboard',
            'create-audit',
            'view-audit',
            'edit-audit',
            'delete-audit'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign all permissions to admin role
        $adminRole->givePermissionTo(Permission::all());
        
        // Assign basic permissions to user role
        $userRole->givePermissionTo([
            'create-audit',
            'view-audit',
            'edit-audit'
        ]);
    }
} 