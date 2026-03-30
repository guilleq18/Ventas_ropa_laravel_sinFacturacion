# Checkpoint Homologacion Fiscal

Fecha: 2026-03-30

## Estado actual

Ya se avanzo hasta entrar correctamente a `WSASS` de ARCA/AFIP en entorno de homologacion.

Situacion confirmada:

1. El servicio `WSASS - Autogestion Certificados Homologacion` ya estaba adherido.
2. El usuario pudo ingresar a `WSASS`.
3. Se llego a la pantalla `Crear DN y certificado`.

## Usuario actual para WSASS

- CUIT usado para ingresar a `WSASS`: `20364362634`
- Este CUIT corresponde al usuario fisico que administra homologacion.

Importante:

1. En `WSASS` se trabaja primero con el usuario fisico que ingreso.
2. El CUIT de la empresa para facturar se usa mas adelante al autorizar el servicio fiscal (`wsfe`).

## Donde quedamos exactamente

Pantalla actual en `WSASS`:

1. Campo `Nombre simbolico del DN`
2. Campo `CUIT del contribuyente` ya mostrando `20364362634`
3. Campo `Solicitud de certificado en formato PKCS#10`

Todavia NO se pego una CSR.

## Proximo paso real

Antes de completar `WSASS`, hay que generar en la PC:

1. una clave privada `.key`
2. una solicitud de certificado `.csr`

Luego:

1. pegar la `.csr` completa en `WSASS`
2. presionar `Crear DN y obtener certificado`
3. guardar el certificado PEM devuelto por `WSASS` en un archivo `.crt`

## Script preparado en el proyecto

Se creo este script para generar la clave privada y la CSR sin depender de OpenSSL:

- [generar-csr-arca-homo.ps1](/e:/Dev/Projects/tienda_ropa_laravel/scripts/generar-csr-arca-homo.ps1)

Uso previsto:

```powershell
.\scripts\generar-csr-arca-homo.ps1 -Cuit 20364362634 -Alias tiendaropahomo -Organization "QUINTELA GUILLERMO" -CommonName "tienda_ropa_laravel"
```

Si PowerShell bloquea la ejecucion:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\generar-csr-arca-homo.ps1 -Cuit 20364362634 -Alias tiendaropahomo -Organization "QUINTELA GUILLERMO" -CommonName "tienda_ropa_laravel"
```

## Que genera ese script

En `storage/app/fiscal`:

1. un archivo `.key`
2. un archivo `.csr`

Despues hay que:

1. abrir la `.csr`
2. copiar todo el contenido `BEGIN/END CERTIFICATE REQUEST`
3. pegarlo en `WSASS`

## Alias sugerido para WSASS

- `tiendaropahomo`

## Siguiente conversacion

Si retomamos este tema, arrancar desde:

1. ejecutar el script `generar-csr-arca-homo.ps1`
2. copiar la CSR al formulario de `WSASS`
3. guardar el certificado `.crt`
4. despues crear la autorizacion al servicio `wsfe`
