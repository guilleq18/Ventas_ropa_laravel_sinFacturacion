# Facturacion Electronica Argentina para Tienda de Indumentaria

Fecha del analisis: 2026-03-30

Estado del documento: propuesta funcional y tecnica para incorporar facturacion electronica argentina al sistema actual, manteniendo convivencia entre venta interna y venta facturada.

Nota importante: este documento no reemplaza la validacion final de un contador o asesor fiscal. La normativa argentina cambia con frecuencia y algunas decisiones de implementacion dependen de la condicion fiscal real de la empresa, del tipo de cliente y del circuito operativo elegido.

## 1. Resumen ejecutivo

El sistema actual ya separa bastante bien la operacion comercial de la representacion del comprobante: existe `Venta`, hay POS, pagos, tickets internos, cuentas corrientes y snapshot de datos fiscales basicos. Esa base es buena para incorporar facturacion electronica sin rehacer caja desde cero.

La recomendacion para este proyecto es:

1. Mantener `Venta` como hecho comercial.
2. Introducir una capa nueva de comprobantes fiscales electronicos, separada de la venta.
3. Permitir tres modos operativos por sucursal o por venta:
   - `SOLO_REGISTRO`: registra la venta internamente y emite solo comprobante no fiscal.
   - `FACTURA_ELECTRONICA`: emite comprobante autorizado por ARCA y genera CAE/QR.
   - `FACTURA_EXTERNA_REFERENCIADA`: registra la venta en el sistema, pero guarda que el comprobante legal fue emitido por fuera del sistema.

Advertencia legal: `SOLO_REGISTRO` no puede usarse como sustituto de la factura cuando la operacion exige comprobante fiscal. Debe entenderse como modo interno o como venta cuya documentacion legal se emite por otro canal.

## 2. Analisis normativo aplicable

### 2.1. Regimen general

De acuerdo con el sitio oficial de ARCA para Factura Electronica, el regimen alcanza a responsables inscriptos, exentos en IVA, monotributistas y sujetos adheridos al IVA simple, entre otros. Para operaciones locales tipicas de este sistema, la emision se encuadra en el regimen general de comprobantes electronicos.

Impacto para el sistema:

1. Hay que soportar emision electronica para distintas condiciones fiscales del emisor.
2. No alcanza con un ticket interno: debe existir un comprobante fiscal cuando la operacion lo requiera.
3. El sistema debe manejar tipo de comprobante, punto de venta, numeracion fiscal y autorizacion ARCA.

### 2.2. Tipos de comprobante relevantes

En el regimen general vigente, los comprobantes clase `A`, `B` y `C` siguen siendo los mas relevantes para este proyecto:

1. `A`: operaciones de un emisor habilitado frente a determinados receptores con condicion fiscal compatible.
2. `B`: tipico circuito minorista a consumidor final, exentos o no alcanzados.
3. `C`: tipico para monotributistas.

Ademas, ARCA informa que desde el 2025-12-01 el esquema de comprobantes clase `M` fue reemplazado por un esquema clase `A` con leyenda y retencion de IVA/Ganancias para responsables inscriptos que deban emitir bajo ese tratamiento. Eso significa que el sistema no deberia modelar `M` como estrategia principal nueva; conviene modelar reglas de emision `A` con atributos o leyendas especiales.

### 2.3. Monotributo

ARCA indica que monotributistas de todas las categorias deben emitir factura electronica clase `C`. Si la empresa usuaria del sistema es monotributista, el circuito inicial puede simplificarse mucho:

1. Tipo de comprobante predominante: `C`.
2. Menor complejidad que responsable inscripto para la primera salida a produccion.
3. Igual siguen siendo obligatorios CAE, QR y representacion correcta del comprobante.

### 2.4. Identificacion de consumidor final

ARCA publica que, para consumidor final, cuando el importe de la operacion sea igual o superior a ARS 10.000.000 debe informarse apellido y nombres, domicilio y CUIT/CUIL/CDI o DNI. El sistema tiene que validar esto en POS antes de intentar facturar.

Impacto concreto:

1. Hoy el POS no exige datos fiscales completos del comprador para ventas comunes.
2. Hace falta una capa de "datos fiscales del receptor" separada del cliente de cuenta corriente.
3. La validacion debe dispararse por monto y por tipo de comprobante.

### 2.5. Solicitud de autorizacion, CAE y tiempos

ARCA exige solicitud de autorizacion electronica para cada comprobante, con correlatividad numerica por punto de venta y clase de comprobante. Tambien indica que la fecha del comprobante a autorizar no puede ser mayor a cinco dias corridos anteriores ni mayor a cinco dias corridos posteriores respecto de la fecha de solicitud.

Impacto tecnico:

1. El sistema no puede numerar fiscalmente "a ojo" usando la secuencia comercial actual de `Venta`.
2. La numeracion fiscal debe vivir en la capa del comprobante, no en la capa de la venta.
3. Debe haber manejo de reloj, timezone, reintentos e idempotencia.

### 2.6. QR en comprobantes electronicos

ARCA mantiene el regimen de codigo QR para comprobantes electronicos. La representacion impresa o digital del comprobante autorizado debe incluir el QR conforme a la especificacion oficial.

Impacto tecnico:

1. Hace falta almacenar payload o URL QR por comprobante.
2. El ticket actual no sirve como representacion fiscal final.
3. Debe existir un render fiscal separado del ticket interno.

### 2.7. Transparencia fiscal al consumidor

Con el regimen de transparencia fiscal al consumidor, ARCA exige exponer el desglose del IVA contenido y otros impuestos nacionales indirectos en operaciones alcanzadas. El sistema actual ya tiene una buena base porque `Venta` y `VentaItem` guardan desglose fiscal nacional.

Impacto concreto:

1. Hay que llevar ese desglose al comprobante fiscal y a su representacion.
2. Debe revisarse si el render cambia segun condicion fiscal del emisor y del receptor.
3. Si mas adelante se incorporan otras alicuotas o impuestos, el modelo actual va a necesitar expansion.

### 2.8. Particularidad del rubro indumentaria

No encontre en las fuentes relevadas un regimen separado para la venta minorista comun a consumidor final de indumentaria; por eso infiero que ese flujo entra en el regimen general.

Si encontre una particularidad importante para este rubro: ARCA publica un regimen especial de comprobantes electronicos clase `A` para operaciones vinculadas con indumentaria y accesorios que no dan lugar al computo del credito fiscal. Esa pagina incluso menciona los supuestos de excepcion y el uso del codigo de identificacion `5` en datos adicionales por RG.

Conclusion practica para este proyecto:

1. Si la tienda vende casi exclusivamente a consumidor final, el primer alcance deberia priorizar `B` o `C`.
2. Si la tienda tambien vende a responsables inscriptos, hay que contemplar desde el diseno el caso `A` para indumentaria y sus validaciones especificas.
3. Aunque el primer release no implemente todo el circuito `A` especial, el modelo de datos debe quedar preparado.

## 3. Lectura del sistema actual

### 3.1. Lo que ya existe y suma

Del relevamiento del repo:

1. `app/Domain/Ventas/Models/Venta.php`
   - La venta ya existe como entidad comercial separada.
   - Tiene sucursal, cajero, cliente, medio de pago, total y snapshot de empresa.

2. `app/Domain/Ventas/Support/VentaConfirmationService.php`
   - Confirma ventas desde POS.
   - Genera items, pagos, movimientos de cuenta corriente y baja de stock.
   - Ya calcula y guarda parte del desglose fiscal nacional.

3. `app/Domain/Caja/Support/VentaTicketViewBuilder.php`
   - Genera ticket interno con empresa, items, pagos y desglose fiscal.

4. `app/Domain/Admin/Support/AdminSettingsManager.php`
   - Ya guarda nombre, razon social, CUIT, direccion y condicion fiscal de la empresa.

5. `resources/views/caja/pos.blade.php`
   - El POS ya maneja carrito, medios de pago mixtos y flujo de confirmacion.

Esto es una ventaja clara: no hay que inventar el concepto de venta ni rehacer la caja. Hay que agregar la capa fiscal.

### 3.2. Lo que falta

Hoy no existe una capa completa de comprobacion fiscal electronica. Faltan al menos:

1. Datos fiscales del receptor.
2. Tipo de comprobante y punto de venta fiscal.
3. Numeracion fiscal por ARCA.
4. CAE, vencimiento CAE y QR.
5. Estados fiscales de la venta.
6. Registro de request/response u observaciones de ARCA.
7. Distincion formal entre ticket interno y comprobante fiscal.
8. Notas de credito/debito para anulaciones o devoluciones.
9. Contingencia y reproceso si ARCA no responde.

## 4. Requerimientos funcionales para el sistema

### 4.1. Configuracion maestra

El sistema debe incorporar en configuracion:

1. Condicion fiscal real del emisor con catalogo ampliado.
2. CUIT emisor validado.
3. Punto de venta fiscal por sucursal y por tipo de circuito.
4. Modo fiscal por sucursal:
   - `SOLO_REGISTRO`
   - `FACTURAR_SI_SE_SOLICITA`
   - `FACTURACION_OBLIGATORIA`
5. Entorno fiscal:
   - `HOMOLOGACION`
   - `PRODUCCION`
6. Credenciales y certificados ARCA.
7. Politica de impresion:
   - ticket interno
   - comprobante fiscal PDF/print
   - ambos

### 4.2. Datos del cliente/receptor

Hace falta separar "cliente comercial" de "receptor fiscal". El sistema debe poder capturar:

1. Tipo de documento.
2. Numero de documento.
3. Apellido y nombre o razon social.
4. Condicion frente al IVA del receptor.
5. Domicilio.
6. Email para envio del comprobante.
7. Indicadores especiales para operaciones de indumentaria clase `A`, si aplica.

No todo esto tiene que ser obligatorio para una venta interna no fiscal. Si la accion fiscal elegida es facturar, entonces si.

### 4.3. Flujo en POS

El POS debe sumar una decision explicita antes de cerrar la venta:

1. `Registrar solo venta`
2. `Emitir factura electronica`
3. `Registrar venta con factura emitida por fuera`

Reglas recomendadas:

1. Si la sucursal esta en `FACTURACION_OBLIGATORIA`, la opcion 1 debe bloquearse.
2. Si la sucursal esta en `FACTURAR_SI_SE_SOLICITA`, las tres opciones pueden coexistir segun permiso y contexto.
3. Si la venta supera umbrales o requiere datos del receptor, el POS debe pedirlos antes de confirmar.
4. Si el usuario elige facturar y ARCA rechaza, la venta no debe presentarse como fiscalmente emitida.

### 4.4. Administracion y postventa

El panel admin debe permitir:

1. Ver estado fiscal de cada venta.
2. Reintentar emision fallida.
3. Ver detalle del comprobante emitido.
4. Descargar o reimprimir PDF/representacion fiscal.
5. Registrar referencia de comprobante externo.
6. Emitir nota de credito o nota de debito en fases posteriores.

## 5. Modelo funcional recomendado

### 5.1. Mantener `Venta` como documento comercial

La venta debe seguir representando:

1. Items vendidos.
2. Pagos.
3. Afectacion de stock.
4. Cuenta corriente.
5. Reportes comerciales.

No conviene convertir `Venta` directamente en "factura", porque:

1. Una venta puede no facturarse en el sistema.
2. Una venta puede tener un comprobante externo.
3. Una venta puede necesitar comprobantes posteriores asociados, por ejemplo una nota de credito.

### 5.2. Agregar capa de comprobante fiscal

Se recomienda incorporar una tabla principal como `venta_comprobantes` con relacion `0..n` respecto de `Venta`.

Campos sugeridos:

| Campo | Proposito |
| --- | --- |
| `venta_id` | Relacion con la venta comercial |
| `modo_emision` | Interna, electronica ARCA, externa referenciada |
| `estado_fiscal` | No requerido, pendiente, autorizado, rechazado, anulado, contingencia |
| `tipo_comprobante` | Factura, nota de credito, nota de debito |
| `clase` | A, B, C u otra que aplique |
| `codigo_arca` | Codigo oficial del comprobante |
| `punto_venta` | Punto de venta fiscal |
| `numero_comprobante` | Numero fiscal correlativo |
| `fecha_emision` | Fecha del comprobante |
| `moneda` | Normalmente PES |
| `cotizacion` | Cotizacion si no es moneda local |
| `doc_tipo_receptor` | Tipo de documento del receptor |
| `doc_nro_receptor` | Numero del receptor |
| `receptor_nombre` | Nombre o razon social snapshot |
| `receptor_condicion_iva` | Snapshot fiscal del receptor |
| `importe_neto` | Neto gravado |
| `importe_iva` | IVA total |
| `importe_otros_tributos` | Otros tributos/percepciones |
| `importe_total` | Total del comprobante |
| `cae` | Codigo de autorizacion electronico |
| `cae_vto` | Vencimiento del CAE |
| `qr_payload` | Datos para QR |
| `qr_url` | URL final del QR |
| `resultado_arca` | Aprobado, rechazado, observado |
| `observaciones_arca_json` | Observaciones tecnicas o comerciales |
| `request_payload_json` | Snapshot tecnico para auditoria |
| `response_payload_json` | Respuesta devuelta por ARCA |
| `emitido_en` | Timestamp real de emision |

Se recomienda tambien una tabla `venta_comprobante_eventos` para auditoria y reproceso.

### 5.3. Cliente fiscal separado del cliente de cuenta corriente

No todas las ventas fiscales van a corresponder a un cliente con cuenta corriente. Por eso conviene:

1. Mantener `clientes` de cuentas corrientes como modulo comercial/crediticio.
2. Agregar un snapshot fiscal por venta o un perfil fiscal reutilizable.
3. No forzar la existencia de cuenta corriente para emitir factura.

## 6. Arquitectura tecnica recomendada

### 6.1. Servicios a incorporar

Se recomienda una capa de integracion desacoplada:

1. `FiscalConfigService`
2. `FiscalReceiverResolver`
3. `FiscalDocumentBuilder`
4. `ArcaWsaaClient`
5. `ArcaWsfeClient`
6. `ArcaQrBuilder`
7. `FiscalDocumentRenderer`
8. `FiscalRetryService`

### 6.2. Servicio ARCA a priorizar

Inferencia tecnica para este proyecto: el primer camino deberia montarse sobre `WSFEv1`, porque el sistema hoy opera como retail local con ventas normales, medios de pago mixtos y una estructura simple de items/totales. Ese servicio cubre bien el circuito inicial de comprobantes electronicos tradicionales.

De todos modos, para el caso especifico de indumentaria con circuitos `A` especiales, hay que validar en homologacion si el set de opcionales requerido queda completamente cubierto por el servicio elegido. Si no, habra que evaluar soporte complementario o una estrategia distinta para ese subflujo.

### 6.3. Sincronico vs asincronico

Recomendacion:

1. Para `Emitir factura electronica` desde POS: emision sincronica al confirmar.
2. Si hay timeout o indisponibilidad: guardar la venta con estado fiscal `PENDIENTE_REPROCESO` y bloquear la marca de "facturada".
3. Para reintentos: usar cola o proceso administrativo.

## 7. Fases de implementacion

| Fase | Objetivo | Cambios principales | Salida esperada |
| --- | --- | --- | --- |
| 0. Descubrimiento y definiciones | Cerrar decisiones regulatorias y de alcance | Validar condicion fiscal real, puntos de venta, tipo de clientes, alcance B2C/B2B, necesidad de `A` especial indumentaria, politica legal de `SOLO_REGISTRO` | Matriz de decisiones aprobada |
| 1. Datos maestros y configuracion | Preparar empresa, sucursales y entorno fiscal | Extender empresa/sucursal con datos fiscales, entorno ARCA, certificados, puntos de venta, modos de emision | Backoffice listo para homologacion |
| 2. Dominio fiscal y persistencia | Crear la capa de comprobantes | Migrations nuevas, estados fiscales, snapshots del receptor, eventos, referencias externas | Modelo de datos estable |
| 3. POS y experiencia de usuario | Incorporar decision fiscal en caja | Selector de accion fiscal, captura de receptor, validaciones por monto y por tipo de comprobante, ticket no fiscal vs fiscal | Venta dual operativa |
| 4. Integracion ARCA homologacion | Autorizar comprobantes reales en test | WSAA, WSFEv1, numeracion, CAE, manejo de errores, QR, pruebas end-to-end | Emision homologada |
| 5. Render e historial fiscal | Mostrar y reimprimir comprobantes | PDF/impresion fiscal, vista admin, filtros por estado fiscal, descarga, reintentos | Operacion diaria usable |
| 6. Notas y postventa fiscal | Cubrir devoluciones y ajustes | Nota de credito, nota de debito, asociacion de comprobantes, anulaciones operativas | Ciclo fiscal completo |
| 7. Salida a produccion | Pasar de homologacion a real | Credenciales productivas, checklist, monitoreo, alertas, capacitacion | Sistema listo para uso real |

## 8. Alcance recomendado por iteraciones

### Iteracion 1: salida rapida y segura

Priorizar:

1. Venta minorista a consumidor final.
2. Factura `B` si emisor es responsable inscripto.
3. Factura `C` si emisor es monotributista.
4. Ticket no fiscal para `SOLO_REGISTRO`.
5. Referencia a comprobante externo.
6. CAE, QR y render fiscal.
7. Transparencia fiscal en representacion.

Dejar para despues:

1. Nota de credito.
2. Nota de debito.
3. B2B complejo.
4. Casos especiales de indumentaria clase `A`.
5. Contingencias con `CAEA`.

### Iteracion 2: robustez fiscal

Sumar:

1. Circuito `A` para clientes empresariales.
2. Validaciones especiales del rubro indumentaria si corresponde.
3. Notas de credito por devoluciones.
4. Reproceso automatico de fallas.
5. Reportes fiscales y conciliacion.

## 9. Reglas de negocio recomendadas

### 9.1. Politica de modos operativos

| Modo | Uso recomendado | Impresion | Riesgo |
| --- | --- | --- | --- |
| `SOLO_REGISTRO` | Venta interna, reserva, prueba, o venta cuya documentacion legal se emite afuera | Ticket no fiscal | Alto si se usa para operaciones que debian facturarse |
| `FACTURA_ELECTRONICA` | Venta que el sistema factura en ARCA | Representacion fiscal con CAE y QR | Camino principal recomendado |
| `FACTURA_EXTERNA_REFERENCIADA` | Venta registrada en el sistema pero facturada por Comprobantes en Linea u otro sistema | Ticket interno mas referencia del comprobante externo | Requiere disciplina operativa |

### 9.2. Validaciones minimas del POS

1. No permitir `FACTURA_ELECTRONICA` sin configuracion fiscal completa.
2. No permitir `FACTURA_ELECTRONICA` sin punto de venta asignado.
3. Pedir datos del receptor cuando la normativa lo exige.
4. No marcar la venta como fiscalmente emitida si no hay CAE aprobado.
5. Distinguir visualmente comprobante interno de comprobante fiscal.

### 9.3. Stock y cuenta corriente

La venta comercial puede seguir impactando stock y pagos como hoy. Lo que cambia es el estado fiscal asociado.

Recomendacion:

1. `Venta` mantiene stock/pagos.
2. `VentaComprobante` mantiene emision.
3. Los reportes deben poder separar:
   - ventas confirmadas
   - ventas facturadas
   - ventas pendientes de emision

## 10. Cambios concretos sobre el sistema actual

### 10.1. Base de datos

Agregar:

1. `venta_comprobantes`
2. `venta_comprobante_eventos`
3. Campos fiscales adicionales en sucursal o tabla asociada:
   - punto de venta
   - domicilio fiscal
   - modo fiscal
   - entorno
4. Snapshot fiscal del receptor por venta o por comprobante

Revisar:

1. Si `ventas.numero_sucursal` debe quedar como numeracion comercial interna.
2. Si el actual `codigo_sucursal` debe seguir mostrandose como codigo comercial y no fiscal.

### 10.2. Backend

Modificar:

1. `VentaConfirmationService`
   - debe emitir o referenciar comprobante segun accion elegida
   - debe devolver resultado comercial y resultado fiscal

2. `CajaController`
   - debe aceptar la accion fiscal
   - debe validar receptor
   - debe manejar errores ARCA sin ensuciar la UX del POS

3. `VentaTicketViewBuilder`
   - debe distinguir ticket interno vs representacion fiscal

4. Admin ventas
   - sumar columnas de tipo/estado fiscal, CAE, punto de venta, numero fiscal

### 10.3. Frontend

Agregar en POS:

1. Selector de accion fiscal.
2. Formulario de receptor fiscal.
3. Resumen del comprobante a emitir.
4. Resultado de emision con CAE y QR.
5. Aviso fuerte cuando el comprobante sea no fiscal.

Agregar en admin:

1. Filtros por estado fiscal.
2. Detalle del comprobante.
3. Reintento.
4. Descarga PDF.

## 11. Riesgos y decisiones que no conviene patear

1. Confirmar si la empresa emisora es `Responsable Inscripto` o `Monotributista`.
2. Confirmar si las sucursales tendran punto de venta propio.
3. Confirmar si el negocio realmente necesita vender a empresas con factura `A`.
4. Definir la politica legal de `SOLO_REGISTRO`.
5. Definir si las devoluciones se manejaran desde el mismo sistema con nota de credito.
6. Definir si la integracion fiscal debe ser obligatoria en produccion o activable por sucursal.

## 12. Recomendacion final

La mejor estrategia para este sistema es implementar facturacion electronica como una capa fiscal desacoplada de la venta.

Para una tienda de indumentaria, el primer release deberia enfocarse en el flujo mas frecuente y de menor riesgo:

1. venta minorista,
2. consumidor final,
3. factura `B` o `C`,
4. CAE y QR,
5. ticket no fiscal claramente diferenciado,
6. registro de comprobante externo.

En paralelo, el modelo debe quedar listo para un segundo release con:

1. clientes empresariales,
2. factura `A`,
3. particularidades del rubro indumentaria,
4. notas de credito y debito,
5. reprocesos y contingencia.

## 13. Fuentes oficiales relevadas

1. Regimen general de comprobantes: https://www.arca.gob.ar/facturacion/regimen-general/comprobantes.asp
2. Factura electronica, sujetos alcanzados: https://www.arca.gob.ar/fe/emision-autorizacion/sujetos.asp
3. Factura electronica, datos de los comprobantes: https://www.arca.gob.ar/fe/emision-autorizacion/datos-comprobantes.asp
4. Factura electronica, solicitud de autorizacion: https://www.arca.gob.ar/fe/emision-autorizacion/solicitud-autorizacion.asp
5. Factura electronica, consideraciones generales: https://arca.gob.ar/fe/emision-autorizacion/consideraciones.asp
6. Monotributo, facturacion: https://www.arca.gob.ar/monotributo/ayuda/facturacion.asp
7. QR para comprobantes electronicos: https://www.arca.gob.ar/fe/qr/
8. Vigencia y aplicacion del QR: https://arca.gob.ar/fe/qr/vigencia-y-aplicacion.asp
9. Regimen especial de indumentaria y accesorios: https://arca.gob.ar/fe/regimenes-especiales/operaciones-credito-fiscal.asp
10. Acciones para consumir web services: https://www.arca.gob.ar/ws/documentacion/arquitectura-general.asp
11. Manual WSFEv1: https://www.arca.gob.ar/fe/ayuda/documentos/wsfev1-RG-4291.pdf
12. Manual WSAA: https://www.arca.gob.ar/ws/WSAA/WSAAmanualDev.pdf
13. Resolucion General 5614 de transparencia fiscal al consumidor: https://www.boletinoficial.gob.ar/detalleAviso/primera/318151/20241213

