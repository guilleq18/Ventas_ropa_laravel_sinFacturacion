# Instructivo breve: Credenciales ARCA desde el panel

## Dónde está

En el panel de administración:

1. Ir a `Configuración`.
2. Abrir la pestaña `Credenciales ARCA`.

Importante:

- Las credenciales ARCA son globales para el sistema.
- La sucursal seleccionada solo se usa para la prueba de homologación.

## Flujo recomendado

### 1. Generar clave privada y CSR

Completar:

- `CUIT representado`
- `Alias simbólico DN`
- `Organization`
- `Common Name`

Importante para `Alias simbólico DN`:

- solo puede tener letras y números
- no usar espacios
- no usar guiones
- no usar guion bajo

Luego presionar `Generar key + CSR`.

Qué hace el sistema:

- genera la clave privada
- genera la CSR
- guarda ambos archivos en storage privado
- muestra la CSR lista para copiar
- permite copiar la CSR completa con un botón

## 2. Crear el certificado en WSASS

Con la CSR generada:

1. Ingresar a `WSASS` de ARCA/AFIP.
2. Usar el alias en `Nombre simbólico del DN`.
3. Pegar la CSR completa en `Solicitud de certificado en formato PKCS#10`.
4. Crear el certificado.
5. Guardar el certificado devuelto por ARCA en formato PEM o `.crt`.

## 3. Cargar el certificado en el sistema

Volver a `Configuración > Credenciales ARCA`.

Se puede:

- subir el archivo `.crt/.pem`
- o pegar el contenido completo del certificado

Luego presionar `Guardar certificado`.

Qué valida el sistema:

- que el certificado tenga formato válido
- que corresponda con la clave privada actual

## 4. Validar credenciales

Presionar `Validar credenciales`.

El sistema informa:

- si la clave y el certificado coinciden
- CUIT detectado en el certificado
- fechas de validez
- observaciones si encuentra inconsistencias

## 5. Probar conexión ARCA

Con una sucursal seleccionada, presionar `Probar conexión ARCA`.

La prueba verifica:

- estado básico de WSFE (`FEDummy`)
- acceso WSAA
- consulta del último comprobante autorizado

## Notas útiles

- Si regenerás la clave privada, tenés que volver a cargar el certificado.
- Si no hay sucursales activas, igual podés generar y cargar credenciales; solo no se puede hacer la prueba de homologación desde el panel.
- La configuración fiscal por sucursal sigue estando en la pestaña `Facturación electrónica`.
