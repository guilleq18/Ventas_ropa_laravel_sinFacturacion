<?php

namespace App\Support\Migration;

use App\Domain\Admin\Support\AdminPermissionCatalog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LegacyAccessSyncService
{
    public function __construct(
        protected AdminPermissionCatalog $permissionCatalog,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function syncFromConnection(string $connection): array
    {
        $this->permissionCatalog->ensurePermissions();

        $groups = DB::connection($connection)
            ->table('auth_group')
            ->orderBy('name')
            ->get(['id', 'name']);
        $groupPermissions = DB::connection($connection)
            ->table('auth_group_permissions as gp')
            ->join('auth_permission as p', 'p.id', '=', 'gp.permission_id')
            ->join('django_content_type as ct', 'ct.id', '=', 'p.content_type_id')
            ->orderBy('gp.group_id')
            ->get([
                'gp.group_id',
                'p.codename',
                'ct.app_label',
                'ct.model',
            ])
            ->groupBy('group_id');
        $userGroups = DB::connection($connection)
            ->table('auth_user_groups')
            ->orderBy('user_id')
            ->get(['user_id', 'group_id'])
            ->groupBy('user_id');
        $directPermissions = DB::connection($connection)
            ->table('auth_user_user_permissions as up')
            ->join('auth_permission as p', 'p.id', '=', 'up.permission_id')
            ->join('django_content_type as ct', 'ct.id', '=', 'p.content_type_id')
            ->orderBy('up.user_id')
            ->get([
                'up.user_id',
                'p.codename',
                'ct.app_label',
                'ct.model',
            ])
            ->groupBy('user_id');

        $rolesByLegacyGroupId = [];
        $rolesSynced = 0;

        foreach ($groups as $group) {
            $permissionNames = collect($groupPermissions->get($group->id, collect()))
                ->flatMap(fn (object $permission) => $this->mapLegacyPermission(
                    (string) $permission->app_label,
                    (string) $permission->model,
                    (string) $permission->codename,
                ))
                ->filter()
                ->unique()
                ->values();

            if ($permissionNames->contains('admin_panel.view_usuarioperfil')) {
                $permissionNames->push('admin_panel.view_reportes');
            }

            $permissionModels = Permission::query()
                ->whereIn('name', $permissionNames->unique()->all())
                ->get();

            $role = Role::query()->firstOrNew([
                'name' => (string) $group->name,
                'guard_name' => 'web',
            ]);
            $role->guard_name = 'web';
            $role->save();
            $role->syncPermissions($permissionModels);

            $rolesByLegacyGroupId[(int) $group->id] = $role;
            $rolesSynced++;
        }

        $usersSynced = 0;

        foreach (User::query()->get(['id']) as $user) {
            $legacyRoles = collect($userGroups->get($user->id, collect()))
                ->map(fn (object $membership) => $rolesByLegacyGroupId[(int) $membership->group_id] ?? null)
                ->filter()
                ->values();
            $user->syncRoles($legacyRoles);

            $directPermissionNames = collect($directPermissions->get($user->id, collect()))
                ->flatMap(fn (object $permission) => $this->mapLegacyPermission(
                    (string) $permission->app_label,
                    (string) $permission->model,
                    (string) $permission->codename,
                ))
                ->filter()
                ->unique()
                ->values();

            if ($directPermissionNames->contains('admin_panel.view_usuarioperfil')) {
                $directPermissionNames->push('admin_panel.view_reportes');
            }

            $user->syncPermissions(
                Permission::query()->whereIn('name', $directPermissionNames->unique()->all())->get(),
            );
            $usersSynced++;
        }

        return [
            'roles' => $rolesSynced,
            'users' => $usersSynced,
            'role_assignments' => DB::table('model_has_roles')->count(),
            'direct_permissions' => DB::table('model_has_permissions')->count(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function mapLegacyPermission(string $appLabel, string $model, string $codename): array
    {
        if ($appLabel === 'ventas' && $codename === 'usar_caja_pos') {
            return ['ventas.usar_caja_pos'];
        }

        if ($appLabel === 'ventas' && str_contains($codename, 'venta')) {
            return ['ventas.view_venta'];
        }

        if ($appLabel === 'caja') {
            return ['ventas.usar_caja_pos'];
        }

        if ($appLabel === 'catalogo') {
            return ['catalogo.manage_catalogo'];
        }

        if ($appLabel === 'cuentas_corrientes') {
            return ['cuentas_corrientes.manage'];
        }

        if ($appLabel === 'core' && in_array($model, ['appsetting', 'sucursal'], true)) {
            return ['core.change_appsetting'];
        }

        if ($appLabel === 'admin_panel' && $model === 'usuarioperfil') {
            return str_starts_with($codename, 'view_')
                ? ['admin_panel.view_usuarioperfil']
                : ['admin_panel.manage_users'];
        }

        if ($appLabel === 'admin_panel' && $model === 'sistemaconfig') {
            return ['core.change_appsetting'];
        }

        return [];
    }
}
