# App Android — iSeller Códigos

App interna para asociar códigos de barras a productos ya registrados en un
negocio (`bss_id`). Incluye búsqueda por nombre (palabras con AND), búsqueda
escaneando un código y asignación por lector de códigos de Google.

## API

Dos endpoints nuevos en `api/`. No piden `X-Api-Key` porque esa llave no se
puede incrustar en un APK; la autenticación es el usuario/contraseña del negocio
y un token Bearer de `PosAuth` (mismo almacén que el POS de Windows).

### POST `/api/codigos_login.php`

```json
{ "usuario": "cajero", "password": "..." }
```

Respuesta:

```json
{
  "ok": true,
  "token": "…64 hex…",
  "expira_en": 2592000,
  "bss_id": 2,
  "negocio": "Loreannys",
  "usuario": { "id": 54, "nombre": "Prueba", "usuario": "cajero" }
}
```

Valida usuario activo, contraseña y membresía vigente. Límite: 15 intentos por
IP cada 5 minutos. Errores genéricos en credenciales.

### GET `/api/codigos.php`

Cabecera `Authorization: Bearer <token>`. Devuelve todos los productos activos
del `bss_id` del token:

```json
{
  "ok": true,
  "bss_id": 2,
  "negocio": "Loreannys",
  "productos": [
    { "id": 1495, "nombre": "ARROZ MARY TRADICIONAL", "codigo_barras": "" }
  ]
}
```

### POST `/api/codigos.php`

```json
{ "producto_id": 1495, "codigo_barras": "7591002200145" }
```

- El producto debe pertenecer al `bss_id` del token (evita IDOR).
- Rechaza con `409` si el código ya pertenece a otro producto del negocio.
- Rechaza vacío o más de 255 caracteres.
- Responde el producto actualizado.

## App

Proyecto: `C:\xampp\iseller-app-codigos` (fuera del webroot, igual que la app
de clientes). Kotlin + Compose, `com.iseller.codigos`, minSdk 26, target 34.

- Login con servidor editable (por defecto `https://iseller-tiendas.com`).
- Descarga la lista al entrar y permite refrescarla.
- Buscador tipo Fuse con método AND: cada palabra debe aparecer en el nombre o
  en el código; ignora mayúsculas y acentos.
- Cada producto muestra nombre, código de barras y botón **Asignar/Cambiar**
  (lector de Google). El botón de lápiz permite escribir el código a mano.
- **Escanear código** busca un código exacto en el catálogo y avisa si existe
  (con su producto/código) o si no está asignado a ningún producto.
- Si el producto ya tenía otro código, pide confirmación antes de reemplazarlo.
- El token se guarda cifrado con Android Keystore (`EncryptedSharedPreferences`).

### Compilar

```powershell
$env:JAVA_HOME='C:\Program Files\Android\Android Studio\jbr'
cd C:\xampp\iseller-app-codigos
.\gradlew.bat testDebugUnitTest assembleDebug
```

Producción (por defecto):

```
app/build/outputs/apk/debug/app-debug.apk
```

Local, con el teléfono por USB y Apache en el puerto 80:

```powershell
adb reverse tcp:8080 tcp:80
.\gradlew.bat assembleDebug -PserverBase=http://127.0.0.1:8080/iseller-tiendas.com
adb install -r app\build\outputs\apk\debug\app-debug.apk
```

## Pruebas hechas (local)

- Login, lista de 1947 productos del `bss_id=2` y persistencia de sesión.
- Búsqueda AND (`arroz mary` → 6 resultados; cada palabra se cumple).
- Búsqueda por código exacto existente y por código inexistente.
- Lector de Google abre desde **Cambiar/Asignar** (probado en TECNO BG7).
- Asignación manual de un código nuevo, confirmación de reemplazo y
  actualización reflejada en la lista y en MySQL.
- Rechazo de código duplicado (`409`) mostrado en la app.
- Errores `400/401/404/405/409` verificados con llamadas HTTP directas.
- Datos de prueba restaurados y usuario temporal eliminado.

## Despliegue pendiente

Subir `api/codigos.php` y `api/codigos_login.php` al servidor de producción.
Hasta entonces el APK abre y muestra el login, pero contra
`https://iseller-tiendas.com` responde 404.
