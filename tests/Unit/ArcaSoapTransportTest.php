<?php

namespace Tests\Unit;

use App\Domain\Fiscal\Support\ArcaSoapTransport;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ArcaSoapTransportTest extends TestCase
{
    public function test_it_uses_the_project_ca_bundle_when_no_custom_path_is_configured(): void
    {
        $transport = new class extends ArcaSoapTransport
        {
            public function exposeResolvedCaInfoPath(string $configuredCaInfo): ?string
            {
                return $this->resolvedCaInfoPath($configuredCaInfo);
            }
        };

        $this->assertSame(
            base_path('resources/certs/cacert.pem'),
            $transport->exposeResolvedCaInfoPath(''),
        );
    }

    public function test_it_accepts_an_absolute_ca_bundle_path(): void
    {
        $transport = new class extends ArcaSoapTransport
        {
            public function exposeResolvedCaInfoPath(string $configuredCaInfo): ?string
            {
                return $this->resolvedCaInfoPath($configuredCaInfo);
            }
        };

        $bundlePath = storage_path('framework/testing/arca-ca-test.pem');
        File::ensureDirectoryExists(dirname($bundlePath));
        File::put($bundlePath, "-----BEGIN CERTIFICATE-----\nTEST\n-----END CERTIFICATE-----\n");

        $this->assertSame($bundlePath, $transport->exposeResolvedCaInfoPath($bundlePath));
    }

    public function test_it_explains_when_ssl_validation_has_no_ca_bundle_available(): void
    {
        $transport = new class extends ArcaSoapTransport
        {
            public function exposeFormatCurlErrorMessage(
                string $curlError,
                bool $verifySsl,
                string $configuredCaInfo,
                ?string $resolvedCaInfo,
            ): string {
                return $this->formatCurlErrorMessage($curlError, $verifySsl, $configuredCaInfo, $resolvedCaInfo);
            }
        };

        $message = $transport->exposeFormatCurlErrorMessage(
            'SSL certificate problem: self-signed certificate in certificate chain',
            true,
            '',
            null,
        );

        $this->assertStringContainsString('resources/certs/cacert.pem', $message);
        $this->assertStringContainsString('ARCA_CAINFO', $message);
    }
}
