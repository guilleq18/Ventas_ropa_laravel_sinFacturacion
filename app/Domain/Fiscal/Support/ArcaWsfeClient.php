<?php

namespace App\Domain\Fiscal\Support;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class ArcaWsfeClient
{
    public function __construct(
        protected ArcaSoapTransport $soapTransport,
    ) {
    }

    public function getLastAuthorized(string $environment, array $auth, int $pointOfSale, int $receiptType): array
    {
        $body = <<<XML
<FECompUltimoAutorizado xmlns="http://ar.gov.afip.dif.FEV1/">
    {$this->authXml($auth)}
    <PtoVta>{$pointOfSale}</PtoVta>
    <CbteTipo>{$receiptType}</CbteTipo>
</FECompUltimoAutorizado>
XML;

        $response = $this->soapTransport->call(
            $this->endpoint($environment),
            'http://ar.gov.afip.dif.FEV1/FECompUltimoAutorizado',
            $body,
            (int) config('fiscal.arca.timeout_seconds', 20),
        );

        $dom = $this->dom($response, 'WSFEv1 devolvió una respuesta inválida al consultar el último comprobante.');
        $xpath = new DOMXPath($dom);

        return [
            'numero' => (int) $xpath->evaluate("string(//*[local-name()='CbteNro'])"),
            'errors' => $this->parseList($xpath, 'Err'),
            'events' => $this->parseList($xpath, 'Evt'),
            'raw_xml' => $response,
        ];
    }

    public function dummy(string $environment): array
    {
        $response = $this->soapTransport->call(
            $this->endpoint($environment),
            'http://ar.gov.afip.dif.FEV1/FEDummy',
            '<FEDummy xmlns="http://ar.gov.afip.dif.FEV1/" />',
            (int) config('fiscal.arca.timeout_seconds', 20),
        );

        $dom = $this->dom($response, 'WSFEv1 devolvió una respuesta inválida al consultar FEDummy.');
        $xpath = new DOMXPath($dom);

        return [
            'app_server' => trim((string) $xpath->evaluate("string(//*[local-name()='AppServer'])")),
            'db_server' => trim((string) $xpath->evaluate("string(//*[local-name()='DbServer'])")),
            'auth_server' => trim((string) $xpath->evaluate("string(//*[local-name()='AuthServer'])")),
            'raw_xml' => $response,
        ];
    }

    public function requestCae(string $environment, array $auth, array $request): array
    {
        $detail = $request['detail'];
        $ivaXml = '';

        if (($detail['Iva'] ?? []) !== []) {
            $ivaRows = collect($detail['Iva'])
                ->map(fn (array $row) => <<<XML
<AlicIva>
    <Id>{$row['Id']}</Id>
    <BaseImp>{$row['BaseImp']}</BaseImp>
    <Importe>{$row['Importe']}</Importe>
</AlicIva>
XML)
                ->implode('');

            $ivaXml = "<Iva>{$ivaRows}</Iva>";
        }

        $condicionIvaXml = array_key_exists('CondicionIVAReceptorId', $detail)
            && $detail['CondicionIVAReceptorId'] !== null
            ? '<CondicionIVAReceptorId>'.$detail['CondicionIVAReceptorId'].'</CondicionIVAReceptorId>'
            : '';

        $body = <<<XML
<FECAESolicitar xmlns="http://ar.gov.afip.dif.FEV1/">
    {$this->authXml($auth)}
    <FeCAEReq>
        <FeCabReq>
            <CantReg>1</CantReg>
            <PtoVta>{$request['point_of_sale']}</PtoVta>
            <CbteTipo>{$request['receipt_type']}</CbteTipo>
        </FeCabReq>
        <FeDetReq>
            <FECAEDetRequest>
                <Concepto>{$detail['Concepto']}</Concepto>
                <DocTipo>{$detail['DocTipo']}</DocTipo>
                <DocNro>{$detail['DocNro']}</DocNro>
                <CbteDesde>{$detail['CbteDesde']}</CbteDesde>
                <CbteHasta>{$detail['CbteHasta']}</CbteHasta>
                <CbteFch>{$detail['CbteFch']}</CbteFch>
                <ImpTotal>{$detail['ImpTotal']}</ImpTotal>
                <ImpTotConc>{$detail['ImpTotConc']}</ImpTotConc>
                <ImpNeto>{$detail['ImpNeto']}</ImpNeto>
                <ImpOpEx>{$detail['ImpOpEx']}</ImpOpEx>
                <ImpTrib>{$detail['ImpTrib']}</ImpTrib>
                <ImpIVA>{$detail['ImpIVA']}</ImpIVA>
                <MonId>{$detail['MonId']}</MonId>
                <MonCotiz>{$detail['MonCotiz']}</MonCotiz>
                {$condicionIvaXml}
                {$ivaXml}
            </FECAEDetRequest>
        </FeDetReq>
    </FeCAEReq>
</FECAESolicitar>
XML;

        $response = $this->soapTransport->call(
            $this->endpoint($environment),
            'http://ar.gov.afip.dif.FEV1/FECAESolicitar',
            $body,
            (int) config('fiscal.arca.timeout_seconds', 20),
        );

        $dom = $this->dom($response, 'WSFEv1 devolvió una respuesta inválida al solicitar CAE.');
        $xpath = new DOMXPath($dom);

        return [
            'cab_resultado' => trim((string) $xpath->evaluate("string(//*[local-name()='FeCabResp']/*[local-name()='Resultado'])")),
            'cab_reproceso' => trim((string) $xpath->evaluate("string(//*[local-name()='FeCabResp']/*[local-name()='Reproceso'])")),
            'fch_proceso' => trim((string) $xpath->evaluate("string(//*[local-name()='FeCabResp']/*[local-name()='FchProceso'])")),
            'detalle_resultado' => trim((string) $xpath->evaluate("string((//*[local-name()='FECAEDetResponse'])[1]/*[local-name()='Resultado'])")),
            'cbte_desde' => (int) $xpath->evaluate("string((//*[local-name()='FECAEDetResponse'])[1]/*[local-name()='CbteDesde'])"),
            'cbte_hasta' => (int) $xpath->evaluate("string((//*[local-name()='FECAEDetResponse'])[1]/*[local-name()='CbteHasta'])"),
            'cae' => trim((string) $xpath->evaluate("string((//*[local-name()='FECAEDetResponse'])[1]/*[local-name()='CAE'])")),
            'cae_vto' => trim((string) $xpath->evaluate("string((//*[local-name()='FECAEDetResponse'])[1]/*[local-name()='CAEFchVto'])")),
            'observations' => $this->parseList($xpath, 'Obs'),
            'errors' => $this->parseList($xpath, 'Err'),
            'events' => $this->parseList($xpath, 'Evt'),
            'raw_xml' => $response,
        ];
    }

    protected function parseList(DOMXPath $xpath, string $nodeName): array
    {
        $rows = [];

        foreach ($xpath->query("//*[local-name()='{$nodeName}']") as $node) {
            $rows[] = [
                'code' => (int) $xpath->evaluate("string(./*[local-name()='Code'])", $node),
                'message' => trim((string) $xpath->evaluate("string(./*[local-name()='Msg'])", $node)),
            ];
        }

        return $rows;
    }

    protected function authXml(array $auth): string
    {
        return <<<XML
<Auth>
    <Token>{$this->xml((string) ($auth['token'] ?? ''))}</Token>
    <Sign>{$this->xml((string) ($auth['sign'] ?? ''))}</Sign>
    <Cuit>{$auth['cuit']}</Cuit>
</Auth>
XML;
    }

    protected function endpoint(string $environment): string
    {
        $key = strtoupper(trim($environment)) === 'PRODUCCION' ? 'produccion' : 'homologacion';

        return (string) config("fiscal.arca.wsfe.{$key}.endpoint");
    }

    protected function dom(string $response, string $message): DOMDocument
    {
        $dom = new DOMDocument();

        if (! @ $dom->loadXML($response)) {
            throw new RuntimeException($message);
        }

        return $dom;
    }

    protected function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
