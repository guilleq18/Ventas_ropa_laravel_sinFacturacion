<?php

namespace App\Support\Migration;

use InvalidArgumentException;

class LegacyDatasetCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function selected(array $only = []): array
    {
        $datasets = $this->all();

        if ($only === []) {
            return $datasets;
        }

        $wanted = collect($only)
            ->map(fn (mixed $value) => trim((string) $value))
            ->filter()
            ->values();
        $known = collect($datasets)->pluck('key');
        $unknown = $wanted->diff($known);

        if ($unknown->isNotEmpty()) {
            throw new InvalidArgumentException(
                'Datasets desconocidos: '.$unknown->implode(', '),
            );
        }

        return collect($datasets)
            ->filter(fn (array $dataset) => $wanted->contains($dataset['key']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            $this->dataset('sucursales', 'core_sucursal', 'sucursales', ['id', 'nombre', 'direccion', 'telefono', 'activa']),
            $this->dataset('app_settings', 'core_appsetting', 'app_settings', ['id', 'key', 'value_bool', 'value_int', 'value_str', 'description', 'updated_at'], nullable: ['value_bool', 'value_int', 'value_str', 'updated_at'], boolean: ['value_bool']),
            $this->dataset('system_configs', 'admin_panel_sistemaconfig', 'system_configs', ['id', 'permitir_vender_sin_stock', 'permitir_cambiar_precio_venta', 'updated_at'], nullable: ['updated_at'], boolean: ['permitir_vender_sin_stock', 'permitir_cambiar_precio_venta']),
            [
                'key' => 'users',
                'source_table' => 'auth_user',
                'destination_table' => 'users',
                'csv' => 'users.csv',
                'source_columns' => ['id', 'username', 'first_name', 'last_name', 'email', 'password', 'is_active', 'date_joined', 'last_login'],
                'headers' => ['id', 'name', 'username', 'first_name', 'last_name', 'email', 'email_verified_at', 'password', 'is_active', 'created_at', 'updated_at'],
                'unique_by' => ['id'],
                'nullable' => ['email_verified_at', 'created_at', 'updated_at'],
                'boolean' => ['is_active'],
            ],
            $this->dataset('user_profiles', 'admin_panel_usuarioperfil', 'user_profiles', ['id', 'user_id', 'sucursal_id', 'updated_at'], nullable: ['sucursal_id', 'updated_at']),
            $this->dataset('categorias', 'catalogo_categoria', 'categorias', ['id', 'nombre', 'activa'], boolean: ['activa']),
            $this->dataset('productos', 'catalogo_producto', 'productos', ['id', 'nombre', 'descripcion', 'categoria_id', 'activo', 'precio_base', 'costo_base', 'created_at', 'updated_at'], nullable: ['descripcion', 'categoria_id', 'created_at', 'updated_at'], boolean: ['activo']),
            $this->dataset('atributos', 'catalogo_atributo', 'atributos', ['id', 'nombre', 'activo'], boolean: ['activo']),
            $this->dataset('atributo_valores', 'catalogo_atributovalor', 'atributo_valores', ['id', 'atributo_id', 'valor', 'activo'], boolean: ['activo']),
            $this->dataset('variantes', 'catalogo_variante', 'variantes', ['id', 'producto_id', 'sku', 'codigo_barras', 'precio', 'costo', 'activo', 'created_at', 'updated_at'], nullable: ['codigo_barras', 'created_at', 'updated_at'], boolean: ['activo']),
            $this->dataset('variante_atributos', 'catalogo_varianteatributo', 'variante_atributos', ['id', 'variante_id', 'atributo_id', 'valor_id']),
            $this->dataset('stock_sucursal', 'catalogo_stocksucursal', 'stock_sucursal', ['id', 'sucursal_id', 'variante_id', 'cantidad', 'updated_at'], nullable: ['updated_at']),
            $this->dataset('clientes', 'cuentas_corrientes_cliente', 'clientes', ['id', 'dni', 'nombre', 'apellido', 'telefono', 'direccion', 'fecha_nacimiento', 'activo', 'creado_en'], nullable: ['telefono', 'direccion', 'fecha_nacimiento'], boolean: ['activo']),
            $this->dataset('cuentas_corrientes', 'cuentas_corrientes_cuentacorriente', 'cuentas_corrientes', ['id', 'cliente_id', 'activa', 'creada_en'], boolean: ['activa']),
            [
                'key' => 'caja_sesiones',
                'source_table' => 'caja_cajasesion',
                'destination_table' => 'caja_sesiones',
                'csv' => 'caja_sesiones.csv',
                'source_columns' => ['id', 'sucursal_id', 'cajero_apertura_id', 'abierta_en', 'cajero_cierre_id', 'cerrada_en'],
                'headers' => ['id', 'sucursal_id', 'cajero_apertura_id', 'abierta_en', 'cajero_cierre_id', 'cerrada_en', 'abierta_marker'],
                'unique_by' => ['id'],
                'nullable' => ['cajero_cierre_id', 'cerrada_en', 'abierta_marker'],
                'boolean' => [],
            ],
            $this->dataset('plan_cuotas', 'ventas_plancuotas', 'plan_cuotas', ['id', 'tarjeta', 'cuotas', 'recargo_pct', 'activo'], boolean: ['activo']),
            $this->dataset('ventas', 'ventas_venta', 'ventas', ['id', 'sucursal_id', 'numero_sucursal', 'caja_sesion_id', 'cajero_id', 'fecha', 'cliente_id', 'estado', 'medio_pago', 'total', 'empresa_nombre_snapshot', 'empresa_razon_social_snapshot', 'empresa_cuit_snapshot', 'empresa_direccion_snapshot', 'empresa_condicion_fiscal_snapshot', 'fiscal_items_sin_impuestos_nacionales', 'fiscal_items_iva_contenido', 'fiscal_items_otros_impuestos_nacionales_indirectos'], nullable: ['numero_sucursal', 'caja_sesion_id', 'cajero_id', 'cliente_id', 'fiscal_items_sin_impuestos_nacionales', 'fiscal_items_iva_contenido', 'fiscal_items_otros_impuestos_nacionales_indirectos']),
            $this->dataset('venta_items', 'ventas_ventaitem', 'venta_items', ['id', 'venta_id', 'variante_id', 'cantidad', 'precio_unitario', 'iva_alicuota_pct', 'subtotal', 'precio_unitario_sin_impuestos_nacionales', 'precio_unitario_iva_contenido', 'subtotal_sin_impuestos_nacionales', 'subtotal_iva_contenido', 'subtotal_otros_impuestos_nacionales_indirectos'], nullable: ['precio_unitario_sin_impuestos_nacionales', 'precio_unitario_iva_contenido', 'subtotal_sin_impuestos_nacionales', 'subtotal_iva_contenido', 'subtotal_otros_impuestos_nacionales_indirectos']),
            $this->dataset('venta_pagos', 'ventas_ventapago', 'venta_pagos', ['id', 'venta_id', 'tipo', 'monto', 'cuotas', 'coeficiente', 'recargo_pct', 'recargo_monto', 'plan_id', 'referencia', 'pos_proveedor', 'pos_terminal_id', 'pos_lote', 'pos_cupon', 'pos_autorizacion', 'pos_marca', 'pos_ultimos4', 'created_at'], nullable: ['plan_id', 'referencia', 'pos_proveedor', 'pos_terminal_id', 'pos_lote', 'pos_cupon', 'pos_autorizacion', 'pos_marca', 'pos_ultimos4', 'created_at']),
            $this->dataset('movimientos_cuenta_corriente', 'cuentas_corrientes_movimientocuentacorriente', 'movimientos_cuenta_corriente', ['id', 'cuenta_id', 'tipo', 'monto', 'fecha', 'venta_id', 'referencia', 'observacion', 'created_at'], nullable: ['venta_id', 'referencia', 'observacion', 'created_at']),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function normalizeExportRow(string $datasetKey, array $row): array
    {
        return match ($datasetKey) {
            'users' => $this->normalizeUserRow($row),
            'caja_sesiones' => $this->normalizeCajaSesionRow($row),
            default => $row,
        };
    }

    /**
     * @param array<string, mixed> $dataset
     * @param array<string, string|null> $row
     * @return array<string, mixed>
     */
    public function normalizeImportRow(array $dataset, array $row): array
    {
        $nullable = collect($dataset['nullable'] ?? []);
        $boolean = collect($dataset['boolean'] ?? []);

        $normalized = [];

        foreach ($row as $column => $value) {
            $string = $value === null ? '' : trim((string) $value);

            if ($nullable->contains($column) && $string === '') {
                $normalized[$column] = null;
                continue;
            }

            if ($boolean->contains($column)) {
                $normalized[$column] = in_array(strtolower($string), ['1', 'true', 't', 'yes', 'si'], true) ? 1 : 0;
                continue;
            }

            $normalized[$column] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function normalizeUserRow(array $row): array
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));
        $username = trim((string) ($row['username'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $name = trim("{$firstName} {$lastName}");

        if ($name === '') {
            $name = $username !== '' ? $username : "legacy-user-{$row['id']}";
        }

        if ($email === '') {
            $emailLocalPart = $username !== '' ? $username : "legacy-user-{$row['id']}";
            $email = "{$emailLocalPart}@legacy.local";
        }

        return [
            'id' => $row['id'],
            'name' => $name,
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'email_verified_at' => null,
            'password' => $row['password'],
            'is_active' => $row['is_active'],
            'created_at' => $row['date_joined'] ?: null,
            'updated_at' => $row['last_login'] ?: ($row['date_joined'] ?: null),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function normalizeCajaSesionRow(array $row): array
    {
        return [
            'id' => $row['id'],
            'sucursal_id' => $row['sucursal_id'],
            'cajero_apertura_id' => $row['cajero_apertura_id'],
            'abierta_en' => $row['abierta_en'],
            'cajero_cierre_id' => $row['cajero_cierre_id'],
            'cerrada_en' => $row['cerrada_en'],
            'abierta_marker' => $row['cerrada_en'] ? null : 1,
        ];
    }

    /**
     * @param list<string> $headers
     * @param list<string> $nullable
     * @param list<string> $boolean
     * @return array<string, mixed>
     */
    protected function dataset(
        string $key,
        string $sourceTable,
        string $destinationTable,
        array $headers,
        array $nullable = [],
        array $boolean = [],
    ): array {
        return [
            'key' => $key,
            'source_table' => $sourceTable,
            'destination_table' => $destinationTable,
            'csv' => "{$key}.csv",
            'source_columns' => $headers,
            'headers' => $headers,
            'unique_by' => ['id'],
            'nullable' => $nullable,
            'boolean' => $boolean,
        ];
    }
}
