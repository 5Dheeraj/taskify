<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // ✅ Existing module permissions - untouched
        $modules = ['activity_log'];
        $actions = ['delete', 'manage'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$module}";
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
            }
        }

        // ✅ ✅ NEW: Taskify Config Permissions Seeder (Safe Addition)
        if (config('taskify.permissions')) {
            $dynamicPermissions = config('taskify.permissions');

            foreach ($dynamicPermissions as $module => $actions) {
                foreach ($actions as $permission) {
                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web'
                    ]);
                }
            }
        }

        // Assign permissions to a role (optional, still commented)
        // $adminRole = Role::findByName('admin');
        // $adminRole->syncPermissions(Permission::all());
    }
}
