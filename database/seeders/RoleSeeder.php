<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // pulisci la cache dei permessi/ruoli
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // crea (o recupera) i ruoli per la guard web
        foreach (['super admin', 'admin', 'staff', 'customer'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
