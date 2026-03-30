<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $venta->codigo_sucursal }}</title>
    <style>
        :root {
            --text: #111827;
            --muted: #6b7280;
            --line: #d1d5db;
            --paper: #ffffff;
            --bg: #eef2f7;
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
        }

        .document-flag {
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b42318;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
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
    <div class="page">
        <div class="receipt">
            <div class="pad center">
                <div class="title">{{ $empresa['nombre'] }}</div>
                <div class="subtitle">TICKET</div>
                <div class="subtitle"><span class="mono">{{ $venta->codigo_sucursal }}</span> · {{ $venta->sucursal?->nombre ?? '-' }}</div>
                <div class="subtitle">{{ $venta->fecha?->format('d/m/Y H:i') ?? '-' }}</div>
                <div class="document-flag">Documento no fiscal</div>

                <div class="divider"></div>

                <div class="kv small">
                    <span class="muted">Sucursal</span>
                    <strong>{{ $venta->sucursal?->nombre ?? '-' }}</strong>
                </div>
                <div class="kv small">
                    <span class="muted">Fecha</span>
                    <strong class="mono">{{ $venta->fecha?->format('d/m/Y H:i') ?? '-' }}</strong>
                </div>
                <div class="kv small">
                    <span class="muted">Cajero</span>
                    <strong>
                        {{ $cajeroNombre }}
                        @if ($cajeroId)
                            <span class="mono muted">(#{{ $cajeroId }})</span>
                        @endif
                    </strong>
                </div>
                @if ($venta->cliente)
                    <div class="kv small">
                        <span class="muted">Cliente</span>
                        <strong>{{ $venta->cliente->nombre_completo }}@if($venta->cliente->dni) ({{ $venta->cliente->dni }})@endif</strong>
                    </div>
                @endif
                @if ($empresa['razon_social'] !== '')
                    <div class="kv small">
                        <span class="muted">Razon social</span>
                        <strong>{{ $empresa['razon_social'] }}</strong>
                    </div>
                @endif
                @if ($empresa['cuit'] !== '')
                    <div class="kv small">
                        <span class="muted">CUIT</span>
                        <strong class="mono">{{ $empresa['cuit'] }}</strong>
                    </div>
                @endif
                <div class="kv small">
                    <span class="muted">Cond. fiscal</span>
                    <strong>{{ $empresa['condicion_fiscal_label'] }}</strong>
                </div>
                @if ($empresa['direccion'] !== '')
                    <div class="kv small">
                        <span class="muted">Direccion</span>
                        <strong>{{ $empresa['direccion'] }}</strong>
                    </div>
                @endif
            </div>

            <div class="pad" style="padding-top: 0;">
                <div class="divider"></div>

                <div class="section-title">Items</div>
                @forelse ($items as $item)
                    <div class="item">
                        <div class="leftcol">
                            <div style="font-size: 12px; font-weight: 700;">{{ $item->nombre_ticket }}</div>
                            <div class="small muted">SKU: {{ $item->variante?->sku ?: '-' }}</div>
                        </div>
                        <div class="rightcol">
                            <div class="pill mono">x{{ $item->cantidad }}</div>
                            <div class="small muted mono">Unit: ${{ number_format((float) $item->precio_unitario, 2, ',', '.') }}</div>
                            <div class="mono" style="font-weight: 900;">${{ number_format((float) $item->subtotal, 2, ',', '.') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="small muted">No hay items registrados.</div>
                @endforelse

                <div class="box">
                    <div class="kv">
                        <span class="muted">Total por items</span>
                        <strong class="mono">${{ number_format((float) $totalItems, 2, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="section-title">Pagos</div>
                @forelse ($payments as $payment)
                    <div class="pay">
                        <div class="leftcol">
                            <div class="ptype">{{ $payment->tipo_ticket }}</div>

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
                                @if ($payment->recargo_monto_ticket > 0)
                                    <div class="pmeta">
                                        Base: ${{ number_format((float) $payment->monto, 2, ',', '.') }}
                                        · Recargo: ${{ number_format((float) $payment->recargo_monto_ticket, 2, ',', '.') }}
                                    </div>
                                @endif
                            @endif

                            @if ($payment->referencia)
                                <div class="pmeta">Ref: {{ $payment->referencia }}</div>
                            @endif
                        </div>
                        <div class="right mono" style="font-weight: 900; white-space: nowrap;">
                            ${{ number_format((float) $payment->total_pago_ticket, 2, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <div class="small muted">No hay pagos registrados.</div>
                @endforelse

                <div class="box">
                    <div class="kv">
                        <span class="muted">Total recargos</span>
                        <strong class="mono">${{ number_format((float) $totalRecargos, 2, ',', '.') }}</strong>
                    </div>
                    <div class="kv" style="margin-top: 4px;">
                        <span style="font-weight: 900;">TOTAL VENTA</span>
                        <span class="grand mono">${{ number_format((float) $totalFinal, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="section-title">Transparencia fiscal</div>
                <div class="box">
                    <div class="small muted" style="line-height: 1.35;">
                        @if ($empresa['es_responsable_inscripto'])
                            Regimen de Transparencia Fiscal al Consumidor (Ley 27.743)
                        @else
                            Detalle fiscal informativo para precios al consumidor.
                        @endif
                    </div>

                    <div class="kv small" style="margin-top: 8px;">
                        <span class="muted">Base de calculo</span>
                        <strong class="mono">Items: ${{ number_format((float) $totalItems, 2, ',', '.') }}</strong>
                    </div>
                    <div class="kv small">
                        <span class="muted">Precio sin impuestos nacionales</span>
                        <strong class="mono">${{ number_format((float) $fiscalItems['monto_sin_impuestos_nacionales'], 2, ',', '.') }}</strong>
                    </div>
                    <div class="kv small">
                        <span class="muted">IVA contenido</span>
                        <strong class="mono">${{ number_format((float) $fiscalItems['iva_contenido'], 2, ',', '.') }}</strong>
                    </div>
                    <div class="kv small">
                        <span class="muted">Otros imp. nac. indirectos</span>
                        <strong class="mono">${{ number_format((float) $fiscalItems['otros_impuestos_nacionales_indirectos'], 2, ',', '.') }}</strong>
                    </div>

                    @if ((float) $totalRecargos > 0)
                        <div class="small muted" style="margin-top: 8px; line-height: 1.35;">
                            El detalle fiscal se calcula sobre el total de items. Los recargos de financiacion se muestran por separado.
                        </div>
                    @endif

                    @if ($empresa['es_monotributista'])
                        <div class="small muted" style="margin-top: 8px; line-height: 1.35;">
                            Empresa configurada como Monotributista.
                        </div>
                    @endif
                </div>

                <div class="footer-note center">Gracias por su compra</div>
            </div>

            <div class="btns">
                <button class="btn" onclick="window.close()">Cerrar</button>
                <button class="btn primary" onclick="window.print()">Imprimir</button>
            </div>
        </div>
    </div>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
