<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Permission as ModelsPermission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::create(['name' => 'admin']);

        $permissions = [
            ['name' => 'user list'],
            ['name' => 'create user'],
            ['name' => 'edit user'],
            ['name' => 'delete user']
        ];

        foreach($permissions as $item) {
            Permission::create($item);
        }

        $role->syncPermissions(Permission::all());

        $user = User::first();
        $user->assignRole($role);
    }
}
