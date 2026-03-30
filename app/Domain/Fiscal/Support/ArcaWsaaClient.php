<?php

namespace App\Domain\Fiscal\Support;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ArcaWsaaClient
{
    public function __construct(
        protected ArcaSoapTransport $soapTransport,
        protected ArcaCredentialManager $arcaCredentialManager,
    ) {
    }

    public function accessTicket(string $environment, ?string $serviceId = null): array
    {
        $serviceId ??= (string) config('fiscal.arca.service_id', 'wsfe');
        $cachePath = $this->cachePath($environment, $serviceId);

        if (File::exists($cachePath)) {
            $cached = json_decode((string) File::get($cachePath), true);
            $expiration = data_get($cached, 'expiration_time');

            if (
                is_array($cached)
                && is_string($expiration)
                && now()->addMinutes(2)->lt(\Carbon\CarbonImmutable::parse($expiration))
            ) {
                return [
                    'token' => (string) data_get($cached, 'token', ''),
                    'sign' => (string) data_get($cached, 'sign', ''),
                    'expiration_time' => $expiration,
                ];
            }
        }

        $loginTicketResponse = $this->requestNewAccessTicket($environment, $serviceId);

        File::ensureDirectoryExists(dirname($cachePath));
        File::put($cachePath, json_encode($loginTicketResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $loginTicketResponse;
    }

    protected function requestNewAccessTicket(string $environment, string $serviceId): array
    {
        $traXml = $this->buildLoginTicketRequest($serviceId);
        $cms = $this->signCms($traXml);
        $endpoint = (string) config("fiscal.arca.wsaa.{$this->environmentKey($environment)}.endpoint");
        $namespace = $this->wsaaNamespace($endpoint);
        $response = $this->soapTransport->call(
            $endpoint,
            '',
            '<loginCms xmlns="'.$this->xml($namespace).'"><in0>'.$this->xml($cms).'</in0></loginCms>',
            (int) config('fiscal.arca.timeout_seconds', 20),
        );

        return $this->parseLoginCmsResponse($response);
    }

    protected function buildLoginTicketRequest(string $serviceId): string
    {
        $now = now();
        $uniqueId = (string) $now->timestamp;
        $generationTime = $now->copy()->subMinutes(2)->format('c');
        $expirationTime = $now->copy()->addHours(12)->format('c');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<loginTicketRequest version="1.0">
    <header>
        <uniqueId>{$uniqueId}</uniqueId>
        <generationTime>{$generationTime}</generationTime>
        <expirationTime>{$expirationTime}</expirationTime>
    </header>
    <service>{$serviceId}</service>
</loginTicketRequest>
XML;
    }

    protected function signCms(string $traXml): string
    {
        $certificatePath = $this->resolvedCertificatePath();
        $privateKeyPath = $this->resolvedPrivateKeyPath();
        $passphrase = (string) config('fiscal.arca.private_key_passphrase', '');
        $inputPath = tempnam(sys_get_temp_dir(), 'arca-tra-');
        $outputPath = tempnam(sys_get_temp_dir(), 'arca-cms-');

        if ($inputPath === false || $outputPath === false) {
            throw new RuntimeException('No se pudieron preparar los archivos temporales para firmar el CMS.');
        }

        File::put($inputPath, $traXml);
        $pkcs7Flags = \PKCS7_BINARY;

        if (defined('PKCS7_NOSMIMECAP')) {
            $pkcs7Flags |= (int) constant('PKCS7_NOSMIMECAP');
        }

        $signed = openssl_pkcs7_sign(
            $inputPath,
            $outputPath,
            'file://'.$certificatePath,
            ['file://'.$privateKeyPath, $passphrase],
            [],
            $pkcs7Flags,
        );

        if (! $signed) {
            @unlink($inputPath);
            @unlink($outputPath);

            throw new RuntimeException('No se pudo firmar el TRA con el certificado configurado.');
        }

        $cms = (string) File::get($outputPath);

        @unlink($inputPath);
        @unlink($outputPath);

        $parts = preg_split("/\R\R/", $cms, 2);
        $body = $parts[1] ?? $cms;

        $body = preg_replace('/-----BEGIN (CMS|PKCS7)-----/', '', $body);
        $body = preg_replace('/-----END (CMS|PKCS7)-----/', '', (string) $body);
        $body = preg_replace('/\s+/', '', (string) $body);

        if ($body === '') {
            throw new RuntimeException('No se pudo extraer el CMS firmado para WSAA.');
        }

        return $body;
    }

    protected function parseLoginCmsResponse(string $response): array
    {
        $dom = new DOMDocument();

        if (! @ $dom->loadXML($response)) {
            throw new RuntimeException('WSAA devolvió una respuesta XML inválida.');
        }

        $xpath = new DOMXPath($dom);
        $node = $xpath->query("//*[local-name()='loginCmsReturn']")->item(0);

        if (! $node) {
            throw new RuntimeException('WSAA no devolvió el loginCmsReturn esperado.');
        }

        $ticketXml = trim($node->textContent);
        $ticketDom = new DOMDocument();

        if (! @ $ticketDom->loadXML($ticketXml)) {
            throw new RuntimeException('No se pudo interpretar el Ticket de Acceso devuelto por WSAA.');
        }

        $ticketXpath = new DOMXPath($ticketDom);
        $token = trim((string) $ticketXpath->evaluate("string(//*[local-name()='token'])"));
        $sign = trim((string) $ticketXpath->evaluate("string(//*[local-name()='sign'])"));
        $expiration = trim((string) $ticketXpath->evaluate("string(//*[local-name()='expirationTime'])"));

        if ($token === '' || $sign === '' || $expiration === '') {
            throw new RuntimeException('El Ticket de Acceso de WSAA vino incompleto.');
        }

        return [
            'token' => $token,
            'sign' => $sign,
            'expiration_time' => $expiration,
            'raw_response_xml' => $ticketXml,
        ];
    }

    protected function cachePath(string $environment, string $serviceId): string
    {
        $cacheDir = (string) config('fiscal.arca.ta_cache_dir', 'app/fiscal');
        $basePath = storage_path($cacheDir);
        $representedCuit = preg_replace('/\D+/', '', $this->arcaCredentialManager->resolvedRepresentedCuit()) ?: 'default';

        return $basePath.DIRECTORY_SEPARATOR."ta-{$this->environmentKey($environment)}-{$serviceId}-{$representedCuit}.json";
    }

    protected function resolvedCertificatePath(): string
    {
        $path = trim($this->arcaCredentialManager->resolvedCertificatePath());

        if ($path === '') {
            throw new RuntimeException('Configura ARCA_CERTIFICATE_PATH para emitir facturas electrónicas.');
        }

        $resolved = $this->absolutePath($path);

        if (! File::exists($resolved)) {
            throw new RuntimeException('No se encontró el certificado configurado para ARCA.');
        }

        return $resolved;
    }

    protected function resolvedPrivateKeyPath(): string
    {
        $path = trim($this->arcaCredentialManager->resolvedPrivateKeyPath());

        if ($path === '') {
            throw new RuntimeException('Configura ARCA_PRIVATE_KEY_PATH para emitir facturas electrónicas.');
        }

        $resolved = $this->absolutePath($path);

        if (! File::exists($resolved)) {
            throw new RuntimeException('No se encontró la clave privada configurada para ARCA.');
        }

        return $resolved;
    }

    protected function environmentKey(string $environment): string
    {
        return strtoupper(trim($environment)) === 'PRODUCCION' ? 'produccion' : 'homologacion';
    }

    protected function wsaaNamespace(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        $host = $parts['host'] ?? 'wsaahomo.afip.gov.ar';
        $path = $parts['path'] ?? '/ws/services/LoginCms';

        return 'https://'.$host.$path;
    }

    protected function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected function absolutePath(string $path): string
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\\\\\\\|\\/)/', $path) === 1
            ? $path
            : base_path($path);
    }
}
