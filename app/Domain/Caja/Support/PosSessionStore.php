<?php

namespace App\Domain\Caja\Support;

use App\Domain\Catalogo\Models\StockSucursal;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Catalogo\Support\CatalogoManager;
use App\Domain\Core\Models\Sucursal;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\Ventas\Models\PlanCuotas;
use App\Domain\Ventas\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PosSessionStore
{
    public const string CART_KEY = 'pos_cart';
    public const string PAYMENTS_KEY = 'pos_payments';
    public const string CONFIRM_TOKEN_KEY = 'pos_confirm_token';
    public const string FISCAL_DRAFT_KEY = 'pos_fiscal_draft';
    public const string CLOSE_SUMMARY_KEY = 'pos_caja_cierre_resumen';

    public const array PAYMENT_TYPES = [
        'CONTADO' => 'Contado',
        'DEBITO' => 'Debito',
        'CREDITO' => 'Credito',
        'TRANSFERENCIA' => 'Transferencia',
        'QR' => 'QR',
        'CUENTA_CORRIENTE' => 'Cuenta corriente',
    ];

    public function __construct(
        protected CatalogoManager $catalogoManager,
    ) {
    }

    public function ensureConfirmToken(Request $request): string
    {
        $token = (string) $request->session()->get(self::CONFIRM_TOKEN_KEY, '');

        if ($token === '') {
            $token = $this->refreshConfirmToken($request);
        }

        return $token;
    }

    public function refreshConfirmToken(Request $request): string
    {
        $token = (string) str()->uuid();
        $request->session()->put(self::CONFIRM_TOKEN_KEY, $token);

        return $token;
    }

    public function pullCloseSummary(Request $request): ?array
    {
        return $request->session()->pull(self::CLOSE_SUMMARY_KEY);
    }

    public function storeCloseSummary(Request $request, array $summary): void
    {
        $request->session()->put(self::CLOSE_SUMMARY_KEY, $summary);
    }

    public function cart(Request $request): array
    {
        return $request->session()->get(self::CART_KEY, []);
    }

    public function payments(Request $request): array
    {
        return $request->session()->get(self::PAYMENTS_KEY, []);
    }

    public function saveCart(Request $request, array $cart): void
    {
        $request->session()->put(self::CART_KEY, $cart);
    }

    public function savePayments(Request $request, array $payments): void
    {
        $request->session()->put(self::PAYMENTS_KEY, $payments);
    }

    public function clearPosState(Request $request): void
    {
        $this->saveCart($request, []);
        $this->savePayments($request, []);
    }

    public function fiscalDraft(Request $request, array $defaults = []): array
    {
        $base = $this->normalizeFiscalDraft($defaults);
        $stored = $request->session()->get(self::FISCAL_DRAFT_KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        return $this->normalizeFiscalDraft([
            ...$base,
            ...$stored,
        ]);
    }

    public function saveFiscalDraft(Request $request, array $draft, array $defaults = []): array
    {
        $stored = $this->normalizeFiscalDraft([
            ...$this->fiscalDraft($request, $defaults),
            ...$draft,
        ]);

        $request->session()->put(self::FISCAL_DRAFT_KEY, $stored);

        return $stored;
    }

    public function prepareFiscalDraftForNextSale(Request $request, array $defaults = []): array
    {
        $current = $this->fiscalDraft($request, $defaults);
        $base = $this->normalizeFiscalDraft($defaults);

        $next = $this->normalizeFiscalDraft([
            ...$base,
            'fiscal_receptor_doc_tipo' => $current['fiscal_receptor_doc_tipo'],
            'fiscal_receptor_doc_nro' => $current['fiscal_receptor_doc_nro'],
            'fiscal_receptor_nombre' => $current['fiscal_receptor_nombre'],
            'fiscal_receptor_domicilio' => $current['fiscal_receptor_domicilio'],
            'fiscal_receptor_condicion_iva' => $current['fiscal_receptor_condicion_iva'],
        ]);

        $request->session()->put(self::FISCAL_DRAFT_KEY, $next);

        return $next;
    }

    public function paymentTypeOptions(): array
    {
        return self::PAYMENT_TYPES;
    }

    public function defaultPayment(): array
    {
        return [
            'tipo' => 'CONTADO',
            'monto' => '0.00',
            'tarjeta' => '',
            'plan_id' => '',
            'cuotas' => 1,
            'recargo_pct' => '0.00',
            'referencia' => '',
            'cc_cliente_id' => '',
        ];
    }

    public function searchRows(string $query, Sucursal $branch): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $variants = Variante::query()
            ->with(['producto', 'atributos.atributo', 'atributos.valor'])
            ->where('activo', true)
            ->whereHas('producto', fn ($builder) => $builder->where('activo', true))
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('sku', 'like', "%{$query}%")
                    ->orWhere('codigo_barras', 'like', "%{$query}%")
                    ->orWhereHas('producto', fn ($productQuery) => $productQuery->where('nombre', 'like', "%{$query}%"));
            })
            ->orderBy('sku')
            ->limit(30)
            ->get();

        return $this->mapVariantRows($variants, $branch);
    }

    public function exactScanVariant(string $query): ?Variante
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        $matches = Variante::query()
            ->with('producto')
            ->where('activo', true)
            ->whereHas('producto', fn ($builder) => $builder->where('activo', true))
            ->where(function ($builder) use ($query): void {
                $builder->where('sku', $query)->orWhere('codigo_barras', $query);
            })
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function addVariant(Request $request, Sucursal $branch, Variante $variant, bool $allowWithoutStock): void
    {
        $this->guardVariant($variant);

        $cart = $this->cart($request);
        $key = (string) $variant->id;
        $currentQty = (int) ($cart[$key]['qty'] ?? 0);
        $stock = $this->availableStock($branch, $variant->id);

        if (! $allowWithoutStock && $currentQty + 1 > $stock) {
            throw new DomainException(
                $stock > 0
                    ? "Stock insuficiente. Disponible: {$stock} en {$branch->nombre}."
                    : "Sin stock en {$branch->nombre}.",
            );
        }

        $cart[$key] = [
            'qty' => $currentQty + 1,
            'precio' => $this->moneyString($cart[$key]['precio'] ?? $variant->precio),
        ];

        $this->saveCart($request, $cart);
    }

    public function setQty(
        Request $request,
        Sucursal $branch,
        Variante $variant,
        int $quantity,
        bool $allowWithoutStock,
    ): void {
        $cart = $this->cart($request);
        $key = (string) $variant->id;

        if (! isset($cart[$key])) {
            return;
        }

        $quantity = max($quantity, 1);
        $stock = $this->availableStock($branch, $variant->id);

        if (! $allowWithoutStock && $quantity > $stock) {
            if ($stock <= 0) {
                unset($cart[$key]);
                $this->saveCart($request, $cart);

                throw new DomainException("Sin stock en {$branch->nombre}. Se quito del carrito.");
            }

            $cart[$key]['qty'] = $stock;
            $this->saveCart($request, $cart);

            throw new DomainException("Cantidad ajustada al stock disponible: {$stock} en {$branch->nombre}.");
        }

        $cart[$key]['qty'] = $quantity;
        $this->saveCart($request, $cart);
    }

    public function setPrice(
        Request $request,
        Variante $variant,
        string $price,
        bool $allowChangePrice,
    ): void {
        if (! $allowChangePrice) {
            throw new DomainException('Esta sucursal no permite cambiar el precio de venta.');
        }

        $cart = $this->cart($request);
        $key = (string) $variant->id;

        if (! isset($cart[$key])) {
            return;
        }

        $normalized = $this->moneyString($price);

        if ($this->money($normalized)->isLessThanOrEqualTo(BigDecimal::zero())) {
            throw new DomainException('El precio debe ser mayor a cero.');
        }

        $cart[$key]['precio'] = $normalized;
        $this->saveCart($request, $cart);
    }

    public function removeVariant(Request $request, Variante $variant): void
    {
        $cart = $this->cart($request);
        unset($cart[(string) $variant->id]);
        $this->saveCart($request, $cart);
    }

    public function clearCart(Request $request): void
    {
        $this->clearPosState($request);
    }

    public function cartTotal(array $cart): string
    {
        $total = BigDecimal::zero();

        foreach ($cart as $row) {
            $qty = max((int) ($row['qty'] ?? 0), 0);

            if ($qty === 0) {
                continue;
            }

            $total = $total->plus(
                $this->money($row['precio'] ?? '0')
                    ->multipliedBy((string) $qty)
                    ->toScale(2, RoundingMode::HALF_UP),
            );
        }

        return $total->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    public function buildCartContext(Request $request, Sucursal $branch): array
    {
        $cart = $this->cart($request);
        $variantIds = collect(array_keys($cart))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $variants = Variante::query()
            ->with(['producto', 'atributos.atributo', 'atributos.valor'])
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        $stockMap = $this->stockMap($branch, $variantIds);
        $rows = [];
        $total = BigDecimal::zero();

        foreach ($cart as $variantId => $row) {
            /** @var Variante|null $variant */
            $variant = $variants->get((int) $variantId);

            if (! $variant) {
                continue;
            }

            $qty = max((int) ($row['qty'] ?? 0), 1);
            $price = $this->moneyString($row['precio'] ?? $variant->precio);
            $subtotal = $this->money($price)
                ->multipliedBy((string) $qty)
                ->toScale(2, RoundingMode::HALF_UP)
                ->__toString();
            $total = $total->plus($subtotal);

            $rows[] = [
                'variant' => $variant,
                'label' => $this->buildVariantLabel($variant),
                'qty' => $qty,
                'price' => $price,
                'subtotal' => $subtotal,
                'stock' => $stockMap[$variant->id] ?? 0,
            ];
        }

        return [
            'rows' => $rows,
            'total' => $total->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'stock_map' => $stockMap,
            'count' => collect($rows)->sum('qty'),
        ];
    }

    public function activePlans(): Collection
    {
        return PlanCuotas::query()
            ->where('activo', true)
            ->orderBy('tarjeta')
            ->orderBy('cuotas')
            ->get();
    }

    public function activeCcAccounts(): Collection
    {
        return CuentaCorriente::query()
            ->with('cliente')
            ->where('activa', true)
            ->whereHas('cliente', fn ($builder) => $builder->where('activo', true))
            ->get()
            ->sortBy(fn (CuentaCorriente $account) => ($account->cliente?->apellido ?? '').' '.($account->cliente?->nombre ?? ''))
            ->values();
    }

    public function addPayment(Request $request): void
    {
        if ($this->money($this->cartTotal($this->cart($request)))->isLessThanOrEqualTo(BigDecimal::zero())) {
            throw new DomainException('Primero agrega productos al carrito.');
        }

        $payments = $this->payments($request);
        $payments[] = $this->defaultPayment();
        $this->savePayments($request, $payments);
    }

    public function updatePayment(Request $request, int $index, array $payload): void
    {
        $payments = $this->payments($request);
        $plans = $this->activePlans();
        $accounts = $this->activeCcAccounts()->keyBy('cliente_id');

        if (! isset($payments[$index])) {
            throw new DomainException('La linea de pago no existe.');
        }

        $payment = $payments[$index];
        $type = $this->normalizePaymentType($payload['tipo'] ?? $payment['tipo'] ?? 'CONTADO');

        $payment['tipo'] = $type;
        $payment['monto'] = $this->moneyString($payload['monto'] ?? $payment['monto'] ?? '0');

        if ($this->money($payment['monto'])->isLessThan(BigDecimal::zero())) {
            throw new DomainException('El monto no puede ser menor a cero.');
        }

        $payment['referencia'] = trim((string) ($payload['referencia'] ?? ''));
        $payment['plan_id'] = '';
        $payment['tarjeta'] = '';
        $payment['cuotas'] = 1;
        $payment['recargo_pct'] = '0.00';
        $payment['cc_cliente_id'] = '';

        if ($type === 'CREDITO') {
            $plan = null;
            $planId = (int) ($payload['plan_id'] ?? 0);

            if ($planId > 0) {
                $plan = $plans->firstWhere('id', $planId);
            }

            if ($plan) {
                $payment['plan_id'] = (string) $plan->id;
                $payment['tarjeta'] = $plan->tarjeta;
                $payment['cuotas'] = (int) $plan->cuotas;
                $payment['recargo_pct'] = $this->moneyString($plan->recargo_pct);
            }
        }

        if ($type === 'CUENTA_CORRIENTE') {
            $account = $accounts->get((int) ($payload['cc_cliente_id'] ?? 0));

            if ($account?->cliente) {
                $payment['cc_cliente_id'] = (string) $account->cliente_id;
                $payment['referencia'] = "{$account->cliente->apellido}, {$account->cliente->nombre} - {$account->cliente->dni}";
            }
        }

        $payments[$index] = $payment;
        $preview = $this->buildPaymentsSummary($payments, $this->cartTotal($this->cart($request)), $plans, $accounts);

        if ($this->money($preview['saldo'])->isLessThan(BigDecimal::zero())) {
            throw new DomainException('El saldo no puede ser menor a cero. Ajusta el monto del pago.');
        }

        $this->savePayments($request, $payments);
    }

    public function deletePayment(Request $request, int $index): void
    {
        $payments = $this->payments($request);

        if (isset($payments[$index])) {
            array_splice($payments, $index, 1);
            $this->savePayments($request, $payments);
        }
    }

    public function clearPayments(Request $request): void
    {
        $this->savePayments($request, []);
    }

    public function buildPaymentsContext(Request $request, string $totalBase): array
    {
        $payments = $this->payments($request);
        $plans = $this->activePlans();
        $accounts = $this->activeCcAccounts()->keyBy('cliente_id');
        
        return $this->buildPaymentsSummary($payments, $totalBase, $plans, $accounts);
    }

    protected function buildPaymentsSummary(
        array $payments,
        string $totalBase,
        ?Collection $plans = null,
        ?Collection $accounts = null,
    ): array {
        $plans = $plans ?? $this->activePlans();
        $accounts = $accounts ?? $this->activeCcAccounts()->keyBy('cliente_id');
        $rows = [];
        $recargos = BigDecimal::zero();
        $pagado = BigDecimal::zero();

        foreach ($payments as $index => $payment) {
            $type = $this->normalizePaymentType($payment['tipo'] ?? 'CONTADO');
            $amount = $this->moneyString($payment['monto'] ?? '0');
            $plan = null;
            $clientAccount = null;
            $recargoPct = '0.00';

            if ($type === 'CREDITO' && ($payment['plan_id'] ?? '') !== '') {
                $plan = $plans->firstWhere('id', (int) $payment['plan_id']);
            }

            if ($plan) {
                $recargoPct = $this->moneyString($plan->recargo_pct);
                $payment['tarjeta'] = $plan->tarjeta;
                $payment['cuotas'] = (int) $plan->cuotas;
            } else {
                $payment['tarjeta'] = '';
                $payment['cuotas'] = 1;
            }

            if ($type === 'CUENTA_CORRIENTE' && ($payment['cc_cliente_id'] ?? '') !== '') {
                $clientAccount = $accounts->get((int) $payment['cc_cliente_id']);
            }

            $recargoAmount = $type === 'CREDITO'
                ? $this->percentageAmount($amount, $recargoPct)
                : '0.00';
            $lineTotal = $type === 'CREDITO'
                ? $this->addMoney($amount, $recargoAmount)
                : $amount;

            $recargos = $recargos->plus($recargoAmount);
            $pagado = $pagado->plus($lineTotal);

            $rows[] = [
                'index' => $index,
                'tipo' => $type,
                'monto' => $amount,
                'referencia' => (string) ($payment['referencia'] ?? ''),
                'plan_id' => (string) ($payment['plan_id'] ?? ''),
                'tarjeta' => (string) ($payment['tarjeta'] ?? ''),
                'cuotas' => (int) ($payment['cuotas'] ?? 1),
                'recargo_pct' => $recargoPct,
                'recargo_monto' => $recargoAmount,
                'line_total' => $lineTotal,
                'cc_cliente_id' => (string) ($payment['cc_cliente_id'] ?? ''),
                'cc_name' => $clientAccount?->cliente?->nombre_completo ?? '',
                'cc_ok' => $clientAccount !== null,
                'cc_saldo' => $clientAccount ? $clientAccount->saldo() : '0.00',
            ];
        }

        $totalCobrar = $this->addMoney($totalBase, $recargos->toScale(2, RoundingMode::HALF_UP)->__toString());
        $saldo = $this->money($totalCobrar)
            ->minus($pagado)
            ->toScale(2, RoundingMode::HALF_UP)
            ->__toString();

        return [
            'rows' => $rows,
            'plans' => $plans,
            'accounts' => $accounts->values(),
            'types' => $this->paymentTypeOptions(),
            'total_base' => $this->moneyString($totalBase),
            'recargos' => $recargos->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'total_cobrar' => $totalCobrar,
            'pagado' => $pagado->toScale(2, RoundingMode::HALF_UP)->__toString(),
            'saldo' => $saldo,
        ];
    }

    public function validateReadyToConfirm(
        Request $request,
        Sucursal $branch,
        bool $allowWithoutStock,
    ): array {
        $cart = $this->buildCartContext($request, $branch);

        if ($cart['rows'] === []) {
            throw new DomainException('El carrito esta vacio.');
        }

        foreach ($cart['rows'] as $row) {
            if (! $allowWithoutStock && $row['qty'] > $row['stock']) {
                throw new DomainException(
                    "Stock insuficiente para {$row['label']}. Disponible: {$row['stock']} en {$branch->nombre}.",
                );
            }
        }

        $payments = $this->buildPaymentsContext($request, $cart['total']);

        if ($payments['rows'] === []) {
            throw new DomainException('No hay pagos cargados.');
        }

        foreach ($payments['rows'] as $payment) {
            if ($this->money($payment['monto'])->isLessThanOrEqualTo(BigDecimal::zero())) {
                throw new DomainException('Todos los pagos deben tener un monto mayor a cero.');
            }

            if ($payment['tipo'] === 'CUENTA_CORRIENTE' && ! $payment['cc_ok']) {
                throw new DomainException('Cuenta corriente: selecciona un cliente con cuenta activa.');
            }
        }

        if (! $this->money($payments['saldo'])->isZero()) {
            throw new DomainException(
                "Pagos incompletos. Total a cobrar $ {$payments['total_cobrar']} - Pagado $ {$payments['pagado']}.",
            );
        }

        return [
            'items' => count($cart['rows']),
            'payments' => count($payments['rows']),
            'total_base' => $payments['total_base'],
            'total_cobrar' => $payments['total_cobrar'],
        ];
    }

    protected function mapVariantRows(Collection $variants, Sucursal $branch): Collection
    {
        $stockMap = $this->stockMap($branch, $variants->pluck('id')->all());

        return $variants->map(function (Variante $variant) use ($stockMap): array {
            return [
                'variant' => $variant,
                'label' => $this->buildVariantLabel($variant),
                'stock' => $stockMap[$variant->id] ?? 0,
                'price' => $this->moneyString($variant->precio),
            ];
        });
    }

    protected function stockMap(Sucursal $branch, array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        return StockSucursal::query()
            ->where('sucursal_id', $branch->id)
            ->whereIn('variante_id', $variantIds)
            ->pluck('cantidad', 'variante_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    protected function availableStock(Sucursal $branch, int $variantId): int
    {
        return (int) (
            StockSucursal::query()
                ->where('sucursal_id', $branch->id)
                ->where('variante_id', $variantId)
                ->value('cantidad') ?? 0
        );
    }

    protected function buildVariantLabel(Variante $variant): string
    {
        [$talle, $color] = $this->catalogoManager->extractTalleColor($variant);
        $parts = array_values(array_filter([
            trim((string) $variant->producto?->nombre),
            trim((string) $color),
            trim((string) $talle),
        ]));

        return $parts !== [] ? implode(' - ', $parts) : ($variant->sku ?: 'Variante');
    }

    protected function guardVariant(Variante $variant): void
    {
        if (! $variant->activo || ! $variant->producto?->activo) {
            throw new DomainException('La variante seleccionada no esta disponible para vender.');
        }
    }

    protected function normalizePaymentType(mixed $value): string
    {
        $type = strtoupper(trim((string) $value));

        return array_key_exists($type, self::PAYMENT_TYPES) ? $type : 'CONTADO';
    }

    protected function money(mixed $value): BigDecimal
    {
        $string = trim((string) ($value ?? '0'));

        if ($string === '') {
            return BigDecimal::zero()->toScale(2, RoundingMode::HALF_UP);
        }

        $string = str_replace(['$', ' '], '', $string);

        if (str_contains($string, ',') && str_contains($string, '.')) {
            $string = str_replace('.', '', $string);
            $string = str_replace(',', '.', $string);
        } elseif (str_contains($string, ',')) {
            $string = str_replace(',', '.', $string);
        }

        $string = preg_replace('/[^0-9\.\-]/', '', $string) ?: '0';

        try {
            return BigDecimal::of($string)->toScale(2, RoundingMode::HALF_UP);
        } catch (\Throwable) {
            return BigDecimal::zero()->toScale(2, RoundingMode::HALF_UP);
        }
    }

    protected function moneyString(mixed $value): string
    {
        return $this->money($value)->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    protected function normalizeFiscalDraft(array $draft): array
    {
        $docType = strtoupper(trim((string) ($draft['fiscal_receptor_doc_tipo'] ?? '')));
        $vatCondition = strtoupper(trim((string) ($draft['fiscal_receptor_condicion_iva'] ?? '')));
        $receiverName = trim((string) ($draft['fiscal_receptor_nombre'] ?? ''));

        if ($docType === '') {
            $docType = 'CONSUMIDOR_FINAL';
        }

        if ($vatCondition === '') {
            $vatCondition = 'CONSUMIDOR_FINAL';
        }

        if ($receiverName === '' && $docType === 'CONSUMIDOR_FINAL') {
            $receiverName = 'Consumidor Final';
        }

        return [
            'accion_fiscal' => Venta::normalizeFiscalAction((string) ($draft['accion_fiscal'] ?? '')),
            'referencia_comprobante_externo' => trim((string) ($draft['referencia_comprobante_externo'] ?? '')),
            'fiscal_receptor_doc_tipo' => $docType,
            'fiscal_receptor_doc_nro' => preg_replace('/\D+/', '', (string) ($draft['fiscal_receptor_doc_nro'] ?? '')) ?: '',
            'fiscal_receptor_nombre' => $receiverName,
            'fiscal_receptor_domicilio' => trim((string) ($draft['fiscal_receptor_domicilio'] ?? '')),
            'fiscal_receptor_condicion_iva' => $vatCondition,
        ];
    }

    protected function addMoney(mixed $left, mixed $right): string
    {
        return $this->money($left)
            ->plus($this->money($right))
            ->toScale(2, RoundingMode::HALF_UP)
            ->__toString();
    }

    protected function percentageAmount(mixed $amount, mixed $percentage): string
    {
        return $this->money($amount)
            ->multipliedBy($this->money($percentage))
            ->dividedBy('100', 2, RoundingMode::HALF_UP)
            ->toScale(2, RoundingMode::HALF_UP)
            ->__toString();
    }
}
