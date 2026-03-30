param(
    [Parameter(Mandatory = $true)]
    [string]$Cuit,

    [Parameter(Mandatory = $true)]
    [string]$Alias,

    [string]$Organization = 'QUINTELA GUILLERMO',

    [string]$CommonName = 'tienda_ropa_laravel',

    [string]$OutputDir = 'storage/app/fiscal',

    [switch]$Force
)

$ErrorActionPreference = 'Stop'

function Convert-ToPem {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Label,

        [Parameter(Mandatory = $true)]
        [byte[]]$Bytes
    )

    $base64 = [Convert]::ToBase64String($Bytes, [Base64FormattingOptions]::InsertLineBreaks)

    return @(
        "-----BEGIN $Label-----"
        $base64
        "-----END $Label-----"
        ''
    ) -join [Environment]::NewLine
}

$normalizedCuit = ($Cuit -replace '\D+', '').Trim()
if ($normalizedCuit.Length -ne 11) {
    throw 'El CUIT debe tener 11 digitos.'
}

$safeAlias = $Alias.Trim()
if ([string]::IsNullOrWhiteSpace($safeAlias)) {
    throw 'El alias no puede quedar vacio.'
}

if ($safeAlias -notmatch '^[a-zA-Z0-9]+$') {
    throw 'El alias solo puede contener letras y numeros, sin espacios ni simbolos.'
}

$resolvedOutputDir = if ([System.IO.Path]::IsPathRooted($OutputDir)) {
    $OutputDir
} else {
    Join-Path (Get-Location) $OutputDir
}

[System.IO.Directory]::CreateDirectory($resolvedOutputDir) | Out-Null

$baseName = "arca-homo-$safeAlias-$normalizedCuit"
$keyPath = Join-Path $resolvedOutputDir "$baseName.key"
$csrPath = Join-Path $resolvedOutputDir "$baseName.csr"

if (-not $Force) {
    foreach ($path in @($keyPath, $csrPath)) {
        if (Test-Path $path) {
            throw "Ya existe el archivo $path. Usa -Force si queres regenerarlo."
        }
    }
}

$subject = "C=AR, O=$Organization, CN=$CommonName, SERIALNUMBER=CUIT $normalizedCuit"
$rsa = [System.Security.Cryptography.RSA]::Create(2048)

try {
    $request = [System.Security.Cryptography.X509Certificates.CertificateRequest]::new(
        $subject,
        $rsa,
        [System.Security.Cryptography.HashAlgorithmName]::SHA256,
        [System.Security.Cryptography.RSASignaturePadding]::Pkcs1
    )

    $csrPem = Convert-ToPem -Label 'CERTIFICATE REQUEST' -Bytes $request.CreateSigningRequest()
    $keyPem = Convert-ToPem -Label 'RSA PRIVATE KEY' -Bytes $rsa.ExportRSAPrivateKey()

    [System.IO.File]::WriteAllText($csrPath, $csrPem, [System.Text.Encoding]::ASCII)
    [System.IO.File]::WriteAllText($keyPath, $keyPem, [System.Text.Encoding]::ASCII)
}
finally {
    $rsa.Dispose()
}

Write-Host ''
Write-Host 'Archivos generados:'
Write-Host " - Clave privada: $keyPath"
Write-Host " - CSR PKCS#10 : $csrPath"
Write-Host ''
Write-Host 'Siguiente paso en WSASS:'
Write-Host "  1. En 'Nombre simbolico del DN' usa: $safeAlias"
Write-Host "  2. Pega el contenido completo de: $csrPath"
Write-Host "  3. Presiona 'Crear DN y obtener certificado'"
Write-Host "  4. Guarda el resultado PEM en un archivo .crt en la misma carpeta"
