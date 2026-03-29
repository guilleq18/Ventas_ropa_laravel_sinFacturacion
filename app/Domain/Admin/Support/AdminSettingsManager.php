<?php

namespace App\Domain\Admin\Support;

use App\Domain\Core\Models\AppSetting;
use App\Domain\Core\Models\Sucursal;

class AdminSettingsManager
{
    public const array COMPANY_FIELDS = [
        'nombre' => [
            'key' => 'empresa.nombre',
            'default' => '',
            'description' => 'Nombre comercial de la empresa para tickets y pantallas.',
        ],
        'razon_social' => [
            'key' => 'empresa.razon_social',
            'default' => '',
            'description' => 'Razon social de la empresa.',
        ],
        'cuit' => [
            'key' => 'empresa.cuit',
            'default' => '',
            'description' => 'CUIT de la empresa.',
        ],
        'direccion' => [
            'key' => 'empresa.direccion',
            'default' => '',
            'description' => 'Direccion comercial/fiscal de la empresa.',
        ],
    ];

    public const string FISCAL_CONDITION_KEY = 'empresa.condicion_fiscal';
    public const string FISCAL_RESPONSABLE_INSCRIPTO = 'RESPONSABLE_INSCRIPTO';
    public const string FISCAL_MONOTRIBUTISTA = 'MONOTRIBUTISTA';
    public const array FISCAL_CHOICES = [
        self::FISCAL_RESPONSABLE_INSCRIPTO => 'Responsable Inscripto',
        self::FISCAL_MONOTRIBUTISTA => 'Monotributista',
    ];

    public const array SALES_FLAGS_META = [
        'permitir_sin_stock' => [
            'default' => false,
            'label' => 'Permitir vender sin stock',
            'help_text' => 'Habilita confirmar ventas aunque la sucursal no tenga stock suficiente.',
            'description' => 'Permite confirmar venta aunque no haya stock suficiente.',
        ],
        'permitir_cambiar_precio_venta' => [
            'default' => false,
            'label' => 'Permitir cambiar precio de venta',
            'help_text' => 'Permite editar el precio unitario desde el carrito del POS.',
            'description' => 'Permite cambiar el precio de venta en el POS.',
        ],
    ];

    public function getStringSetting(string $key, string $default, string $description): string
    {
        $setting = AppSetting::query()->firstOrCreate(
            ['key' => $key],
            ['value_str' => $default, 'description' => $description],
        );

        return trim((string) ($setting->value_str ?? $default));
    }

    public function setStringSetting(string $key, string $value, string $default, string $description): void
    {
        $setting = AppSetting::query()->firstOrCreate(
            ['key' => $key],
            ['value_str' => $default, 'description' => $description],
        );

        $setting->value_str = trim($value);
        if (! trim((string) $setting->description)) {
            $setting->description = $description;
        }
        $setting->save();
    }

    public function getBoolSetting(string $key, bool $default, string $description): bool
    {
        $setting = AppSetting::query()->firstOrCreate(
            ['key' => $key],
            ['value_bool' => $default, 'description' => $description],
        );

        return (bool) $setting->value_bool;
    }

    public function setBoolSetting(string $key, bool $value, bool $default, string $description): void
    {
        $setting = AppSetting::query()->firstOrCreate(
            ['key' => $key],
            ['value_bool' => $default, 'description' => $description],
        );

        $setting->value_bool = $value;
        if (! trim((string) $setting->description)) {
            $setting->description = $description;
        }
        $setting->save();
    }

    public function getCompanyData(): array
    {
        $data = [];

        foreach (self::COMPANY_FIELDS as $field => $meta) {
            $data[$field] = $this->getStringSetting(
                $meta['key'],
                $meta['default'],
                $meta['description'],
            );
        }

        $data['condicion_fiscal'] = $this->getFiscalCondition();

        return $data;
    }

    public function saveCompanyData(array $payload): void
    {
        foreach (self::COMPANY_FIELDS as $field => $meta) {
            $this->setStringSetting(
                $meta['key'],
                (string) ($payload[$field] ?? ''),
                $meta['default'],
                $meta['description'],
            );
        }

        $this->setStringSetting(
            self::FISCAL_CONDITION_KEY,
            $this->normalizeFiscalCondition((string) ($payload['condicion_fiscal'] ?? '')),
            self::FISCAL_MONOTRIBUTISTA,
            'Condicion fiscal de la empresa para POS y ticket.',
        );
    }

    public function getFiscalCondition(): string
    {
        return $this->normalizeFiscalCondition(
            $this->getStringSetting(
                self::FISCAL_CONDITION_KEY,
                self::FISCAL_MONOTRIBUTISTA,
                'Condicion fiscal de la empresa para POS y ticket.',
            ),
        );
    }

    public function fiscalChoices(): array
    {
        return self::FISCAL_CHOICES;
    }

    public function normalizeFiscalCondition(string $value): string
    {
        $normalized = strtoupper(str_replace([' ', '-', '_'], '', trim($value)));

        return match ($normalized) {
            'RI', 'RESPONSABLEINSCRIPTO' => self::FISCAL_RESPONSABLE_INSCRIPTO,
            'MONOTRIBUTO', 'MONOTRIBUTISTA' => self::FISCAL_MONOTRIBUTISTA,
            default => self::FISCAL_MONOTRIBUTISTA,
        };
    }

    public function salesFlagsCatalog(): array
    {
        $rows = [];

        foreach (self::SALES_FLAGS_META as $name => $meta) {
            $rows[] = [
                'name' => $name,
                'label' => $meta['label'],
                'help_text' => $meta['help_text'],
                'description' => $meta['description'],
                'default' => (bool) $meta['default'],
            ];
        }

        return $rows;
    }

    public function salesFlagsUi(?Sucursal $sucursal): array
    {
        $rows = [];
        $sucursalId = $sucursal?->id;

        foreach ($this->salesFlagsCatalog() as $flag) {
            $source = 'global';
            $value = null;

            if ($sucursalId) {
                $overrideKey = $this->salesSucursalKey($sucursalId, $flag['name']);
                $overrideValue = AppSetting::query()
                    ->where('key', $overrideKey)
                    ->value('value_bool');

                if ($overrideValue !== null) {
                    $source = 'sucursal';
                    $value = (bool) $overrideValue;
                }
            }

            if ($value === null) {
                $value = $this->getBoolSetting(
                    "ventas.{$flag['name']}",
                    $flag['default'],
                    $flag['description'],
                );
            }

            $rows[] = [
                ...$flag,
                'value' => (bool) $value,
                'source' => $source,
            ];
        }

        return $rows;
    }

    public function salesFlagValue(?Sucursal $sucursal, string $flagName): bool
    {
        $meta = self::SALES_FLAGS_META[$flagName] ?? null;

        if (! $meta) {
            return false;
        }

        if ($sucursal?->id) {
            $overrideValue = AppSetting::query()
                ->where('key', $this->salesSucursalKey($sucursal->id, $flagName))
                ->value('value_bool');

            if ($overrideValue !== null) {
                return (bool) $overrideValue;
            }
        }

        return $this->getBoolSetting(
            "ventas.{$flagName}",
            (bool) $meta['default'],
            $meta['description'],
        );
    }

    public function saveSalesFlags(Sucursal $sucursal, array $flagValues): void
    {
        foreach ($this->salesFlagsCatalog() as $flag) {
            if (! array_key_exists($flag['name'], $flagValues)) {
                continue;
            }

            $this->setBoolSetting(
                $this->salesSucursalKey($sucursal->id, $flag['name']),
                (bool) $flagValues[$flag['name']],
                $flag['default'],
                "{$flag['description']} (Sucursal #{$sucursal->id})",
            );
        }
    }

    protected function salesSucursalKey(int $sucursalId, string $flagName): string
    {
        return "ventas.sucursal.{$sucursalId}.{$flagName}";
    }
}
