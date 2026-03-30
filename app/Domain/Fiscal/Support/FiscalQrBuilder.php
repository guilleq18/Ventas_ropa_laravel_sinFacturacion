<?php

namespace App\Domain\Fiscal\Support;

class FiscalQrBuilder
{
    public function build(array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $encoded = base64_encode($json ?: '{}');

        return [
            'payload' => $payload,
            'url' => 'https://www.arca.gob.ar/fe/qr/?p='.$encoded,
        ];
    }
}
