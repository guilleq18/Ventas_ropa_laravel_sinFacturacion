<?php

namespace App\Http\Controllers\Caja;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Caja\Support\CajaManager;
use App\Domain\Caja\Support\PosSessionStore;
use App\Domain\Caja\Support\VentaTicketViewBuilder;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Fiscal\Support\FiscalConfigManager;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Support\VentaConfirmationService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Fiscal\FiscalMath;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CajaController extends Controller
{
    public function __construct(
        protected CajaManager $cajaManager,
        protected PosSessionStore $posSessionStore,
        protected VentaConfirmationService $ventaConfirmationService,
        protected AdminSettingsManager $settingsManager,
        protected FiscalConfigManager $fiscalConfigManager,
        protected VentaTicketViewBuilder $ticketViewBuilder,
    ) {
    }

    public function index(Request $request): View
    {
        $setupError = null;
        $branch = null;
        $cashState = [
            'session' => null,
            'is_open' => false,
            'can_sell' => false,
            'opened_by_other' => false,
            'message' => 'Caja no disponible.',
        ];
        $searchRows = collect();
        $cart = [
            'rows' => [],
            'total' => '0.00',
            'count' => 0,
            'stock_map' => [],
        ];
        $payments = [
            'rows' => [],
            'plans' => collect(),
            'accounts' => collect(),
            'types' => $this->posSessionStore->paymentTypeOptions(),
            'total_base' => '0.00',
            'recargos' => '0.00',
            'total_cobrar' => '0.00',
            'pagado' => '0.00',
            'saldo' => '0.00',
        ];
        $company = $this->buildCompanyData();
        $fiscalItems = FiscalMath::desglosarMontoFinalGravadoConIva('0.00');
        $lastSale = null;
        $lastSaleView = null;
        $showLastSaleModal = false;
        $fiscalUi = $this->fiscalConfigManager->branchUi(null);
        $fiscalDraft = $this->defaultFiscalDraft($fiscalUi);

        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $cashState = $this->cajaManager->buildState($request->user(), $branch);
            $searchRows = $this->posSessionStore->searchRows((string) $request->query('q', ''), $branch);
            $cart = $this->posSessionStore->buildCartContext($request, $branch);
            $payments = $this->posSessionStore->buildPaymentsContext($request, $cart['total']);
            $cart = $this->decorateCartContext($cart);
            $payments = $this->decoratePaymentsContext($payments);
            $company = $this->buildCompanyData();
            $fiscalItems = FiscalMath::desglosarMontoFinalGravadoConIva($payments['total_base']);
            $lastSaleId = (int) $request->session()->get('pos_last_sale_id', 0);
            $lastSaleModalId = (int) $request->session()->pull('pos_last_sale_modal_id', 0);

            if ($lastSaleId > 0) {
                $lastSale = Venta::query()->find($lastSaleId);

                if ($lastSale) {
                    $lastSaleView = $this->ticketViewBuilder->build($lastSale);
                    $showLastSaleModal = $lastSale->id === $lastSaleModalId;
                }
            }

            $fiscalUi = $this->fiscalConfigManager->branchUi($branch);
            $fiscalDraft = $this->posSessionStore->fiscalDraft($request, $this->defaultFiscalDraft($fiscalUi));
        } catch (DomainException $exception) {
            $setupError = $exception->getMessage();
        }

        return view('caja.pos', [
            'branch' => $branch,
            'cashState' => $cashState,
            'setupError' => $setupError,
            'confirmToken' => $this->posSessionStore->ensureConfirmToken($request),
            'searchQuery' => trim((string) $request->query('q', '')),
            'searchRows' => $searchRows,
            'cart' => $cart,
            'payments' => $payments,
            'company' => $company,
            'fiscalItems' => $fiscalItems,
            'activeModal' => $this->resolveActiveModal((string) $request->query('modal', '')),
            'canViewAdminPanel' => $this->canViewAdminPanel($request->user()),
            'closeSummary' => $this->posSessionStore->pullCloseSummary($request),
            'allowChangePrice' => $branch ? $this->cajaManager->allowChangePrice($branch) : false,
            'allowSellWithoutStock' => $branch ? $this->cajaManager->allowSellWithoutStock($branch) : false,
            'lastSale' => $lastSale,
            'lastSaleView' => $lastSaleView,
            'showLastSaleModal' => $showLastSaleModal,
            'fiscalUi' => $fiscalUi,
            'fiscalDraft' => $fiscalDraft,
        ]);
    }

    public function search(Request $request): View|RedirectResponse
    {
        if ($request->header('HX-Request') !== 'true') {
            return redirect()->route('caja.pos', [
                'q' => trim((string) $request->query('q', '')),
                'modal' => 'buscar',
            ]);
        }

        $query = trim((string) $request->query('q', ''));

        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $cashState = $this->cajaManager->buildState($request->user(), $branch);

            return view('caja.partials.results', [
                'branch' => $branch,
                'query' => $query,
                'results' => $this->posSessionStore->searchRows($query, $branch),
                'searchError' => null,
                'canOperate' => $cashState['can_sell'],
                'allowSellWithoutStock' => $this->cajaManager->allowSellWithoutStock($branch),
            ]);
        } catch (DomainException $exception) {
            return response()
                ->view('caja.partials.results', [
                    'branch' => null,
                    'query' => $query,
                    'results' => collect(),
                    'searchError' => $exception->getMessage(),
                    'canOperate' => false,
                    'allowSellWithoutStock' => false,
                ], 422);
        }
    }

    public function open(Request $request): RedirectResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->open($request->user(), $branch);
            $this->posSessionStore->refreshConfirmToken($request);

            return $this->redirectToPos($request)
                ->with('success', "Caja abierta para {$branch->nombre}.");
        } catch (DomainException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function close(Request $request): RedirectResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $result = $this->cajaManager->close($request->user(), $branch);
            $this->posSessionStore->clearPosState($request);
            $this->posSessionStore->refreshConfirmToken($request);
            $this->posSessionStore->storeCloseSummary($request, $result['summary']);

            return $this->redirectToPos($request)
                ->with('success', "Caja cerrada para {$branch->nombre}.");
        } catch (DomainException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function scan(Request $request): RedirectResponse
    {
        $query = trim((string) $request->input('q', ''));

        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);

            if ($query === '') {
                throw new DomainException('Ingresa un codigo para escanear.');
            }

            $variant = $this->posSessionStore->exactScanVariant($query);

            if (! $variant) {
                return $this->redirectToPos($request, [
                    'q' => $query,
                    'modal' => 'buscar',
                ])
                    ->with('error', 'No hubo coincidencia exacta. Revisa los resultados manuales.');
            }

            $this->posSessionStore->addVariant(
                $request,
                $branch,
                $variant,
                $this->cajaManager->allowSellWithoutStock($branch),
            );

            return $this->redirectToPos($request)
                ->with('success', "Producto agregado: {$variant->sku}.");
        } catch (DomainException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function addVariant(Request $request, Variante $variante): RedirectResponse|JsonResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->addVariant(
                $request,
                $branch,
                $variante->loadMissing('producto'),
                $this->cajaManager->allowSellWithoutStock($branch),
            );

            if ($this->isAsyncUiRequest($request)) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Item agregado al carrito.',
                ]);
            }

            return $this->redirectToPos($request)->with('success', 'Item agregado al carrito.');
        } catch (DomainException $exception) {
            if ($this->isAsyncUiRequest($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function setQuantity(Request $request, Variante $variante): RedirectResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->setQty(
                $request,
                $branch,
                $variante,
                (int) $request->input('qty', 1),
                $this->cajaManager->allowSellWithoutStock($branch),
            );

            return $this->redirectToPos($request);
        } catch (DomainException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function setPrice(Request $request, Variante $variante): RedirectResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->setPrice(
                $request,
                $variante,
                (string) $request->input('precio', ''),
                $this->cajaManager->allowChangePrice($branch),
            );

            return $this->redirectToPos($request)->with('success', 'Precio actualizado.');
        } catch (DomainException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function removeVariant(Request $request, Variante $variante): RedirectResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->removeVariant($request, $variante);

            return $this->redirectToPos($request);
        } catch (DomainException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function clearCart(Request $request): RedirectResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->clearCart($request);

            return $this->redirectToPos($request)->with('success', 'Carrito y pagos temporales limpiados.');
        } catch (DomainException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function addPayment(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->addPayment($request);

            if ($this->isAsyncUiRequest($request)) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Se agrego una nueva linea de pago.',
                ]);
            }

            return $this->redirectToPos($request)->with('success', 'Se agrego una nueva linea de pago.');
        } catch (DomainException $exception) {
            if ($this->isAsyncUiRequest($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function updatePayment(Request $request, int $index): RedirectResponse|JsonResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->updatePayment($request, $index, $request->only([
                'tipo',
                'monto',
                'referencia',
                'plan_id',
                'cc_cliente_id',
            ]));

            if ($this->isAsyncUiRequest($request)) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Pago actualizado.',
                ]);
            }

            return $this->redirectToPos($request)->with('success', 'Pago actualizado.');
        } catch (DomainException $exception) {
            if ($this->isAsyncUiRequest($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function deletePayment(Request $request, int $index): RedirectResponse|JsonResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->deletePayment($request, $index);

            if ($this->isAsyncUiRequest($request)) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Pago eliminado.',
                ]);
            }

            return $this->redirectToPos($request);
        } catch (DomainException $exception) {
            if ($this->isAsyncUiRequest($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function clearPayments(Request $request): RedirectResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $this->posSessionStore->clearPayments($request);

            return $this->redirectToPos($request)->with('success', 'Pagos temporales limpiados.');
        } catch (DomainException $exception) {
            return $this->redirectWithError($request, $exception->getMessage());
        }
    }

    public function confirmSale(Request $request): RedirectResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $fiscalUi = $this->fiscalConfigManager->branchUi($branch);

            $sentToken = trim((string) $request->input('confirm_token', ''));
            $sessionToken = $this->posSessionStore->ensureConfirmToken($request);

            if ($sentToken === '' || $sentToken !== $sessionToken) {
                throw new DomainException('Operacion ya procesada o token invalido. Recarga el POS e intenta nuevamente.');
            }

            $cart = $this->posSessionStore->buildCartContext($request, $branch);
            $payments = $this->posSessionStore->buildPaymentsContext($request, $cart['total']);
            $sale = $this->ventaConfirmationService->confirmFromPos(
                $request->user(),
                $branch,
                $cart['rows'],
                $payments['rows'],
                [
                    'accion_fiscal' => (string) $request->input('accion_fiscal', ''),
                    'referencia_comprobante_externo' => (string) $request->input('referencia_comprobante_externo', ''),
                    'fiscal_observacion' => (string) $request->input('fiscal_observacion', ''),
                    'fiscal_receptor_doc_tipo' => (string) $request->input('fiscal_receptor_doc_tipo', ''),
                    'fiscal_receptor_doc_nro' => (string) $request->input('fiscal_receptor_doc_nro', ''),
                    'fiscal_receptor_nombre' => (string) $request->input('fiscal_receptor_nombre', ''),
                    'fiscal_receptor_domicilio' => (string) $request->input('fiscal_receptor_domicilio', ''),
                    'fiscal_receptor_condicion_iva' => (string) $request->input('fiscal_receptor_condicion_iva', ''),
                ],
            );

            $this->posSessionStore->clearPosState($request);
            $this->posSessionStore->prepareFiscalDraftForNextSale($request, $this->defaultFiscalDraft($fiscalUi));
            $this->posSessionStore->refreshConfirmToken($request);
            $request->session()->put('pos_last_sale_id', $sale->id);
            $request->session()->put('pos_last_sale_modal_id', $sale->id);

            return redirect()
                ->route('caja.pos')
                ->with(
                    'success',
                    $this->saleSuccessMessage($sale),
                );
        } catch (DomainException $exception) {
            return $this->redirectToPos($request)
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    public function saveFiscalDraft(Request $request): JsonResponse
    {
        try {
            $branch = $this->cajaManager->resolveSucursalForUser($request->user());
            $this->cajaManager->assertOperable($request->user(), $branch);
            $fiscalUi = $this->fiscalConfigManager->branchUi($branch);

            $draft = $this->posSessionStore->saveFiscalDraft(
                $request,
                [
                    'accion_fiscal' => (string) $request->input('accion_fiscal', ''),
                    'referencia_comprobante_externo' => (string) $request->input('referencia_comprobante_externo', ''),
                    'fiscal_receptor_doc_tipo' => (string) $request->input('fiscal_receptor_doc_tipo', ''),
                    'fiscal_receptor_doc_nro' => (string) $request->input('fiscal_receptor_doc_nro', ''),
                    'fiscal_receptor_nombre' => (string) $request->input('fiscal_receptor_nombre', ''),
                    'fiscal_receptor_domicilio' => (string) $request->input('fiscal_receptor_domicilio', ''),
                    'fiscal_receptor_condicion_iva' => (string) $request->input('fiscal_receptor_condicion_iva', ''),
                ],
                $this->defaultFiscalDraft($fiscalUi),
            );

            return response()->json([
                'ok' => true,
                'message' => 'Datos fiscales guardados para reutilizar en la siguiente venta.',
                'draft' => $draft,
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    protected function redirectWithError(Request $request, string $message): RedirectResponse
    {
        return $this->redirectToPos($request)->with('error', $message);
    }

    protected function redirectToPos(Request $request, array $params = []): RedirectResponse
    {
        return redirect()->route('caja.pos', $this->posRouteParameters($request, $params));
    }

    protected function posRouteParameters(Request $request, array $params = []): array
    {
        $modal = $this->resolveActiveModal((string) ($params['modal'] ?? $request->input('return_modal', '')));
        $query = trim((string) ($params['q'] ?? $request->input('return_q', '')));

        unset($params['modal'], $params['q']);

        if ($modal) {
            $params['modal'] = $modal;
        }

        if ($query !== '') {
            $params['q'] = $query;
        }

        return $params;
    }

    protected function resolveActiveModal(string $value): ?string
    {
        $value = trim($value);

        return in_array($value, ['buscar', 'pagos'], true) ? $value : null;
    }

    protected function defaultFiscalDraft(array $fiscalUi): array
    {
        return [
            'accion_fiscal' => (string) ($fiscalUi['default_action'] ?? Venta::ACCION_FISCAL_SOLO_REGISTRO),
            'referencia_comprobante_externo' => '',
            ...((array) ($fiscalUi['default_receiver'] ?? [])),
        ];
    }

    protected function isAsyncUiRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    protected function canViewAdminPanel(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('admin_panel.view_usuarioperfil')
            || $user->can('admin_panel.manage_users')
            || $user->can('admin_panel.view_reportes');
    }

    protected function buildCompanyData(): array
    {
        $company = $this->settingsManager->getCompanyData();
        $conditionCode = $this->settingsManager->normalizeFiscalCondition((string) ($company['condicion_fiscal'] ?? ''));

        return [
            ...$company,
            'nombre' => trim((string) ($company['nombre'] ?? '')) ?: 'VGC',
            'condicion_fiscal_code' => $conditionCode,
            'condicion_fiscal_label' => AdminSettingsManager::FISCAL_CHOICES[$conditionCode] ?? 'Monotributista',
            'es_responsable_inscripto' => $conditionCode === AdminSettingsManager::FISCAL_RESPONSABLE_INSCRIPTO,
        ];
    }

    protected function decorateCartContext(array $cart): array
    {
        $rows = collect($cart['rows'] ?? [])
            ->map(function (array $row): array {
                $subtotal = (string) ($row['subtotal'] ?? '0.00');

                return [
                    ...$row,
                    'subtotal_bruto' => $subtotal,
                    'descuento' => '0.00',
                    'recargo' => (string) ($row['recargo'] ?? '0.00'),
                    'fiscal_subtotal' => FiscalMath::desglosarMontoFinalGravadoConIva($subtotal),
                ];
            })
            ->all();

        $cart['rows'] = $rows;

        return $cart;
    }

    protected function decoratePaymentsContext(array $payments): array
    {
        $typeLabels = $payments['types'] ?? [];
        $payments['brands'] = collect($payments['plans'] ?? [])
            ->pluck('tarjeta')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $payments['rows'] = collect($payments['rows'] ?? [])
            ->map(function (array $row) use ($typeLabels): array {
                return [
                    ...$row,
                    'tipo_label' => $typeLabels[$row['tipo']] ?? ucfirst(strtolower(str_replace('_', ' ', $row['tipo']))),
                ];
            })
            ->all();

        return $payments;
    }

    protected function saleSuccessMessage(Venta $sale): string
    {
        $base = "Venta confirmada: {$sale->codigo_sucursal} por $ {$sale->total}.";

        return match ($sale->accion_fiscal) {
            Venta::ACCION_FISCAL_FACTURA_EXTERNA_REFERENCIADA => $base.' Se registró una referencia de comprobante externo.',
            Venta::ACCION_FISCAL_FACTURA_ELECTRONICA => $this->electronicSaleSuccessMessage($sale, $base),
            default => $base.' Se guardó como registro interno.',
        };
    }

    protected function electronicSaleSuccessMessage(Venta $sale, string $base): string
    {
        $document = $sale->comprobantePrincipal;

        if ($sale->estado_fiscal === Venta::ESTADO_FISCAL_AUTORIZADO && $document) {
            $number = $document->numero_completo ?: 's/n';

            return $base." Factura autorizada: {$document->descripcion_completa} {$number}, CAE {$document->cae}.";
        }

        if ($sale->estado_fiscal === Venta::ESTADO_FISCAL_RECHAZADO) {
            return $base.' ARCA rechazó el comprobante electrónico; la venta quedó registrada para revisión.';
        }

        return $base.' La venta quedó pendiente de reproceso fiscal.';
    }
}
