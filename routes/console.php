<?php

use App\Support\Migration\LegacyCsvMigrationService;
use App\Support\Migration\LegacyAccessSyncService;
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
