# Backlog Tecnico Fase 1: Facturacion Electronica Argentina

Fecha: 2026-03-30

Documento base relacionado: [facturacion-electronica-argentina.md](./facturacion-electronica-argentina.md)

## 1. Objetivo de la fase 1

Entregar un primer circuito productizable para convivir entre:

1. `SOLO_REGISTRO`
2. `FACTURA_ELECTRONICA`
3. `FACTURA_EXTERNA_REFERENCIADA`

con foco en:

1. venta minorista,
2. consumidor final,
3. factura `B` para responsable inscripto,
4. factura `C` para monotributista,
5. CAE,
6. QR,
7. comprobante fiscal imprimible,
8. ticket interno no fiscal separado.

Fuera de alcance de esta fase:

1. notas de credito,
2. notas de debito,
3. devoluciones fiscales,
4. circuito `A` complejo,
5. contingencia con `CAEA`,
6. multiples alicuotas avanzadas,
7. percepciones complejas.

## 2. Decisiones tecnicas para esta fase

### 2.1. Modelo operativo

Se mantiene `Venta` como documento comercial principal y se incorpora una capa fiscal nueva.

### 2.2. Estrategia de integracion

La integracion inicial se prepara sobre `WSAA` + `WSFEv1`.

### 2.3. Estrategia de configuracion

1. Datos comerciales basicos de empresa siguen en `AppSetting`.
2. Configuracion fiscal por sucursal pasa a tabla propia.
3. Credenciales y certificados ARCA no se administran desde UI en fase 1.
4. Certificado, clave privada y alias se cargan desde `env` o storage seguro.

### 2.4. Estrategia de UX

1. El POS pregunta la accion fiscal antes de confirmar.
2. Si se elige facturar, muestra campos del receptor fiscal.
3. Si se elige solo registro, imprime ticket no fiscal.
4. Si se elige factura externa, obliga a guardar referencia del comprobante externo.

## 3. Modelo de datos a implementar

## 3.1. Cambios sobre `ventas`

Agregar columnas en una migration nueva:

| Columna | Tipo sugerido | Motivo |
| --- | --- | --- |
| `accion_fiscal` | string(40) nullable | `SOLO_REGISTRO`, `FACTURA_ELECTRONICA`, `FACTURA_EXTERNA_REFERENCIADA` |
| `estado_fiscal` | string(40) nullable | `NO_REQUERIDO`, `PENDIENTE`, `AUTORIZADO`, `RECHAZADO`, `EXTERNO_REFERENCIADO` |
| `venta_comprobante_principal_id` | unsignedBigInteger nullable | acceso rapido al comprobante fiscal principal |
| `tiene_comprobante_fiscal` | boolean default false | filtro y reporting rapido |

Observacion:

1. `numero_sucursal` queda como numeracion comercial interna actual.
2. La numeracion fiscal no debe reutilizar ese campo.

## 3.2. Nueva tabla `sucursal_fiscal_configs`

Purpose: guardar configuracion fiscal operativa por sucursal.

Campos sugeridos:

| Columna | Tipo sugerido | Motivo |
| --- | --- | --- |
| `id` | id | PK |
| `sucursal_id` | foreign unique | relacion 1:1 |
| `modo_operacion` | string(40) | `SOLO_REGISTRO`, `FACTURAR_SI_SE_SOLICITA`, `FACTURACION_OBLIGATORIA` |
| `entorno` | string(20) | `HOMOLOGACION` o `PRODUCCION` |
| `punto_venta` | unsignedInteger nullable | punto de venta fiscal |
| `facturacion_habilitada` | boolean default false | habilitacion rapida |
| `requiere_receptor_en_todas` | boolean default false | politica adicional opcional |
| `domicilio_fiscal_emision` | string(255) nullable | si difiere de direccion comercial |
| `ultimo_synced_at` | datetime nullable | auditoria operativa |
| `created_at` | timestamp | trazabilidad |
| `updated_at` | timestamp | trazabilidad |

## 3.3. Nueva tabla `venta_comprobantes`

Purpose: representar comprobante fiscal o referencia externa asociado a la venta.

Campos sugeridos:

| Columna | Tipo sugerido | Motivo |
| --- | --- | --- |
| `id` | id | PK |
| `venta_id` | foreign indexed | relacion con venta |
| `sucursal_id` | foreign indexed | redundancia util para busqueda |
| `modo_emision` | string(40) | `ELECTRONICA_ARCA`, `EXTERNA_REFERENCIADA`, `INTERNA_NO_FISCAL` |
| `estado` | string(40) | `BORRADOR`, `PENDIENTE`, `AUTORIZADO`, `RECHAZADO`, `REFERENCIADO`, `ANULADO` |
| `tipo_comprobante` | string(20) | `FACTURA`, `NC`, `ND` |
| `clase` | string(5) nullable | `A`, `B`, `C` |
| `codigo_arca` | unsignedSmallInteger nullable | codigo oficial |
| `punto_venta` | unsignedInteger nullable | punto de venta fiscal |
| `numero_comprobante` | unsignedBigInteger nullable | correlativo fiscal |
| `fecha_emision` | datetime | fecha del comprobante |
| `moneda` | string(10) default `PES` | compatibilidad futura |
| `cotizacion_moneda` | decimal(12,6) nullable | compatibilidad futura |
| `doc_tipo_receptor` | unsignedSmallInteger nullable | codigos ARCA |
| `doc_nro_receptor` | string(20) nullable | receptor |
| `receptor_nombre` | string(160) nullable | snapshot |
| `receptor_condicion_iva` | string(40) nullable | snapshot |
| `receptor_domicilio` | string(255) nullable | snapshot |
| `importe_neto` | decimal(12,2) default 0 | transparencia fiscal |
| `importe_iva` | decimal(12,2) default 0 | transparencia fiscal |
| `importe_otros_tributos` | decimal(12,2) default 0 | transparencia fiscal |
| `importe_total` | decimal(12,2) default 0 | total fiscal |
| `cae` | string(20) nullable | autorizacion |
| `cae_vto` | date nullable | vencimiento CAE |
| `qr_payload_json` | longText nullable | auditoria QR |
| `qr_url` | text nullable | render/print |
| `referencia_externa_tipo` | string(40) nullable | Comprobantes en linea, controlador, etc |
| `referencia_externa_numero` | string(80) nullable | numero externo |
| `resultado_arca` | string(20) nullable | `A`, `R`, `O` o equivalente interno |
| `observaciones_arca_json` | longText nullable | observaciones |
| `request_payload_json` | longText nullable | auditoria |
| `response_payload_json` | longText nullable | auditoria |
| `emitido_en` | datetime nullable | timestamp real |
| `created_at` | timestamp | trazabilidad |
| `updated_at` | timestamp | trazabilidad |

Indices recomendados:

1. `unique(punto_venta, codigo_arca, numero_comprobante)` para emitidos.
2. `index(venta_id, estado)`.
3. `index(sucursal_id, fecha_emision)`.

## 3.4. Nueva tabla `venta_comprobante_eventos`

Purpose: auditoria y reprocesos.

Campos sugeridos:

1. `venta_comprobante_id`
2. `tipo_evento`
3. `descripcion`
4. `payload_json`
5. `created_at`
6. `created_by`

Eventos iniciales:

1. `INTENCION_CREADA`
2. `REQUEST_ENVIADO_ARCA`
3. `RESPONSE_RECIBIDO_ARCA`
4. `AUTORIZADO`
5. `RECHAZADO`
6. `REFERENCIA_EXTERNA_GUARDADA`
7. `REINTENTO`

## 4. Nuevos modelos y servicios

## 4.1. Modelos a crear

Rutas sugeridas:

1. `app/Domain/Fiscal/Models/SucursalFiscalConfig.php`
2. `app/Domain/Fiscal/Models/VentaComprobante.php`
3. `app/Domain/Fiscal/Models/VentaComprobanteEvento.php`

## 4.2. Servicios a crear

Rutas sugeridas:

1. `app/Domain/Fiscal/Support/FiscalConfigManager.php`
2. `app/Domain/Fiscal/Support/FiscalReceiverResolver.php`
3. `app/Domain/Fiscal/Support/FiscalDocumentBuilder.php`
4. `app/Domain/Fiscal/Support/ArcaWsaaClient.php`
5. `app/Domain/Fiscal/Support/ArcaWsfeClient.php`
6. `app/Domain/Fiscal/Support/FiscalQrBuilder.php`
7. `app/Domain/Fiscal/Support/VentaComprobanteEmitter.php`
8. `app/Domain/Fiscal/Support/VentaComprobanteViewBuilder.php`

## 4.3. Responsabilidad de cada servicio

### `FiscalConfigManager`

1. Resolver modo fiscal por sucursal.
2. Resolver punto de venta.
3. Validar si la sucursal esta lista para facturar.
4. Leer entorno y credenciales.

### `FiscalReceiverResolver`

1. Tomar input del POS.
2. Normalizar datos del receptor.
3. Inferir consumidor final.
4. Validar datos obligatorios por monto y accion fiscal.

### `FiscalDocumentBuilder`

1. Convertir `Venta` y `VentaItem` a estructura fiscal.
2. Resolver clase y codigo de comprobante.
3. Armar importes netos, IVA y total.
4. Generar payload para WSFEv1.

### `ArcaWsaaClient`

1. Obtener token y sign.
2. Manejar cache temporal de credenciales.
3. Separar homologacion y produccion.

### `ArcaWsfeClient`

1. Consultar ultimo comprobante autorizado.
2. Solicitar CAE.
3. Parsear respuesta.
4. Exponer errores u observaciones tecnicas.

### `FiscalQrBuilder`

1. Armar payload QR oficial.
2. Generar URL usable en render.

### `VentaComprobanteEmitter`

1. Orquestar el flujo fiscal.
2. Crear `venta_comprobantes`.
3. Registrar eventos.
4. Actualizar `ventas.estado_fiscal`.

### `VentaComprobanteViewBuilder`

1. Preparar la representacion fiscal imprimible.
2. Exponer CAE, vencimiento, receptor y QR.
3. Separar ticket interno de comprobante fiscal.

## 5. Archivos existentes a modificar

## 5.1. Configuracion de empresa y sucursal

### [app/Domain/Admin/Support/AdminSettingsManager.php](../app/Domain/Admin/Support/AdminSettingsManager.php)

Cambios:

1. Ampliar catalogo de condicion fiscal.
2. Exponer helper para decidir clase de comprobante por emisor.
3. Mantener datos de emisor reutilizables por capa fiscal.

### [app/Http/Requests/Admin/CompanyDataRequest.php](../app/Http/Requests/Admin/CompanyDataRequest.php)

Cambios:

1. Validar CUIT con formato consistente.
2. Preparar campos extra si se suman en fase 1:
   - email fiscal,
   - domicilio fiscal,
   - nombre fantasia opcional.

### [app/Http/Requests/Admin/SucursalRequest.php](../app/Http/Requests/Admin/SucursalRequest.php)

Cambios:

1. Mantener request comercial.
2. No cargarle responsabilidad fiscal extra.
3. Crear request aparte para config fiscal de sucursal.

### [app/Http/Controllers/AdminPanel/AdminEmpresaController.php](../app/Http/Controllers/AdminPanel/AdminEmpresaController.php)

Cambios:

1. Agregar lectura y guardado de configuracion fiscal por sucursal.
2. Exponer un tab nuevo o panel nuevo `Facturacion`.
3. Mostrar readiness de cada sucursal:
   - lista para facturar,
   - incompleta,
   - solo registro.

### [resources/views/admin-panel/empresa/index.blade.php](../resources/views/admin-panel/empresa/index.blade.php)

Cambios:

1. Sumar tab o bloque `Facturacion`.
2. Mostrar punto de venta, modo operativo y entorno por sucursal.
3. Mostrar advertencias si falta CUIT, condicion fiscal o punto de venta.

## 5.2. Flujo de POS

### [app/Http/Controllers/Caja/CajaController.php](../app/Http/Controllers/Caja/CajaController.php)

Cambios:

1. Recibir accion fiscal en `confirmSale`.
2. Recibir datos del receptor fiscal.
3. Invocar al emisor fiscal si corresponde.
4. Manejar respuesta fiscal en el modal final del POS.

### [app/Domain/Ventas/Support/VentaConfirmationService.php](../app/Domain/Ventas/Support/VentaConfirmationService.php)

Cambios:

1. Seguir confirmando venta comercial como hoy.
2. Delegar emision fiscal al nuevo `VentaComprobanteEmitter`.
3. Devolver un resultado compuesto:
   - `venta`
   - `ticket`
   - `comprobante`
   - `estado_fiscal`

### [resources/views/caja/pos.blade.php](../resources/views/caja/pos.blade.php)

Cambios:

1. Agregar selector de accion fiscal.
2. Agregar formulario corto para receptor fiscal.
3. Mostrar validaciones previas antes de confirmar.
4. Cambiar modal de venta confirmada para mostrar:
   - ticket interno
   - comprobante fiscal emitido
   - error o pendiente fiscal

### [app/Domain/Caja/Support/PosSessionStore.php](../app/Domain/Caja/Support/PosSessionStore.php)

Cambios:

1. Guardar en session el estado del formulario fiscal del POS.
2. Persistir accion fiscal temporal.
3. Persistir datos del receptor entre recargas del flujo.

## 5.3. Ticket y comprobante

### [app/Domain/Caja/Support/VentaTicketViewBuilder.php](../app/Domain/Caja/Support/VentaTicketViewBuilder.php)

Cambios:

1. Seguir generando ticket interno.
2. Agregar bandera visible de `DOCUMENTO NO FISCAL`.
3. Dejar de mezclar ticket interno con factura electronica.

### [app/Http/Controllers/Caja/TicketController.php](../app/Http/Controllers/Caja/TicketController.php)

Cambios:

1. Seguir sirviendo ticket interno.
2. Crear controlador nuevo para comprobante fiscal.

### [resources/views/caja/ticket.blade.php](../resources/views/caja/ticket.blade.php)

Cambios:

1. Marcar expresamente el ticket como no fiscal cuando corresponda.
2. Evitar mostrar CAE o QR si no existe comprobante fiscal.

Archivos nuevos sugeridos:

1. `app/Http/Controllers/Fiscal/ComprobanteController.php`
2. `resources/views/fiscal/comprobante.blade.php`

## 5.4. Admin de ventas

### [app/Http/Controllers/AdminPanel/AdminVentasController.php](../app/Http/Controllers/AdminPanel/AdminVentasController.php)

Cambios:

1. Sumar filtros por `accion_fiscal` y `estado_fiscal`.
2. Precargar comprobante principal.
3. Mostrar CAE y numero fiscal cuando exista.

### [resources/views/admin-panel/ventas/index.blade.php](../resources/views/admin-panel/ventas/index.blade.php)

Cambios:

1. Agregar columnas:
   - accion fiscal,
   - estado fiscal,
   - comprobante,
   - CAE.
2. Agregar boton `Comprobante`.

### [resources/views/admin-panel/ventas/show.blade.php](../resources/views/admin-panel/ventas/show.blade.php)

Cambios:

1. Agregar card `Estado fiscal`.
2. Mostrar receptor, tipo, clase, punto de venta, numero, CAE y QR.
3. Mostrar referencia externa si aplica.

## 5.5. Rutas

### [routes/web.php](../routes/web.php)

Cambios sugeridos:

1. Ruta para imprimir comprobante fiscal.
2. Ruta para reintentar emision.
3. Ruta para guardar config fiscal de sucursal.

Rutas tentativas:

1. `GET /fiscal/comprobantes/{ventaComprobante}`
2. `POST /admin-panel/fiscal/sucursales/{sucursal}/config`
3. `POST /admin-panel/ventas/{venta}/reintentar-facturacion`

## 6. Formulario del POS: campos concretos

La confirmacion del POS deberia enviar, como minimo:

| Campo | Uso |
| --- | --- |
| `accion_fiscal` | modo elegido |
| `receptor_doc_tipo` | consumidor final o documento concreto |
| `receptor_doc_nro` | DNI/CUIT/etc |
| `receptor_nombre` | nombre o razon social |
| `receptor_condicion_iva` | condicion fiscal del receptor |
| `receptor_domicilio` | domicilio si aplica |
| `referencia_comprobante_externo` | si la accion es externa referenciada |
| `tipo_comprobante_preferido` | opcional para forzar B/C en casos validos |

Reglas iniciales:

1. `SOLO_REGISTRO`
   - no exige receptor fiscal completo,
   - imprime ticket no fiscal.

2. `FACTURA_ELECTRONICA`
   - exige configuracion fiscal habilitada,
   - exige receptor segun validaciones,
   - intenta emitir y registrar CAE.

3. `FACTURA_EXTERNA_REFERENCIADA`
   - exige referencia externa,
   - no llama a ARCA,
   - deja venta con estado fiscal `EXTERNO_REFERENCIADO`.

## 7. Secuencia de implementacion recomendada

## Bloque A. Dominio y base

Tareas:

1. Crear migrations nuevas.
2. Crear modelos fiscales.
3. Crear relaciones Eloquent en `Venta` y `Sucursal`.
4. Agregar constantes de accion y estado fiscal.

Criterio de aceptacion:

1. Una venta puede existir sin comprobante.
2. Una venta puede tener comprobante principal.
3. Una sucursal puede estar lista o no para facturar.

## Bloque B. Configuracion fiscal de sucursal

Tareas:

1. Crear `FiscalConfigManager`.
2. Crear request y save flow de configuracion fiscal.
3. Extender pantalla de empresa/sucursales.

Criterio de aceptacion:

1. La UI muestra si la sucursal puede facturar.
2. La sucursal puede quedar en `SOLO_REGISTRO`.
3. La sucursal puede quedar en `FACTURAR_SI_SE_SOLICITA`.

## Bloque C. POS con accion fiscal

Tareas:

1. Extender session store.
2. Extender `CajaController`.
3. Extender modal de confirmacion en POS.
4. Persistir datos del receptor.

Criterio de aceptacion:

1. El usuario puede elegir como cerrar la venta.
2. Las validaciones del modo elegido se ejecutan antes de confirmar.
3. El flujo mobile sigue funcionando.

## Bloque D. Integracion ARCA fake + real

Tareas:

1. Crear interfaz para cliente fiscal.
2. Crear fake para tests.
3. Crear cliente real WSAA/WSFEv1.
4. Crear builder de QR.

Criterio de aceptacion:

1. Se puede testear la emision sin llamar a red.
2. Se puede conectar homologacion con credenciales reales.
3. La respuesta deja CAE, vto y QR persistidos.

## Bloque E. Render y admin

Tareas:

1. Crear vista fiscal imprimible.
2. Mantener ticket interno.
3. Extender admin ventas.

Criterio de aceptacion:

1. Una venta facturada tiene boton de comprobante.
2. Una venta solo registrada tiene boton de ticket no fiscal.
3. Admin puede diferenciar ambos casos de un vistazo.

## 8. Pruebas a incorporar

## 8.1. Feature tests nuevos

Archivos sugeridos:

1. `tests/Feature/Fiscal/FiscalConfigWorkflowTest.php`
2. `tests/Feature/Fiscal/FiscalPosWorkflowTest.php`
3. `tests/Feature/Fiscal/FiscalDocumentRenderTest.php`

## 8.2. Casos de prueba minimos

### Configuracion

1. una sucursal puede quedar en `SOLO_REGISTRO` sin punto de venta;
2. una sucursal no puede habilitar `FACTURAR_SI_SE_SOLICITA` sin punto de venta;
3. una sucursal no puede habilitar facturacion si faltan CUIT o condicion fiscal del emisor.

### POS

1. confirma venta en `SOLO_REGISTRO` y genera ticket no fiscal;
2. confirma venta con `FACTURA_EXTERNA_REFERENCIADA` y guarda referencia externa;
3. confirma venta con `FACTURA_ELECTRONICA` usando fake fiscal y persiste CAE;
4. si el fake devuelve rechazo, la venta queda confirmada comercialmente pero con estado fiscal `RECHAZADO` o `PENDIENTE_REPROCESO` segun la politica elegida;
5. si supera el umbral de consumidor final sin datos obligatorios, bloquea emision.

### Render

1. el ticket interno muestra leyenda no fiscal;
2. el comprobante fiscal muestra CAE y QR;
3. admin ventas lista estado fiscal y comprobante.

## 8.3. Tests existentes a extender

1. `tests/Feature/Caja/CajaPosWorkflowTest.php`
2. `tests/Feature/AdminPanel/AdminPanelWorkflowTest.php`

## 9. Dependencias tecnicas externas

## 9.1. Variables de entorno sugeridas

Agregar a `.env.example`:

1. `ARCA_WS_ENV=homologacion`
2. `ARCA_CUIT=`
3. `ARCA_CERT_PATH=`
4. `ARCA_PRIVATE_KEY_PATH=`
5. `ARCA_WSAA_SERVICE=wsfe`
6. `ARCA_WSFE_ENDPOINT_HOMOLOGACION=`
7. `ARCA_WSFE_ENDPOINT_PRODUCCION=`

## 9.2. Librerias

Evaluar necesidad de:

1. cliente SOAP nativo PHP o wrapper robusto,
2. generador QR si se decide imagen embebida,
3. helper para XML firmado si no se usa implementacion propia.

## 10. Riesgos de implementacion

1. Mezclar numeracion comercial con numeracion fiscal.
2. Tratar ticket interno como documento legal.
3. Acoplar demasiado el POS al WSFE.
4. No distinguir emision rechazada de venta comercial confirmada.
5. Cargar certificados desde UI demasiado temprano.
6. Subestimar validaciones del receptor.

## 11. Definicion de terminado de fase 1

La fase 1 se considera terminada cuando:

1. una sucursal puede operar en `SOLO_REGISTRO` o `FACTURAR_SI_SE_SOLICITA`,
2. el POS permite elegir accion fiscal,
3. una venta puede emitir comprobante electronico homologado con CAE y QR,
4. una venta puede quedar como referencia externa,
5. el ticket interno no fiscal sigue existiendo y queda claramente diferenciado,
6. el admin puede ver y reimprimir ticket o comprobante segun corresponda,
7. hay pruebas automatizadas del flujo feliz y del rechazo principal.

## 12. Orden sugerido de ejecucion real

1. Bloque A: base y modelos.
2. Bloque B: configuracion fiscal.
3. Bloque C: POS y accion fiscal.
4. Bloque D: fake fiscal y tests.
5. Bloque E: render admin y comprobante.
6. Integracion homologacion real.

## 13. Primer sprint recomendado

Si queremos arrancar mañana con codigo, el primer sprint deberia incluir solo esto:

1. migrations de `sucursal_fiscal_configs`, `venta_comprobantes`, `venta_comprobante_eventos`,
2. columnas fiscales nuevas en `ventas`,
3. modelos y relaciones Eloquent,
4. constantes de accion/estado fiscal,
5. configuracion fiscal basica de sucursal en admin,
6. selector de accion fiscal en POS sin llamar todavia a ARCA,
7. tests del modo `SOLO_REGISTRO` y `FACTURA_EXTERNA_REFERENCIADA`.

Ese sprint ya nos deja la arquitectura lista y reduce bastante el riesgo antes de tocar la integracion WSAA/WSFEv1.

