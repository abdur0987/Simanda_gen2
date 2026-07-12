<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'create agenda',
            'update agenda',
            'delete agenda',
            'export agenda',
            'upload dokumentasi',
            'view dokumentasi',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $protokol = Role::firstOrCreate(['name' => 'Protokol', 'guard_name' => 'web']);
        $humas = Role::firstOrCreate(['name' => 'Humas', 'guard_name' => 'web']);
        $pimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);

        $superAdmin->syncPermissions($permissions);
        $protokol->syncPermissions(['create agenda', 'update agenda', 'delete agenda', 'export agenda']);
        $humas->syncPermissions(['upload dokumentasi', 'view dokumentasi']);
        $pimpinan->syncPermissions(['view dokumentasi']);

        $users = [
            [
                'name' => 'Superadmin',
                'username' => 'superadmin',
                'email' => 'superadmin@email.com',
                'role' => $superAdmin,
            ],
            [
                'name' => 'Protokol',
                'username' => '199809012025051002',
                'email' => '199809012025051002@email.com',
                'role' => $protokol,
            ],
            [
                'name' => 'Humas',
                'username' => '199908102025051005',
                'email' => '199908102025051005@email.com',
                'role' => $humas,
            ],
            [
                'name' => 'Pimpinan',
                'username' => '197610242000031003',
                'email' => '197610242000031003@email.com',
                'role' => $pimpinan,
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData + ['password' => Hash::make('12345')]
            );

            $user->syncRoles([$role]);
        }
    }
}
