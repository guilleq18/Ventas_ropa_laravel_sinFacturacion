<?php

namespace App\Domain\Admin\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class AdminPermissionCatalog
{
    public const array DEFINITIONS = [
        [
            'name' => 'admin_panel.view_usuarioperfil',
            'label' => 'Acceso al admin panel',
            'group' => 'admin_panel',
            'description' => 'Permite ingresar al panel de administracion.',
        ],
        [
            'name' => 'admin_panel.view_reportes',
            'label' => 'Ver reportes y balances',
            'group' => 'admin_panel',
            'description' => 'Permite ver dashboard, balances y reportes.',
        ],
        [
            'name' => 'admin_panel.manage_users',
            'label' => 'Gestionar usuarios y roles',
            'group' => 'admin_panel',
            'description' => 'Permite crear usuarios, editar roles y administrar accesos.',
        ],
        [
            'name' => 'core.change_appsetting',
            'label' => 'Modificar configuracion',
            'group' => 'core',
            'description' => 'Permite cambiar configuraciones globales y por sucursal.',
        ],
        [
            'name' => 'ventas.usar_caja_pos',
            'label' => 'Usar caja POS',
            'group' => 'ventas',
            'description' => 'Permite operar el POS y confirmar ventas.',
        ],
        [
            'name' => 'ventas.view_venta',
            'label' => 'Ver ventas',
            'group' => 'ventas',
            'description' => 'Permite consultar ventas y su detalle.',
        ],
        [
            'name' => 'catalogo.manage_catalogo',
            'label' => 'Gestionar catalogo',
            'group' => 'catalogo',
            'description' => 'Permite administrar productos, variantes y stock.',
        ],
        [
            'name' => 'cuentas_corrientes.manage',
            'label' => 'Gestionar cuentas corrientes',
            'group' => 'cuentas_corrientes',
            'description' => 'Permite administrar clientes, cuentas y movimientos.',
        ],
    ];

    public function ensurePermissions(): Collection
    {
        return collect(self::DEFINITIONS)
            ->map(function (array $definition): Permission {
                return Permission::findOrCreate($definition['name'], 'web');
            });
    }

    public function options(): Collection
    {
        $this->ensurePermissions();
        $definitionsByName = collect(self::DEFINITIONS)->keyBy('name');

        return Permission::query()
            ->whereIn('name', $definitionsByName->keys())
            ->orderBy('name')
            ->get()
            ->map(function (Permission $permission) use ($definitionsByName): array {
                $meta = $definitionsByName->get($permission->name, []);

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $meta['label'] ?? $permission->name,
                    'group' => $meta['group'] ?? 'general',
                    'description' => $meta['description'] ?? '',
                ];
            });
    }
}
