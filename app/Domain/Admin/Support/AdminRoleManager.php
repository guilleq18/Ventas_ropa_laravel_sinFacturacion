<?php

namespace App\Domain\Admin\Support;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminRoleManager
{
    public function __construct(
        protected AdminPermissionCatalog $permissionCatalog,
    ) {
    }

    public function permissionOptions()
    {
        return $this->permissionCatalog->options();
    }

    public function upsertRole(?Role $role, array $payload): Role
    {
        $role ??= new Role();

        return DB::transaction(function () use ($role, $payload): Role {
            $role->name = $payload['name'];
            $role->guard_name = 'web';
            $role->save();

            $permissions = Permission::query()
                ->whereIn('id', $payload['permission_ids'] ?? [])
                ->get();

            $role->syncPermissions($permissions);

            return $role->load('permissions');
        });
    }
}
