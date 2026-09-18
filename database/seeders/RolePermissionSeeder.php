<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed a starting permission set, an Admin role with all of them, a
     * placeholder Reviewer role with none yet, and assign Admin to the
     * seeded test user so migrate:fresh --seed yields a working login.
     */
    public function run(): void
    {
        $permissions = collect([
            'manage opportunities',
            'view opportunities',
            'manage users',
        ])->map(fn (string $name) => Permission::firstOrCreate(['name' => $name]));

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions($permissions);

        Role::firstOrCreate(['name' => 'Reviewer']);

        User::where('email', 'test@example.com')->first()?->assignRole($admin);
    }
}
