<?php

namespace App\Domain\Fiscal\Support;

use App\Domain\Core\Models\AppSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class ArcaCredentialManager
{
    public const string MANAGED_DIR = 'storage/app/private/fiscal/arca-managed';

    public const array SETTINGS = [
        'represented_cuit' => [
            'key' => 'fiscal.arca.represented_cuit',
            'description' => 'CUIT representado para WSAA/WSFE.',
        ],
        'alias' => [
            'key' => 'fiscal.arca.alias',
            'description' => 'Alias simbólico usado en WSASS para la CSR.',
        ],
        'organization' => [
            'key' => 'fiscal.arca.organization',
            'description' => 'Organization usada al generar la CSR de ARCA.',
        ],
        'common_name' => [
            'key' => 'fiscal.arca.common_name',
            'description' => 'Common Name usado al generar la CSR de ARCA.',
        ],
        'certificate_path' => [
            'key' => 'fiscal.arca.certificate_path',
            'description' => 'Ruta del certificado ARCA administrado desde el panel.',
        ],
        'private_key_path' => [
            'key' => 'fiscal.arca.private_key_path',
            'description' => 'Ruta de la clave privada ARCA administrada desde el panel.',
        ],
        'csr_path' => [
            'key' => 'fiscal.arca.csr_path',
            'description' => 'Ruta de la última CSR ARCA generada desde el panel.',
        ],
    ];

    public function status(): array
    {
        $representedCuit = $this->resolvedSettingWithSource(
            'represented_cuit',
            (string) config('fiscal.arca.represented_cuit', ''),
        );
        $alias = $this->resolvedSettingWithSource('alias');
        $organization = $this->resolvedSettingWithSource('organization');
        $commonName = $this->resolvedSettingWithSource('common_name');
        $certificate = $this->resolvedSettingWithSource(
            'certificate_path',
            (string) config('fiscal.arca.certificate_path', ''),
        );
        $privateKey = $this->resolvedSettingWithSource(
            'private_key_path',
            (string) config('fiscal.arca.private_key_path', ''),
        );
        $csr = $this->resolvedSettingWithSource('csr_path');

        return [
            'represented_cuit' => [
                'configured' => strlen($representedCuit['value']) === 11,
                'masked' => $representedCuit['value'] !== ''
                    ? substr($representedCuit['value'], 0, 2).'*******'.substr($representedCuit['value'], -2)
                    : null,
                'value' => $representedCuit['value'] ?: null,
                'source' => $representedCuit['source'],
            ],
            'alias' => [
                'configured' => $alias['value'] !== '',
                'value' => $alias['value'] ?: null,
                'source' => $alias['source'],
            ],
            'organization' => [
                'configured' => $organization['value'] !== '',
                'value' => $organization['value'] ?: null,
                'source' => $organization['source'],
            ],
            'common_name' => [
                'configured' => $commonName['value'] !== '',
                'value' => $commonName['value'] ?: null,
                'source' => $commonName['source'],
            ],
            'certificate' => $this->pathStatus($certificate['value'], $certificate['source']),
            'private_key' => $this->pathStatus($privateKey['value'], $privateKey['source']),
            'csr' => [
                ...$this->pathStatus($csr['value'], $csr['source']),
                'pem' => $this->readTextFile($csr['value']),
            ],
        ];
    }

    public function resolvedRepresentedCuit(): string
    {
        return $this->resolvedSettingWithSource(
            'represented_cuit',
            (string) config('fiscal.arca.represented_cuit', ''),
        )['value'];
    }

    public function resolvedCertificatePath(): string
    {
        return $this->resolvedSettingWithSource(
            'certificate_path',
            (string) config('fiscal.arca.certificate_path', ''),
        )['value'];
    }

    public function resolvedPrivateKeyPath(): string
    {
        return $this->resolvedSettingWithSource(
            'private_key_path',
            (string) config('fiscal.arca.private_key_path', ''),
        )['value'];
    }

    public function generateKeyAndCsr(
        string $representedCuit,
        string $alias,
        string $organization,
        string $commonName,
    ): array {
        $normalizedCuit = $this->normalizeCuit($representedCuit);
        $safeAlias = $this->sanitizeAlias($alias);
        $organization = trim($organization);
        $commonName = trim($commonName);

        if ($organization === '') {
            throw new RuntimeException('La organización no puede quedar vacía.');
        }

        if ($commonName === '') {
            throw new RuntimeException('El Common Name no puede quedar vacío.');
        }

        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
            'digest_alg' => 'sha256',
            ...$this->opensslConfigOptions(),
        ]);

        if ($privateKey === false) {
            throw new RuntimeException('No se pudo generar la clave privada RSA para ARCA. '.$this->lastOpenSslErrorMessage());
        }

        $privateKeyPem = '';
        $csrPem = '';

        try {
            if (! openssl_pkey_export($privateKey, $privateKeyPem, null, $this->opensslConfigOptions())) {
                throw new RuntimeException('No se pudo exportar la clave privada generada. '.$this->lastOpenSslErrorMessage());
            }

            $csr = openssl_csr_new([
                'countryName' => 'AR',
                'organizationName' => $organization,
                'commonName' => $commonName,
                'serialNumber' => 'CUIT '.$normalizedCuit,
            ], $privateKey, [
                'digest_alg' => 'sha256',
                ...$this->opensslConfigOptions(),
            ]);

            if ($csr === false) {
                throw new RuntimeException('No se pudo generar la CSR para ARCA. '.$this->lastOpenSslErrorMessage());
            }

            if (! openssl_csr_export($csr, $csrPem)) {
                throw new RuntimeException('No se pudo exportar la CSR generada.');
            }
        } finally {
            if (is_object($privateKey)) {
                openssl_pkey_free($privateKey);
            }
        }

        $baseName = $this->managedBaseName($safeAlias, $normalizedCuit);
        $keyRelativePath = $this->managedRelativePath("{$baseName}.key");
        $csrRelativePath = $this->managedRelativePath("{$baseName}.csr");

        $this->ensureManagedDirectory();
        File::put($this->absolutePath($keyRelativePath), $privateKeyPem);
        File::put($this->absolutePath($csrRelativePath), $csrPem);

        $this->storeSetting('represented_cuit', $normalizedCuit);
        $this->storeSetting('alias', $safeAlias);
        $this->storeSetting('organization', $organization);
        $this->storeSetting('common_name', $commonName);
        $this->storeSetting('private_key_path', $keyRelativePath);
        $this->storeSetting('csr_path', $csrRelativePath);
        $this->storeSetting('certificate_path', '');

        return [
            'represented_cuit' => $normalizedCuit,
            'alias' => $safeAlias,
            'organization' => $organization,
            'common_name' => $commonName,
            'private_key_path' => $keyRelativePath,
            'csr_path' => $csrRelativePath,
            'csr_pem' => trim($csrPem),
        ];
    }

    public function storeCertificate(?UploadedFile $uploadedFile = null, ?string $pem = null): array
    {
        $privateKeyPath = trim($this->resolvedPrivateKeyPath());

        if ($privateKeyPath === '') {
            throw new RuntimeException('Primero generá o configurá una clave privada antes de cargar el certificado.');
        }

        $privateKeyAbsolutePath = $this->absolutePath($privateKeyPath);

        if (! File::exists($privateKeyAbsolutePath)) {
            throw new RuntimeException('No se encontró la clave privada configurada para validar el certificado.');
        }

        $certificatePem = $uploadedFile
            ? trim((string) File::get($uploadedFile->getRealPath()))
            : trim((string) $pem);

        if ($certificatePem === '') {
            throw new RuntimeException('Pegá el certificado PEM o subí un archivo .crt/.pem.');
        }

        $certificate = openssl_x509_read($certificatePem);

        if ($certificate === false) {
            throw new RuntimeException('El certificado cargado no tiene un formato PEM/X509 válido.');
        }

        $privateKey = openssl_pkey_get_private(
            File::get($privateKeyAbsolutePath),
            (string) config('fiscal.arca.private_key_passphrase', ''),
        );

        if ($privateKey === false) {
            throw new RuntimeException('No se pudo abrir la clave privada configurada para validar el certificado.');
        }

        $certificatePublicKey = openssl_pkey_get_public($certificatePem);

        if ($certificatePublicKey === false) {
            throw new RuntimeException('No se pudo leer la clave pública del certificado cargado.');
        }

        $privateKeyDetails = openssl_pkey_get_details($privateKey);
        $certificatePublicKeyDetails = openssl_pkey_get_details($certificatePublicKey);

        $privatePublicKey = (string) ($privateKeyDetails['key'] ?? '');
        $certificateKey = (string) ($certificatePublicKeyDetails['key'] ?? '');

        if ($privatePublicKey === '' || $certificateKey === '' || ! hash_equals($privatePublicKey, $certificateKey)) {
            throw new RuntimeException('El certificado cargado no corresponde con la clave privada actual.');
        }

        $certificateRelativePath = $this->managedRelativePath($this->managedBaseName(
            $this->resolvedSettingWithSource('alias')['value'] ?: 'arca',
            $this->resolvedRepresentedCuit() ?: 'sin-cuit',
        ).'.crt');

        $this->ensureManagedDirectory();
        File::put($this->absolutePath($certificateRelativePath), $certificatePem.PHP_EOL);
        $this->storeSetting('certificate_path', $certificateRelativePath);

        return [
            'certificate_path' => $certificateRelativePath,
            'validation' => $this->validateConfiguredCredentials(),
        ];
    }

    public function validateConfiguredCredentials(): array
    {
        $certificatePath = trim($this->resolvedCertificatePath());
        $privateKeyPath = trim($this->resolvedPrivateKeyPath());

        if ($certificatePath === '') {
            throw new RuntimeException('Todavía no hay un certificado ARCA configurado.');
        }

        if ($privateKeyPath === '') {
            throw new RuntimeException('Todavía no hay una clave privada ARCA configurada.');
        }

        $certificateAbsolutePath = $this->absolutePath($certificatePath);
        $privateKeyAbsolutePath = $this->absolutePath($privateKeyPath);

        if (! File::exists($certificateAbsolutePath)) {
            throw new RuntimeException('No se encontró el certificado configurado para ARCA.');
        }

        if (! File::exists($privateKeyAbsolutePath)) {
            throw new RuntimeException('No se encontró la clave privada configurada para ARCA.');
        }

        $certificatePem = (string) File::get($certificateAbsolutePath);
        $privateKeyPem = (string) File::get($privateKeyAbsolutePath);
        $certificate = openssl_x509_read($certificatePem);

        if ($certificate === false) {
            throw new RuntimeException('No se pudo interpretar el certificado configurado.');
        }

        $privateKey = openssl_pkey_get_private(
            $privateKeyPem,
            (string) config('fiscal.arca.private_key_passphrase', ''),
        );

        if ($privateKey === false) {
            throw new RuntimeException('No se pudo abrir la clave privada configurada.');
        }

        $publicKey = openssl_pkey_get_public($certificatePem);

        if ($publicKey === false) {
            throw new RuntimeException('No se pudo leer la clave pública del certificado configurado.');
        }

        $privateKeyDetails = openssl_pkey_get_details($privateKey);
        $publicKeyDetails = openssl_pkey_get_details($publicKey);
        $keyMatches = isset($privateKeyDetails['key'], $publicKeyDetails['key'])
            && hash_equals((string) $privateKeyDetails['key'], (string) $publicKeyDetails['key']);
        $parsedCertificate = openssl_x509_parse($certificate) ?: [];
        $subjectSerialNumber = trim((string) data_get($parsedCertificate, 'subject.serialNumber', ''));
        $subjectCuit = preg_replace('/\D+/', '', $subjectSerialNumber) ?: '';
        $representedCuit = $this->resolvedRepresentedCuit();
        $warnings = [];

        if (! $keyMatches) {
            $warnings[] = 'La clave privada no coincide con el certificado configurado.';
        }

        if (strlen($representedCuit) !== 11) {
            $warnings[] = 'Falta configurar un CUIT representado válido para ARCA.';
        } elseif ($subjectCuit !== '' && $subjectCuit !== $representedCuit) {
            $warnings[] = 'El CUIT del subject del certificado no coincide con el CUIT representado configurado.';
        }

        return [
            'ok' => $warnings === [],
            'key_matches_certificate' => $keyMatches,
            'warnings' => $warnings,
            'represented_cuit' => $representedCuit ?: null,
            'subject' => $this->distinguishedNameToString((array) data_get($parsedCertificate, 'subject', [])),
            'issuer' => $this->distinguishedNameToString((array) data_get($parsedCertificate, 'issuer', [])),
            'subject_cuit' => $subjectCuit ?: null,
            'valid_from' => $this->formatCertificateTimestamp(data_get($parsedCertificate, 'validFrom_time_t')),
            'valid_to' => $this->formatCertificateTimestamp(data_get($parsedCertificate, 'validTo_time_t')),
            'certificate_path' => $certificatePath,
            'private_key_path' => $privateKeyPath,
        ];
    }

    protected function resolvedSettingWithSource(string $name, ?string $fallback = null): array
    {
        $setting = AppSetting::query()
            ->where('key', self::SETTINGS[$name]['key'])
            ->first();

        if ($setting) {
            return [
                'value' => trim((string) ($setting->value_str ?? '')),
                'source' => 'panel',
            ];
        }

        $fallbackValue = trim((string) ($fallback ?? ''));

        return [
            'value' => $fallbackValue,
            'source' => $fallbackValue !== '' ? 'env' : 'missing',
        ];
    }

    protected function storeSetting(string $name, string $value): void
    {
        $definition = self::SETTINGS[$name];

        AppSetting::query()->updateOrCreate(
            ['key' => $definition['key']],
            [
                'value_str' => $value,
                'description' => $definition['description'],
            ],
        );
    }

    protected function pathStatus(string $path, string $source): array
    {
        $exists = $path !== '' && File::exists($this->absolutePath($path));

        return [
            'configured' => $path !== '',
            'exists' => $exists,
            'path' => $path !== '' ? $path : null,
            'source' => $source,
            'managed' => $path !== '' && str_starts_with(str_replace('\\', '/', $path), self::MANAGED_DIR.'/'),
            'updated_at' => $exists
                ? CarbonImmutable::createFromTimestamp(File::lastModified($this->absolutePath($path)))
                    ->timezone(config('app.timezone'))
                    ->format('Y-m-d H:i')
                : null,
        ];
    }

    protected function readTextFile(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        $absolutePath = $this->absolutePath($path);

        if (! File::exists($absolutePath)) {
            return null;
        }

        return trim((string) File::get($absolutePath));
    }

    protected function managedBaseName(string $alias, string $representedCuit): string
    {
        return 'arca-'.$alias.'-'.$representedCuit.'-'.Str::lower(Str::ulid());
    }

    protected function managedRelativePath(string $filename): string
    {
        return self::MANAGED_DIR.'/'.$filename;
    }

    protected function ensureManagedDirectory(): void
    {
        File::ensureDirectoryExists($this->absolutePath(self::MANAGED_DIR));
    }

    protected function absolutePath(string $path): string
    {
        return $this->isAbsolutePath($path) ? $path : base_path($path);
    }

    protected function isAbsolutePath(string $path): bool
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\\\\\\\|\\/)/', $path) === 1;
    }

    protected function normalizeCuit(string $value): string
    {
        $normalized = preg_replace('/\D+/', '', trim($value)) ?: '';

        if (strlen($normalized) !== 11) {
            throw new RuntimeException('El CUIT representado debe tener 11 dígitos.');
        }

        return $normalized;
    }

    protected function sanitizeAlias(string $value): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new RuntimeException('El alias no puede quedar vacío.');
        }

        if (! preg_match('/^[A-Za-z0-9]+$/', $normalized)) {
            throw new RuntimeException('El alias DN solo puede contener letras y números, sin espacios ni símbolos.');
        }

        return $normalized;
    }

    protected function distinguishedNameToString(array $parts): ?string
    {
        if ($parts === []) {
            return null;
        }

        return collect($parts)
            ->map(fn (mixed $value, string $key) => $key.'='.$value)
            ->implode(', ');
    }

    protected function formatCertificateTimestamp(mixed $timestamp): ?string
    {
        if (! is_numeric($timestamp)) {
            return null;
        }

        return CarbonImmutable::createFromTimestampUTC((int) $timestamp)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i');
    }

    protected function opensslConfigOptions(): array
    {
        $path = $this->opensslConfigPath();

        if ($path === null) {
            return [];
        }

        putenv("OPENSSL_CONF={$path}");

        return ['config' => $path];
    }

    protected function opensslConfigPath(): ?string
    {
        $candidates = array_filter([
            getenv('OPENSSL_CONF') ?: null,
            dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
        ]);

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);

            if ($resolved && File::exists($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    protected function lastOpenSslErrorMessage(): string
    {
        $errors = [];

        while ($message = openssl_error_string()) {
            $errors[] = $message;
        }

        return $errors !== [] ? implode(' | ', $errors) : 'Revisá la configuración de OpenSSL del runtime.';
    }
}
