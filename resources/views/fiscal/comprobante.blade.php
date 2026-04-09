<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documento->descripcion_completa }}</title>
    @vite('resources/js/fiscal-document.js')
    <style>
        :root {
            --text: #111827;
            --muted: #6b7280;
            --line: #d1d5db;
            --paper: #ffffff;
            --bg: #eef2f7;
            --accent: #111827;
            --soft: #f8fafc;
            --soft-2: #f3f4f6;
            --success-bg: #ecfdf5;
            --success-line: #a7f3d0;
            --success-text: #047857;
            --warn-bg: #fffbeb;
            --warn-line: #fde68a;
            --warn-text: #b45309;
            --danger-bg: #fef2f2;
            --danger-line: #fecaca;
            --danger-text: #b42318;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: radial-gradient(circle at top, #f8fafc 0%, var(--bg) 70%);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .page {
            display: flex;
            justify-content: center;
            padding: 24px;
        }

        .receipt {
            width: 420px;
            max-width: 95vw;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: var(--paper);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }

        .pad {
            padding: 16px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .muted {
            color: var(--muted);
        }

        .small {
            font-size: 12px;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        }

        .title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .subtitle {
            margin-top: 4px;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.35;
        }

        .doc-stamp {
            margin-top: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: linear-gradient(180deg, #fafafa 0%, #f3f4f6 100%);
            padding: 12px;
        }

        .status-banner {
            margin-top: 12px;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 10px 12px;
            text-align: left;
        }

        .status-banner-title {
            font-size: 13px;
            font-weight: 800;
            line-height: 1.3;
        }

        .status-banner-copy {
            margin-top: 4px;
            font-size: 12px;
            line-height: 1.45;
        }

        .status-banner.is-success {
            background: var(--success-bg);
            border-color: var(--success-line);
            color: var(--success-text);
        }

        .status-banner.is-warn {
            background: var(--warn-bg);
            border-color: var(--warn-line);
            color: var(--warn-text);
        }

        .status-banner.is-danger {
            background: var(--danger-bg);
            border-color: var(--danger-line);
            color: var(--danger-text);
        }

        .doc-stamp-top {
            display: flex;
            align-items: stretch;
            gap: 8px;
        }

        .doc-letter {
            flex: 0 0 58px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 7px 5px 6px;
        }

        .doc-letter-mark {
            font-size: 26px;
            font-weight: 900;
            line-height: 1;
        }

        .doc-letter-code {
            margin-top: 2px;
            font-size: 10px;
            color: var(--muted);
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .doc-main {
            flex: 1 1 auto;
            min-width: 0;
            text-align: left;
        }

        .doc-main-title {
            font-size: 18px;
            font-weight: 900;
            line-height: 1.1;
        }

        .doc-main-meta {
            margin-top: 5px;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.4;
        }

        .divider {
            margin: 12px 0;
            border-top: 1px dashed var(--line);
        }

        .section-title {
            margin: 8px 0 6px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .kv {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 4px 0;
            font-size: 13px;
        }

        .kv span:first-child {
            color: var(--muted);
        }

        .kv strong:last-child {
            text-align: right;
        }

        .item,
        .pay {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .item:last-child,
        .pay:last-child {
            border-bottom: none;
        }

        .leftcol {
            flex: 1 1 auto;
            min-width: 0;
        }

        .rightcol {
            flex: 0 0 150px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 3px;
            white-space: nowrap;
        }

        .pill {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #f8fafc;
            font-size: 12px;
        }

        .ptype {
            font-size: 13px;
            font-weight: 800;
        }

        .pmeta {
            margin-top: 2px;
            font-size: 12px;
            line-height: 1.25;
            color: var(--muted);
        }

        .box {
            margin-top: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fafafa;
            padding: 12px;
        }

        .grand {
            font-size: 19px;
            font-weight: 900;
            letter-spacing: 0.03em;
        }

        .qr-panel {
            display: grid;
            gap: 12px;
            justify-items: center;
        }

        .qr-box {
            width: 148px;
            height: 148px;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .qr-box img {
            display: block;
            width: 148px;
            height: 148px;
            border-radius: 14px;
        }

        .qr-box img[hidden] {
            display: none;
        }

        .qr-note {
            font-size: 12px;
            line-height: 1.45;
            color: var(--muted);
            text-align: center;
            word-break: break-word;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 12px;
            color: var(--muted);
        }

        .btns {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 12px 16px 16px;
            border-top: 1px solid #e5e7eb;
            background: #fafafa;
        }

        .btn {
            border: 1px solid #111827;
            border-radius: 12px;
            background: #fff;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn.primary {
            background: #111827;
            color: #fff;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                padding: 0;
            }

            .receipt {
                width: 80mm;
                max-width: 80mm;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }

            .pad {
                padding: 10px 8px;
            }

            .rightcol {
                flex-basis: 135px;
            }

            .btns {
                display: none !important;
            }
        }
    </style>
</head>
<body>
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
    $documentCode = str_pad((string) ((int) ($documento->codigo_arca ?? 0)), 3, '0', STR_PAD_LEFT);
    $emissionAddress = $domicilioFiscalEmision ?: ($empresa['direccion'] ?: null);
    $statusMeta = match ($documento->estado) {
        'AUTORIZADO' => [
            'class' => 'is-success',
            'title' => 'Comprobante fiscal autorizado',
            'copy' => 'CAE otorgado por ARCA y comprobante listo para imprimir.',
        ],
        'RECHAZADO' => [
            'class' => 'is-danger',
            'title' => 'Comprobante fiscal rechazado',
            'copy' => 'Revisa el detalle fiscal antes de volver a intentar la emision.',
        ],
        default => [
            'class' => 'is-warn',
            'title' => 'Comprobante fiscal pendiente',
            'copy' => 'La autorizacion del comprobante sigue en proceso o requiere reproceso.',
        ],
    };
    $receiverDocLabels = [
        80 => 'CUIT',
        86 => 'CUIL',
        96 => 'DNI',
        99 => 'Consumidor final',
    ];
    $receiverDocLabel = $receiverDocLabels[(int) ($documento->doc_tipo_receptor ?? 0)] ?? 'Documento';
@endphp
    <div class="page">
        <div class="receipt">
            <div class="pad center">
                <div class="title">{{ $empresa['nombre'] ?: ($empresa['razon_social'] ?: 'Comercio') }}</div>
                <div class="subtitle">{{ $empresa['razon_social'] ?: $empresa['nombre'] }}</div>
                <div class="subtitle">CUIT {{ $empresa['cuit'] ?: 'No informado' }} · {{ $empresa['condicion_fiscal_label'] }}</div>

                <div class="doc-stamp">
                    <div class="doc-stamp-top">
                        <div class="doc-letter">
                            <div class="doc-letter-mark">{{ $documento->clase ?: '-' }}</div>
                            <div class="doc-letter-code">Cod. {{ $documentCode }}</div>
                        </div>
                        <div class="doc-main">
                            <div class="doc-main-title">{{ $documento->descripcion_completa }}</div>
                            <div class="doc-main-meta">
                                Nro. {{ $documento->numero_completo ?: 'Sin asignar' }}<br>
                                {{ $documento->fecha_emision?->format('d/m/Y') ?: '-' }} · Pto. vta. {{ $documento->punto_venta ?: '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="status-banner {{ $statusMeta['class'] }}">
                    <div class="status-banner-title">{{ $statusMeta['title'] }}</div>
                    <div class="status-banner-copy">{{ $statusMeta['copy'] }}</div>
                </div>
            </div>

            <div class="pad" style="padding-top: 0;">
                <div class="divider"></div>

                <div class="section-title">Datos fiscales</div>
                <div class="kv small">
                    <span>CAE</span>
                    <strong class="mono">{{ $documento->cae ?: 'Pendiente' }}</strong>
                </div>
                <div class="kv small">
                    <span>Vto. CAE</span>
                    <strong class="mono">{{ $documento->cae_vto?->format('d/m/Y') ?: '-' }}</strong>
                </div>
                <div class="kv small">
                    <span>Sucursal</span>
                    <strong>{{ $venta?->sucursal?->nombre ?: '-' }}</strong>
                </div>
                @if ($emissionAddress)
                    <div class="kv small">
                        <span>Domicilio emision</span>
                        <strong>{{ $emissionAddress }}</strong>
                    </div>
                @endif
                @if (($empresa['direccion'] ?? '') !== '' && $empresa['direccion'] !== $emissionAddress)
                    <div class="kv small">
                        <span>Domicilio comercial</span>
                        <strong>{{ $empresa['direccion'] }}</strong>
                    </div>
                @endif

                <div class="divider"></div>

                <div class="section-title">Receptor</div>
                <div class="kv small">
                    <span>Nombre</span>
                    <strong>{{ $documento->receptor_nombre ?: 'Consumidor Final' }}</strong>
                </div>
                <div class="kv small">
                    <span>{{ $receiverDocLabel }}</span>
                    <strong class="mono">{{ $documento->doc_nro_receptor ?: '-' }}</strong>
                </div>
                <div class="kv small">
                    <span>Cond. IVA</span>
                    <strong>{{ $documento->receptor_condicion_iva ?: '-' }}</strong>
                </div>
                @if ($documento->receptor_domicilio)
                    <div class="kv small">
                        <span>Domicilio</span>
                        <strong>{{ $documento->receptor_domicilio }}</strong>
                    </div>
                @endif

                <div class="divider"></div>

                <div class="section-title">Items</div>
                @forelse ($items as $item)
                    <div class="item">
                        <div class="leftcol">
                            <div style="font-size: 12px; font-weight: 700;">{{ $item->nombre_fiscal }}</div>
                            <div class="small muted">SKU: {{ $item->variante?->sku ?: '-' }}</div>
                        </div>
                        <div class="rightcol">
                            <div class="pill mono">x{{ $item->cantidad }}</div>
                            <div class="small muted mono">Unit: {{ $money($item->precio_unitario) }}</div>
                            <div class="mono" style="font-weight: 900;">{{ $money($item->subtotal) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="small muted">No hay items registrados.</div>
                @endforelse

                @if ($pagos->isNotEmpty())
                    <div class="divider"></div>

                    <div class="section-title">Pagos</div>
                    @foreach ($pagos as $payment)
                        <div class="pay">
                            <div class="leftcol">
                                <div class="ptype">{{ $payment->tipo_fiscal }}</div>

                                @if ($payment->tipo === 'CREDITO')
                                    <div class="pmeta">
                                        @if ($payment->plan)
                                            {{ $payment->plan->tarjeta }} ·
                                        @endif
                                        {{ $payment->cuotas }} cuota(s)
                                        @if ((float) $payment->recargo_pct > 0)
                                            · Recargo {{ number_format((float) $payment->recargo_pct, 2, ',', '.') }}%
                                        @endif
                                    </div>
                                @endif

                                @if ($payment->referencia)
                                    <div class="pmeta">Ref: {{ $payment->referencia }}</div>
                                @endif
                            </div>
                            <div class="right mono" style="font-weight: 900; white-space: nowrap;">
                                {{ $money($payment->total_fiscal) }}
                            </div>
                        </div>
                    @endforeach
                @endif

                <div class="box">
                    <div class="kv">
                        <span>Total por items</span>
                        <strong class="mono">{{ $money($totalItems) }}</strong>
                    </div>
                    @if ($documento->clase === 'C')
                        <div class="kv">
                            <span>Importe comprobante</span>
                            <strong class="mono">{{ $money($documento->importe_total) }}</strong>
                        </div>
                        @if ((float) ($fiscalInformativo['iva_contenido'] ?? 0) > 0)
                            <div class="kv">
                                <span>IVA contenido informativo</span>
                                <strong class="mono">{{ $money($fiscalInformativo['iva_contenido']) }}</strong>
                            </div>
                        @endif
                        <div class="small muted" style="margin-top: 6px; line-height: 1.45;">
                            En factura C el IVA no se discrimina fiscalmente en el comprobante.
                        </div>
                    @else
                        <div class="kv">
                            <span>Neto gravado</span>
                            <strong class="mono">{{ $money($documento->importe_neto) }}</strong>
                        </div>
                        <div class="kv">
                            <span>IVA</span>
                            <strong class="mono">{{ $money($documento->importe_iva) }}</strong>
                        </div>
                        <div class="kv">
                            <span>Otros tributos</span>
                            <strong class="mono">{{ $money($documento->importe_otros_tributos) }}</strong>
                        </div>
                    @endif
                    <div class="kv" style="margin-top: 4px;">
                        <span style="font-weight: 900;">TOTAL COMPROBANTE</span>
                        <span class="grand mono">{{ $money($documento->importe_total) }}</span>
                    </div>
                    @if ((float) $totalPagado > 0)
                        <div class="kv small" style="margin-top: 4px;">
                            <span>Total pagado</span>
                            <strong class="mono">{{ $money($totalPagado) }}</strong>
                        </div>
                    @endif
                </div>

                <div class="divider"></div>

                <div class="section-title">Validacion ARCA</div>
                <div class="box">
                    <div class="qr-panel">
                        <div class="qr-box">
                            <img
                                id="fiscal_qr_image"
                                alt="QR ARCA del comprobante fiscal"
                                data-qr-url="{{ $documento->qr_url ?: '' }}"
                                data-auto-print="{{ $autoPrint ? 'true' : 'false' }}"
                                hidden
                            >
                        </div>
                        <div class="qr-note">
                            @if ($documento->qr_url)
                                Escanea el QR para validar este comprobante en ARCA.
                            @elseif ($documento->estado === 'AUTORIZADO')
                                El comprobante fue autorizado, pero todavia no se genero la URL del QR.
                            @else
                                El QR se mostrara cuando el comprobante quede autorizado.
                            @endif
                        </div>
                    </div>
                </div>

                <div class="footer-note center">Conserve este comprobante</div>
            </div>

            <div class="btns">
                <button class="btn" onclick="window.close()">Cerrar</button>
                <button class="btn primary" onclick="window.print()">Imprimir</button>
            </div>
        </div>
    </div>

    @if (! $documento->qr_url && $autoPrint)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
