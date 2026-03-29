<?php

namespace App\Support\Migration;

use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LegacyCsvMigrationService
{
    public function __construct(
        protected LegacyDatasetCatalog $catalog,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportFromConnection(string $connection, string $outputDir, array $only = []): array
    {
        File::ensureDirectoryExists($outputDir);
        $datasets = $this->catalog->selected($only);
        $manifest = [];

        foreach ($datasets as $dataset) {
            $rows = DB::connection($connection)
                ->table($dataset['source_table'])
                ->orderBy('id')
                ->get($dataset['source_columns'])
                ->map(fn (object $row) => $this->catalog->normalizeExportRow($dataset['key'], (array) $row))
                ->all();
            $rows = $this->normalizeExportRows($dataset['key'], $rows);

            $path = $outputDir.DIRECTORY_SEPARATOR.$dataset['csv'];
            $count = $this->writeCsv($path, $dataset['headers'], $rows);

            $manifest[] = [
                'dataset' => $dataset['key'],
                'source_table' => $dataset['source_table'],
                'destination_table' => $dataset['destination_table'],
                'rows' => $count,
                'file' => $path,
            ];
        }

        File::put(
            $outputDir.DIRECTORY_SEPARATOR.'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        return $manifest;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    protected function normalizeExportRows(string $datasetKey, array $rows): array
    {
        if ($datasetKey !== 'users') {
            return $rows;
        }

        $usedEmails = [];

        foreach ($rows as $index => $row) {
            $rows[$index]['email'] = $this->uniqueUserEmail($row, $usedEmails);
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, bool> $usedEmails
     */
    protected function uniqueUserEmail(array $row, array &$usedEmails): string
    {
        $original = strtolower(trim((string) ($row['email'] ?? '')));
        $username = trim((string) ($row['username'] ?? ''));
        $userId = (int) ($row['id'] ?? 0);
        $candidates = array_filter([
            $original !== '' ? $original : null,
            $username !== '' ? strtolower($username).'@legacy.local' : null,
            "legacy-user-{$userId}@legacy.local",
        ]);

        foreach ($candidates as $candidate) {
            if (! isset($usedEmails[$candidate])) {
                $usedEmails[$candidate] = true;

                return $candidate;
            }
        }

        $suffix = 1;

        do {
            $candidate = "legacy-user-{$userId}-{$suffix}@legacy.local";
            $suffix++;
        } while (isset($usedEmails[$candidate]));

        $usedEmails[$candidate] = true;

        return $candidate;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function importFromDirectory(string $inputDir, array $only = [], bool $truncate = false): array
    {
        $datasets = $this->catalog->selected($only);

        if ($truncate) {
            Schema::disableForeignKeyConstraints();
            foreach (array_reverse($datasets) as $dataset) {
                DB::table($dataset['destination_table'])->delete();
            }
            Schema::enableForeignKeyConstraints();
        }

        $report = [];

        foreach ($datasets as $dataset) {
            $path = $inputDir.DIRECTORY_SEPARATOR.$dataset['csv'];

            if (! File::exists($path)) {
                throw new RuntimeException("No existe el archivo {$path}.");
            }

            $headers = $this->readHeaders($path);
            $rows = [];
            $count = 0;
            $updateColumns = array_values(array_diff($headers, $dataset['unique_by']));

            foreach ($this->readRows($path) as $row) {
                $rows[] = $this->catalog->normalizeImportRow($dataset, $row);
                $count++;

                if (count($rows) >= 500) {
                    DB::table($dataset['destination_table'])->upsert($rows, $dataset['unique_by'], $updateColumns);
                    $rows = [];
                }
            }

            if ($rows !== []) {
                DB::table($dataset['destination_table'])->upsert($rows, $dataset['unique_by'], $updateColumns);
            }

            $report[] = [
                'dataset' => $dataset['key'],
                'destination_table' => $dataset['destination_table'],
                'rows' => $count,
            ];
        }

        if ($this->hasDataset($datasets, 'movimientos_cuenta_corriente')
            && Schema::hasTable('pagos_cuenta_corriente')
            && Schema::hasTable('pago_cuenta_corriente_aplicaciones')) {
            $this->rebuildCurrentAccountPayments();
        }

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateDirectory(string $inputDir, array $only = []): array
    {
        $datasets = $this->catalog->selected($only);
        $counts = [];
        $ok = true;

        foreach ($datasets as $dataset) {
            $path = $inputDir.DIRECTORY_SEPARATOR.$dataset['csv'];

            if (! File::exists($path)) {
                throw new RuntimeException("No existe el archivo {$path}.");
            }

            $csvCount = $this->countRows($path);
            $dbCount = DB::table($dataset['destination_table'])->count();
            $rowOk = $csvCount === $dbCount;
            $ok = $ok && $rowOk;

            $counts[] = [
                'dataset' => $dataset['key'],
                'csv' => $csvCount,
                'db' => $dbCount,
                'ok' => $rowOk,
            ];
        }

        $checks = [];

        if ($this->hasDataset($datasets, 'ventas')) {
            foreach ($this->compareSalesByBranch($inputDir) as $row) {
                $ok = $ok && $row['ok'];
                $checks[] = [
                    'check' => 'ventas_por_sucursal',
                    'key' => $row['key'],
                    'csv' => $row['csv'],
                    'db' => $row['db'],
                    'ok' => $row['ok'],
                ];
            }
        }

        if ($this->hasDataset($datasets, 'movimientos_cuenta_corriente')) {
            foreach ($this->compareCurrentAccountBalances($inputDir) as $row) {
                $ok = $ok && $row['ok'];
                $checks[] = [
                    'check' => 'saldo_por_cuenta_corriente',
                    'key' => $row['key'],
                    'csv' => $row['csv'],
                    'db' => $row['db'],
                    'ok' => $row['ok'],
                ];
            }
        }

        if ($this->hasDataset($datasets, 'stock_sucursal')) {
            foreach ($this->compareStockByBranchAndVariant($inputDir) as $row) {
                $ok = $ok && $row['ok'];
                $checks[] = [
                    'check' => 'stock_por_variante_sucursal',
                    'key' => $row['key'],
                    'csv' => $row['csv'],
                    'db' => $row['db'],
                    'ok' => $row['ok'],
                ];
            }
        }

        return [
            'ok' => $ok,
            'counts' => $counts,
            'checks' => $checks,
        ];
    }

    /**
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     */
    protected function writeCsv(string $path, array $headers, array $rows): int
    {
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException("No se pudo escribir {$path}.");
        }

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (string $header) => $row[$header] ?? '', $headers));
        }

        fclose($handle);

        return count($rows);
    }

    /**
     * @return list<string>
     */
    protected function readHeaders(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("No se pudo leer {$path}.");
        }

        $headers = fgetcsv($handle) ?: [];
        fclose($handle);

        return $headers;
    }

    /**
     * @return \Generator<int, array<string, string|null>>
     */
    protected function readRows(string $path): \Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("No se pudo leer {$path}.");
        }

        $headers = fgetcsv($handle) ?: [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            yield array_combine($headers, $row);
        }

        fclose($handle);
    }

    protected function countRows(string $path): int
    {
        $count = 0;

        foreach ($this->readRows($path) as $row) {
            $count++;
        }

        return $count;
    }

    /**
     * @param array<int, array<string, mixed>> $datasets
     */
    protected function hasDataset(array $datasets, string $key): bool
    {
        return collect($datasets)->contains(fn (array $dataset) => $dataset['key'] === $key);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function compareSalesByBranch(string $inputDir): array
    {
        $csv = [];

        foreach ($this->readRows($inputDir.DIRECTORY_SEPARATOR.'ventas.csv') as $row) {
            if (($row['estado'] ?? '') !== 'CONFIRMADA') {
                continue;
            }

            $key = (string) $row['sucursal_id'];
            $csv[$key] = ($csv[$key] ?? 0.0) + (float) ($row['total'] ?? 0);
        }

        $db = DB::table('ventas')
            ->selectRaw('sucursal_id, SUM(total) as total')
            ->where('estado', 'CONFIRMADA')
            ->groupBy('sucursal_id')
            ->pluck('total', 'sucursal_id')
            ->map(fn (mixed $value) => (float) $value)
            ->all();

        return $this->compareNumericMaps($csv, $db);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function compareCurrentAccountBalances(string $inputDir): array
    {
        $csv = [];

        foreach ($this->readRows($inputDir.DIRECTORY_SEPARATOR.'movimientos_cuenta_corriente.csv') as $row) {
            $key = (string) $row['cuenta_id'];
            $amount = (float) ($row['monto'] ?? 0);
            $sign = ($row['tipo'] ?? '') === 'DEBITO' ? 1 : -1;
            $csv[$key] = ($csv[$key] ?? 0.0) + ($amount * $sign);
        }

        $db = DB::table('movimientos_cuenta_corriente')
            ->selectRaw("
                cuenta_id,
                SUM(CASE WHEN tipo = 'DEBITO' THEN monto ELSE -monto END) as saldo
            ")
            ->groupBy('cuenta_id')
            ->pluck('saldo', 'cuenta_id')
            ->map(fn (mixed $value) => (float) $value)
            ->all();

        return $this->compareNumericMaps($csv, $db);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function compareStockByBranchAndVariant(string $inputDir): array
    {
        $csv = [];

        foreach ($this->readRows($inputDir.DIRECTORY_SEPARATOR.'stock_sucursal.csv') as $row) {
            $key = "{$row['sucursal_id']}:{$row['variante_id']}";
            $csv[$key] = (int) ($row['cantidad'] ?? 0);
        }

        $db = DB::table('stock_sucursal')
            ->get(['sucursal_id', 'variante_id', 'cantidad'])
            ->mapWithKeys(fn (object $row) => [
                "{$row->sucursal_id}:{$row->variante_id}" => (int) $row->cantidad,
            ])
            ->all();

        return $this->compareNumericMaps($csv, $db);
    }

    /**
     * @param array<string, float|int> $csv
     * @param array<string, float|int> $db
     * @return array<int, array<string, mixed>>
     */
    protected function compareNumericMaps(array $csv, array $db): array
    {
        $keys = collect(array_keys($csv))
            ->merge(array_keys($db))
            ->unique()
            ->sort()
            ->values();
        $rows = [];

        foreach ($keys as $key) {
            $csvValue = (float) ($csv[$key] ?? 0);
            $dbValue = (float) ($db[$key] ?? 0);
            $rows[] = [
                'key' => $key,
                'csv' => number_format($csvValue, 2, '.', ''),
                'db' => number_format($dbValue, 2, '.', ''),
                'ok' => abs($csvValue - $dbValue) < 0.00001,
            ];
        }

        return $rows;
    }

    protected function rebuildCurrentAccountPayments(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('pago_cuenta_corriente_aplicaciones')->delete();
        DB::table('pagos_cuenta_corriente')->delete();
        Schema::enableForeignKeyConstraints();

        $accountIds = DB::table('movimientos_cuenta_corriente')
            ->distinct()
            ->orderBy('cuenta_id')
            ->pluck('cuenta_id');

        foreach ($accountIds as $accountId) {
            $debits = DB::table('movimientos_cuenta_corriente')
                ->where('cuenta_id', $accountId)
                ->where('tipo', MovimientoCuentaCorriente::TIPO_DEBITO)
                ->orderBy('fecha')
                ->orderBy('id')
                ->get(['id', 'monto']);

            $pendingByDebit = [];

            foreach ($debits as $debit) {
                $pendingByDebit[(int) $debit->id] = $this->money($debit->monto);
            }

            $credits = DB::table('movimientos_cuenta_corriente')
                ->where('cuenta_id', $accountId)
                ->where('tipo', MovimientoCuentaCorriente::TIPO_CREDITO)
                ->orderBy('fecha')
                ->orderBy('id')
                ->get(['id', 'fecha', 'created_at', 'monto']);

            foreach ($credits as $credit) {
                $paymentId = DB::table('pagos_cuenta_corriente')->insertGetId([
                    'cuenta_id' => $accountId,
                    'movimiento_credito_id' => $credit->id,
                    'created_at' => $credit->created_at ?? $credit->fecha ?? now(),
                ]);

                $remaining = BigDecimal::of($this->money($credit->monto));

                foreach ($pendingByDebit as $debitId => $pendingAmount) {
                    if ($remaining->isLessThanOrEqualTo(BigDecimal::zero())) {
                        break;
                    }

                    $pending = BigDecimal::of($pendingAmount);

                    if ($pending->isLessThanOrEqualTo(BigDecimal::zero())) {
                        continue;
                    }

                    $applied = $remaining->isGreaterThan($pending)
                        ? $pending
                        : $remaining;

                    DB::table('pago_cuenta_corriente_aplicaciones')->insert([
                        'pago_cuenta_corriente_id' => $paymentId,
                        'movimiento_debito_id' => $debitId,
                        'monto_aplicado' => $applied->toScale(2, RoundingMode::HALF_UP)->__toString(),
                        'created_at' => $credit->created_at ?? $credit->fecha ?? now(),
                    ]);

                    $pendingByDebit[$debitId] = $pending
                        ->minus($applied)
                        ->toScale(2, RoundingMode::HALF_UP)
                        ->__toString();

                    $remaining = $remaining
                        ->minus($applied)
                        ->toScale(2, RoundingMode::HALF_UP);
                }
            }
        }
    }

    protected function money(mixed $value): string
    {
        return BigDecimal::of((string) ($value ?? '0'))
            ->toScale(2, RoundingMode::HALF_UP)
            ->__toString();
    }
}
