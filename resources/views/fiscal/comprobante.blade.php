<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documento->descripcion_completa }}</title>
    <style>
        :root { --line:#d7dee8; --text:#182032; --muted:#5d6b7d; --bg:#edf3f8; --paper:#fff; --accent:#20334d; --accent-soft:#eff4f9; }
        * { box-sizing:border-box; }
        body { margin:0; background:linear-gradient(180deg,#f8fbfd 0%,var(--bg) 100%); color:var(--text); font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif; }
        .page { padding:28px; display:flex; justify-content:center; }
        .sheet { width:min(920px,100%); background:var(--paper); border:1px solid var(--line); border-radius:28px; box-shadow:0 24px 60px rgba(15,23,42,.14); overflow:hidden; }
        .sheet-head { padding:26px 28px 18px; background:linear-gradient(135deg,#20334d 0%,#33455e 100%); color:#f8fafc; }
        .sheet-grid { display:grid; grid-template-columns:1.15fr .85fr; gap:20px; }
        .brand-title { font-size:31px; font-weight:900; line-height:1.05; }
        .brand-copy { margin-top:8px; color:rgba(226,232,240,.8); line-height:1.45; font-size:13px; }
        .doc-box { padding:18px; border:1px solid rgba(255,255,255,.14); border-radius:22px; background:rgba(255,255,255,.08); }
        .doc-type { font-size:30px; font-weight:900; line-height:1; }
        .doc-meta { margin-top:8px; display:grid; gap:6px; font-size:13px; }
        .sheet-body { padding:24px 28px 28px; }
        .cards { display:grid; gap:16px; grid-template-columns:repeat(3,minmax(0,1fr)); }
        .card { padding:16px 18px; border:1px solid var(--line); border-radius:20px; background:#fff; }
        .card-label { font-size:11px; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); font-weight:800; }
        .card-value { margin-top:8px; font-size:24px; font-weight:900; }
        .panel-grid { margin-top:18px; display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .panel-title { font-size:13px; letter-spacing:.08em; text-transform:uppercase; font-weight:900; color:var(--muted); }
        .kv { margin-top:12px; display:grid; gap:8px; }
        .kv-row { display:flex; justify-content:space-between; gap:16px; font-size:14px; }
        .kv-row span:first-child { color:var(--muted); }
        .table-shell { margin-top:18px; border:1px solid var(--line); border-radius:22px; overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px 14px; border-bottom:1px solid #eef2f7; text-align:left; font-size:14px; }
        th { background:var(--accent-soft); color:var(--muted); font-size:12px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
        tbody tr:last-child td { border-bottom:none; }
        .right { text-align:right; }
        .footer-grid { margin-top:18px; display:grid; grid-template-columns:1fr 280px; gap:18px; align-items:start; }
        .status-badge { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px; border:1px solid #c7d4e3; background:#f8fbfd; font-size:12px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; }
        .status-badge.is-authorized { color:#0f766e; border-color:#b8e0dc; background:#ecfdf5; }
        .status-badge.is-pending { color:#9a6700; border-color:#f1d9a7; background:#fff9eb; }
        .status-badge.is-rejected { color:#b42318; border-color:#f1c0bb; background:#fff5f4; }
        .qr-shell { padding:18px; border:1px solid var(--line); border-radius:22px; background:#fbfdff; }
        .qr-box { width:152px; height:152px; border:1px dashed #c6d2e0; border-radius:20px; display:flex; align-items:center; justify-content:center; background:#fff; }
        .qr-copy { margin-top:10px; font-size:12px; line-height:1.45; color:var(--muted); word-break:break-word; }
        .actions { padding:14px 28px 24px; display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--line); background:#fafcff; }
        .btn { border:1px solid var(--accent); border-radius:14px; background:#fff; padding:10px 14px; font-size:13px; font-weight:800; cursor:pointer; }
        .btn.primary { background:var(--accent); color:#fff; }
        @media (max-width:760px) { .page { padding:12px; } .sheet-grid,.cards,.panel-grid,.footer-grid { grid-template-columns:1fr; } .sheet-head,.sheet-body,.actions { padding-left:16px; padding-right:16px; } }
        @media print { body { background:#fff; } .page { padding:0; } .sheet { width:100%; border:none; border-radius:0; box-shadow:none; } .actions { display:none !important; } }
    </style>
</head>
<body>
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
    $statusClass = match ($documento->estado) {
        'AUTORIZADO' => 'is-authorized',
        'RECHAZADO' => 'is-rejected',
        default => 'is-pending',
    };
@endphp
    <div class="page">
        <div class="sheet">
            <div class="sheet-head">
                <div class="sheet-grid">
                    <div>
                        <div class="brand-title">{{ $empresa['razon_social'] ?: $empresa['nombre'] }}</div>
                        <div class="brand-copy">
                            {{ $empresa['direccion'] ?: 'Domicilio no informado' }}<br>
                            CUIT {{ $empresa['cuit'] ?: 'No informado' }} · {{ $empresa['condicion_fiscal_label'] }}
                        </div>
                    </div>
                    <div class="doc-box">
                        <div class="doc-type">{{ $documento->descripcion_completa }}</div>
                        <div class="doc-meta">
                            <div><strong>Número:</strong> {{ $documento->numero_completo ?: 'Sin asignar' }}</div>
                            <div><strong>Emisión:</strong> {{ $documento->fecha_emision?->format('d/m/Y') ?: '-' }}</div>
                            <div><strong>Punto de venta:</strong> {{ $documento->punto_venta ?: '-' }}</div>
                            <div><strong>CAE:</strong> {{ $documento->cae ?: 'Pendiente' }}</div>
                            <div><strong>Vto. CAE:</strong> {{ $documento->cae_vto?->format('d/m/Y') ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sheet-body">
                <div class="cards">
                    <div class="card">
                        <div class="card-label">Estado fiscal</div>
                        <div class="card-value"><span class="status-badge {{ $statusClass }}">{{ $documento->estado_label }}</span></div>
                    </div>
                    <div class="card">
                        <div class="card-label">Venta asociada</div>
                        <div class="card-value">{{ $venta?->codigo_sucursal ?: ('#'.$venta?->id) }}</div>
                    </div>
                    <div class="card">
                        <div class="card-label">Importe total</div>
                        <div class="card-value">{{ $money($documento->importe_total) }}</div>
                    </div>
                </div>

                <div class="panel-grid">
                    <section class="card">
                        <div class="panel-title">Emisor</div>
                        <div class="kv">
                            <div class="kv-row"><span>Razón social</span><strong>{{ $empresa['razon_social'] ?: $empresa['nombre'] }}</strong></div>
                            <div class="kv-row"><span>CUIT</span><strong>{{ $empresa['cuit'] ?: '-' }}</strong></div>
                            <div class="kv-row"><span>Condición fiscal</span><strong>{{ $empresa['condicion_fiscal_label'] }}</strong></div>
                            <div class="kv-row"><span>Sucursal</span><strong>{{ $venta?->sucursal?->nombre ?: '-' }}</strong></div>
                        </div>
                    </section>
                    <section class="card">
                        <div class="panel-title">Receptor</div>
                        <div class="kv">
                            <div class="kv-row"><span>Nombre</span><strong>{{ $documento->receptor_nombre ?: 'Consumidor final' }}</strong></div>
                            <div class="kv-row"><span>Documento</span><strong>{{ $documento->doc_nro_receptor ?: '-' }}</strong></div>
                            <div class="kv-row"><span>Tipo doc.</span><strong>{{ $documento->doc_tipo_receptor ?: '-' }}</strong></div>
                            <div class="kv-row"><span>Domicilio</span><strong>{{ $documento->receptor_domicilio ?: 'No informado' }}</strong></div>
                        </div>
                    </section>
                </div>

                <div class="table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="right">Cant.</th>
                                <th class="right">Precio</th>
                                <th class="right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>{{ $item->nombre_fiscal }}</td>
                                    <td class="right">{{ $item->cantidad }}</td>
                                    <td class="right">{{ $money($item->precio_unitario) }}</td>
                                    <td class="right">{{ $money($item->subtotal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">Sin ítems registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="footer-grid">
                    <section class="card">
                        <div class="panel-title">Totales y pagos</div>
                        <div class="kv">
                            <div class="kv-row"><span>Total ítems</span><strong>{{ $money($totalItems) }}</strong></div>
                            <div class="kv-row"><span>Neto fiscal</span><strong>{{ $money($documento->importe_neto) }}</strong></div>
                            <div class="kv-row"><span>IVA</span><strong>{{ $money($documento->importe_iva) }}</strong></div>
                            <div class="kv-row"><span>Otros tributos</span><strong>{{ $money($documento->importe_otros_tributos) }}</strong></div>
                            <div class="kv-row"><span>Total comprobante</span><strong>{{ $money($documento->importe_total) }}</strong></div>
                            <div class="kv-row"><span>Total pagado</span><strong>{{ $money($totalPagado) }}</strong></div>
                        </div>
                    </section>
                    <section class="qr-shell">
                        <div class="panel-title">QR ARCA</div>
                        <div class="qr-box"><canvas id="fiscal_qr_canvas" width="152" height="152"></canvas></div>
                        <div class="qr-copy">
                            @if ($documento->qr_url)
                                {{ $documento->qr_url }}
                            @elseif ($documento->estado === 'AUTORIZADO')
                                El comprobante fue autorizado, pero todavía no se generó la URL del QR.
                            @else
                                El QR se mostrará cuando el comprobante quede autorizado.
                            @endif
                        </div>
                    </section>
                </div>
            </div>

            <div class="actions">
                <button class="btn" onclick="window.close()">Cerrar</button>
                <button class="btn primary" onclick="window.print()">Imprimir</button>
            </div>
        </div>
    </div>

    @if ($documento->qr_url)
        <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
        <script>
            function triggerPrint() {
                if (@json($autoPrint)) {
                    window.print();
                }
            }

            window.addEventListener('load', function () {
                const canvas = document.getElementById('fiscal_qr_canvas');

                if (!canvas || typeof QRCode === 'undefined') {
                    triggerPrint();
                    return;
                }

                QRCode.toCanvas(canvas, @json($documento->qr_url), { width: 152, margin: 0 }, function () {
                    triggerPrint();
                });
            });
        </script>
    @elseif ($autoPrint)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
