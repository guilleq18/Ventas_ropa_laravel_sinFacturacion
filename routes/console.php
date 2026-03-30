<?php

use App\Support\Migration\LegacyCsvMigrationService;
use App\Support\Migration\LegacyAccessSyncService;
use App\Domain\Core\Models\Sucursal;
use App\Domain\Fiscal\Support\ArcaHomologationProbe;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('migracion:exportar-django {--connection=django_mysql} {--output=} {--only=*}', function (LegacyCsvMigrationService $service): int {
    $output = (string) ($this->option('output') ?: storage_path('app/legacy-migration'));
    $report = $service->exportFromConnection(
        (string) $this->option('connection'),
        $output,
        (array) $this->option('only'),
    );

    $this->info("Exportacion generada en {$output}");
    $this->table(
        ['Dataset', 'Origen', 'Destino', 'Filas', 'Archivo'],
        collect($report)->map(fn (array $row) => [
            $row['dataset'],
            $row['source_table'],
            $row['destination_table'],
            $row['rows'],
            $row['file'],
        ])->all(),
    );

    return Command::SUCCESS;
})->purpose('Exporta datasets desde Django/MySQL a CSV normalizado');

Artisan::command('migracion:importar-csv {path?} {--only=*} {--truncate}', function (LegacyCsvMigrationService $service): int {
    $path = (string) ($this->argument('path') ?: storage_path('app/legacy-migration'));
    $report = $service->importFromDirectory(
        $path,
        (array) $this->option('only'),
        (bool) $this->option('truncate'),
    );

    $this->info("Importacion aplicada desde {$path}");
    $this->table(
        ['Dataset', 'Destino', 'Filas'],
        collect($report)->map(fn (array $row) => [
            $row['dataset'],
            $row['destination_table'],
            $row['rows'],
        ])->all(),
    );

    return Command::SUCCESS;
})->purpose('Importa CSVs normalizados de la migracion legacy');

Artisan::command('migracion:validar-csv {path?} {--only=*}', function (LegacyCsvMigrationService $service): int {
    $path = (string) ($this->argument('path') ?: storage_path('app/legacy-migration'));
    $report = $service->validateDirectory(
        $path,
        (array) $this->option('only'),
    );

    $this->table(
        ['Dataset', 'CSV', 'DB', 'Estado'],
        collect($report['counts'])->map(fn (array $row) => [
            $row['dataset'],
            $row['csv'],
            $row['db'],
            $row['ok'] ? 'OK' : 'ERROR',
        ])->all(),
    );

    if ($report['checks'] !== []) {
        $this->newLine();
        $this->table(
            ['Chequeo', 'Clave', 'CSV', 'DB', 'Estado'],
            collect($report['checks'])->map(fn (array $row) => [
                $row['check'],
                $row['key'],
                $row['csv'],
                $row['db'],
                $row['ok'] ? 'OK' : 'ERROR',
            ])->all(),
        );
    }

    if ($report['ok']) {
        $this->info('Validacion OK.');

        return Command::SUCCESS;
    }

    $this->error('Validacion con diferencias.');

    return Command::FAILURE;
})->purpose('Valida conteos y sumas clave contra los CSVs exportados');

Artisan::command('migracion:sincronizar-accesos {--connection=django_mysql}', function (LegacyAccessSyncService $service): int {
    $report = $service->syncFromConnection((string) $this->option('connection'));

    $this->table(
        ['Roles sincronizados', 'Usuarios sincronizados', 'Asignaciones de rol', 'Permisos directos'],
        [[
            $report['roles'],
            $report['users'],
            $report['role_assignments'],
            $report['direct_permissions'],
        ]],
    );

    return Command::SUCCESS;
})->purpose('Sincroniza roles y accesos desde grupos/permisos de Django');

Artisan::command('fiscal:homologacion-probar {sucursal} {--sin-auth}', function (
    ArcaHomologationProbe $probe,
): int {
    $branch = Sucursal::query()->find($this->argument('sucursal'));

    if (! $branch) {
        $this->error('No se encontró la sucursal indicada.');

        return Command::FAILURE;
    }

    try {
        $report = $probe->probeBranch($branch, ! (bool) $this->option('sin-auth'));

        $this->info("Sucursal: {$branch->nombre}");
        $this->table(
            ['Chequeo', 'Resultado'],
            [
                ['Entorno', $report['environment']],
                ['Punto de venta', (string) $report['point_of_sale']],
                ['Clase sugerida', $report['receipt_class']],
                ['Código comprobante', (string) $report['receipt_code']],
                ['Preparación fiscal', $report['readiness']['ready'] ? 'OK' : 'PENDIENTE'],
            ],
        );

        if (($report['readiness']['issues'] ?? []) !== []) {
            $this->warn('Pendientes de configuración:');
            foreach ($report['readiness']['issues'] as $issue) {
                $this->line(" - {$issue}");
            }
        }

        $this->newLine();
        $this->table(
            ['Servicio', 'Estado'],
            [[
                'WSFE FEDummy',
                implode(' | ', [
                    'App '.$report['wsfe_dummy']['app_server'],
                    'DB '.$report['wsfe_dummy']['db_server'],
                    'Auth '.$report['wsfe_dummy']['auth_server'],
                ]),
            ]],
        );

        if ($this->option('sin-auth')) {
            $this->comment('Se omitió la prueba WSAA/FECompUltimoAutorizado por opción --sin-auth.');

            return Command::SUCCESS;
        }

        if ($report['wsaa']) {
            $this->info('WSAA OK. Expiración del TA: '.$report['wsaa']['expiration_time']);
        }

        if ($report['last_authorized']) {
            $this->info(
                'WSFE autorizado. Último comprobante informado: '.(string) ($report['last_authorized']['numero'] ?? 0),
            );
        }

        return Command::SUCCESS;
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());

        return Command::FAILURE;
    }
})->purpose('Prueba el circuito de homologación ARCA/WSFE para una sucursal');
