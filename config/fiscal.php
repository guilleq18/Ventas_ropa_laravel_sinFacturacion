<?php

$defaultGateway = env('APP_ENV') === 'testing' ? 'fake' : 'arca';

return [
    'gateway' => env('FISCAL_GATEWAY', $defaultGateway),

    'consumer_final_identification_threshold' => (float) env('FISCAL_CF_IDENTIFICATION_THRESHOLD', 10000000),

    'arca' => [
        'service_id' => env('ARCA_WSAA_SERVICE_ID', 'wsfe'),
        'represented_cuit' => env('ARCA_REPRESENTED_CUIT'),
        'certificate_path' => env('ARCA_CERTIFICATE_PATH'),
        'private_key_path' => env('ARCA_PRIVATE_KEY_PATH'),
        'private_key_passphrase' => env('ARCA_PRIVATE_KEY_PASSPHRASE'),
        'ta_cache_dir' => env('ARCA_TA_CACHE_DIR', 'app/fiscal'),
        'timeout_seconds' => (int) env('ARCA_TIMEOUT_SECONDS', 20),
        'verify_ssl' => filter_var(env('ARCA_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
        'ca_info' => env('ARCA_CAINFO'),
        'wsaa' => [
            'homologacion' => [
                'wsdl' => env('ARCA_WSAA_WSDL_HOMOLOGACION', 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms?WSDL'),
                'endpoint' => env('ARCA_WSAA_ENDPOINT_HOMOLOGACION', 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms'),
            ],
            'produccion' => [
                'wsdl' => env('ARCA_WSAA_WSDL_PRODUCCION', 'https://wsaa.afip.gov.ar/ws/services/LoginCms?WSDL'),
                'endpoint' => env('ARCA_WSAA_ENDPOINT_PRODUCCION', 'https://wsaa.afip.gov.ar/ws/services/LoginCms'),
            ],
        ],
        'wsfe' => [
            'homologacion' => [
                'wsdl' => env('ARCA_WSFE_WSDL_HOMOLOGACION', 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL'),
                'endpoint' => env('ARCA_WSFE_ENDPOINT_HOMOLOGACION', 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx'),
            ],
            'produccion' => [
                'wsdl' => env('ARCA_WSFE_WSDL_PRODUCCION', 'https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL'),
                'endpoint' => env('ARCA_WSFE_ENDPOINT_PRODUCCION', 'https://servicios1.afip.gov.ar/wsfev1/service.asmx'),
            ],
        ],
    ],
];
