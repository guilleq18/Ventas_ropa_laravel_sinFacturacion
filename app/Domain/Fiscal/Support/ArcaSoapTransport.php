<?php

namespace App\Domain\Fiscal\Support;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Utils;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ArcaSoapTransport
{
    public function call(string $endpoint, string $soapAction, string $body, int $timeoutSeconds = 20): string
    {
        $verifySsl = (bool) config('fiscal.arca.verify_ssl', true);
        $configuredCaInfo = trim((string) config('fiscal.arca.ca_info', ''));
        $resolvedCaInfo = $this->resolvedCaInfoPath($configuredCaInfo);
        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        {$body}
    </soap:Body>
</soap:Envelope>
XML;

        $ch = curl_init($endpoint);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "'.$soapAction.'"',
                'Content-Length: '.strlen($envelope),
            ],
            CURLOPT_POSTFIELDS => $envelope,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ]);

        if ($verifySsl && $resolvedCaInfo !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $resolvedCaInfo);
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException(
                $this->formatCurlErrorMessage($curlError, $verifySsl, $configuredCaInfo, $resolvedCaInfo),
            );
        }

        if ($httpCode >= 400) {
            $faultMessage = $this->extractFaultMessage($response);
            throw new RuntimeException(
                $faultMessage !== null
                    ? "SOAP {$httpCode}: {$faultMessage}"
                    : "SOAP {$httpCode}: el servicio devolvió un error HTTP.",
            );
        }

        return $response;
    }

    protected function extractFaultMessage(string $response): ?string
    {
        $dom = new DOMDocument();

        if (! @ $dom->loadXML($response)) {
            return null;
        }

        $xpath = new DOMXPath($dom);
        $node = $xpath->query("//*[local-name()='faultstring']")->item(0);

        if (! $node) {
            $node = $xpath->query("//*[local-name()='faultcode']")->item(0);
        }

        return $node ? trim($node->textContent) : null;
    }

    protected function resolvedCaInfoPath(string $configuredCaInfo): ?string
    {
        foreach ($this->caInfoCandidates($configuredCaInfo) as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $resolved = $this->absolutePath($candidate);

            if (File::exists($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    protected function caInfoCandidates(string $configuredCaInfo): array
    {
        $candidates = [];

        if ($configuredCaInfo !== '') {
            $candidates[] = $configuredCaInfo;
        }

        $candidates[] = 'resources/certs/cacert.pem';

        foreach (['openssl.cafile', 'curl.cainfo'] as $iniKey) {
            $value = trim((string) ini_get($iniKey));

            if ($value !== '') {
                $candidates[] = $value;
            }
        }

        try {
            $candidates[] = Utils::defaultCaBundle();
        } catch (RuntimeException) {
            // Si el runtime no tiene CA bundle del sistema, seguimos con los candidatos locales.
        }

        return array_values(array_unique($candidates));
    }

    protected function formatCurlErrorMessage(
        string $curlError,
        bool $verifySsl,
        string $configuredCaInfo,
        ?string $resolvedCaInfo,
    ): string {
        $message = $curlError !== '' ? $curlError : 'No se obtuvo respuesta del servicio SOAP.';

        if (
            ! $verifySsl
            || stripos($message, 'SSL certificate problem') === false
            || $resolvedCaInfo !== null
        ) {
            return $message;
        }

        if ($configuredCaInfo !== '') {
            return $message.' Verifica que el archivo configurado en ARCA_CAINFO exista y sea un CA bundle valido.';
        }

        return $message.' El runtime de PHP no encontro un CA bundle para validar SSL. El sistema busca uno en resources/certs/cacert.pem o en ARCA_CAINFO.';
    }

    protected function absolutePath(string $path): string
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\\\\\\\|\\/)/', $path) === 1
            ? $path
            : base_path($path);
    }
}
