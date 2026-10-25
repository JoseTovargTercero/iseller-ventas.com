# Plan — App Android de clientes (iSeller Store)

Fecha: 2026-10-24
Estado: análisis + Fase 1 implementada (API v1 y app Android compilada).

## 1. Objetivo

App Android **nativa** (sin WebView) para los clientes de las tiendas virtuales de iSeller:

- Login con email/contraseña + desbloqueo con huella.
- Branding, hero y catálogo del marketplace y de cada tienda (multi-tienda por `slug`).
- Compra como invitado: carrito sin cuenta y registro/login solo al pagar.
- Carrito y checkout nativos.
- Ubicación del dispositivo para registrar direcciones de entrega.
- Notificaciones push (FCM) para pedidos y chat.
- Mismos colores, tipografía y jerarquía visual que la tienda web.

## 2. Lo que ya existe y se reutiliza

| Área | Dónde | Notas |
|---|---|---|
| Tienda por slug | `index.php` (router), `.htaccess` | `/slug` renderiza la tienda; `negocio.tiene_tienda_virtual=1` |
| Branding/hero | `index.php:2374-2480` | `portada_url`, `logo_url`, `eslogan`, `reputacion_pct`, `categoria` |
| Estilos | `iseller_store/assets/css/global-styles.css` | Paleta `--primary-color:#6FAF7A`, `--primary-dark:#4E8F63`, fondo `#F5F7F6`, texto `#1F2933`, radio 16px, sombras suaves, tipografía Inter |
| Login cliente | `iseller_store/login.php` | Tabla `tienda_usuarios` (cuenta global, sin `negocio_id`) |
| Registro | `iseller_store/api/registrarUserApp.php` | `nombre, email, password, telefono` + `referral_code` |
| Catálogo | `iseller_store/api/productos.php` | `productos + negocio_productos + stock`, precios con `CalculadoraPrecios` por `bss_id` |
| Categorías | `iseller_store/api/get_categories_list.php` | `categorias_producto` |
| Carrito web | `index.php` (IndexedDB) | Nativo se reimplementa local; el servidor solo recibe producto+cantidad |
| Checkout | `iseller_store/checkout.php` | Recalcula precios, valida stock, reserva atómicamente, evita referencia de pago duplicada, crea orden + historial + artículos, chat, email y push al negocio |
| Direcciones | `tienda_usuarios_direcciones` | `lat`, `lng`, `geo_estado`, `geo_municipio`, `referencia`, `es_principal` |
| Geo Venezuela | `iseller_store/core/venezuela_geo.php` | Validación estado/municipio |
| Push | `publico/production/includes/push_fcm.php` | HTTP v1, service account `iseller-e25a8`, tabla `push_dispositivos` (`tipo='cliente'`) |
| Tema/medidas | `index.php` (store-cover 360px desktop), `global-styles.css` | Hero responsive, tarjetas con sombra suave y radio 16px |

## 3. Brechas para una app nativa

1. **Sesión**: las APIs actuales usan cookie de sesión + CSRF. La app necesita tokens.
2. **Checkout**: la lógica vive inline en `checkout.php`. Se replica en la API v1 con las mismas validaciones (pendiente: unificar en un servicio compartido para que web y app no diverjan).
3. **Carrito**: IndexedDB es del navegador. En Android será almacenamiento local (JSON en preferencias) con la misma forma `{producto, sucursal, cantidad}`.
4. **Push**: el servidor ya envía a `tipo='cliente'`, pero no existe una app nativa que registre tokens con Bearer.
5. **Biometría**: no hay nada en el backend; es una protección local del refresh token (lo correcto: el servidor no debe recibir huellas).
6. **Hero**: hoy se arma en HTML. La API entrega un objeto `hero` con imagen, logo, textos, badge y proporción para que la app lo escale.
7. **Ubicación**: la web la pide con `navigator.geolocation`; la app usará permiso + FusedLocationProvider.
8. **Inventario local**: la DB de desarrollo no tiene productos publicados (`negocio_productos` vacío); las pruebas de catálogo/orden deben hacerse en un entorno con datos.

## 4. Arquitectura

```
App Android (Kotlin + Compose, com.iseller.clientes)
  data: OkHttp + kotlinx.serialization · EncryptedSharedPreferences (refresh token)
  ui:   Compose · Navigation · Coil · Material 3 (tema de la tienda)
  push: Firebase Messaging · location: Play Services
            │ HTTPS + Bearer (access 15 min / refresh 60 días rotativo)
            ▼
iseller_store/api/v1/*.php   (nuevo, JSON, fail-closed)
  core/api_v1.php  (JWT HS256, auth, rate limit, helpers)
            │
            ▼
MySQL (iseller): tienda_usuarios, negocio, productos, stock,
                 tienda_ordenes, tienda_orden_articulos,
                 tienda_compras_por_usuarios, tienda_usuarios_direcciones,
                 push_dispositivos, api_refresh_tokens
```

### Multi-tienda y marketplace

La app abre por defecto en **modo marketplace** (sin tienda): productos de todas las
tiendas activas, como la portada web `/`. `STORE_SLUG` está vacío por defecto; el
deep link `iseller://store/{slug}` permite abrir una tienda concreta y desde el
perfil se puede volver al marketplace. El tema y el hero global vienen del servidor
(`store.php` sin `slug`).

## 5. API v1 (segura)

Base: `https://iseller-tiendas.com/iseller_store/api/v1/`

| Método | Ruta | Auth | Función |
|---|---|---|---|
| POST | `auth/login.php` | — | email+password+device_id → access+refresh |
| POST | `auth/register.php` | — | alta de cliente + tokens |
| POST | `auth/refresh.php` | refresh | rota refresh y emite access |
| POST | `auth/logout.php` | Bearer | revoca el refresh del dispositivo |
| GET | `auth/me.php` | Bearer | perfil y nivel/puntos |
| GET | `store.php?slug=` | — | tienda, hero, categorías, delivery, pago, tasas |
| GET | `products.php?slug=&page=&limit=&category=&q=` | — | grid paginado con precio recalculado |
| GET | `geo.php` | — | estados y municipios de Venezuela |
| GET/POST | `addresses.php` | Bearer | listar / crear dirección (lat,lng,estado,municipio) |
| GET/POST | `orders.php` | Bearer | historial, detalle (`?id=`) y crear pedido |
| POST | `push/token.php` | Bearer | alta/baja del token FCM |

Respuesta uniforme: `{ "ok": true, "data": ... }` o `{ "ok": false, "error": "..." }`.

### Seguridad aplicada

- **JWT HS256** firmado con `API_JWT_SECRET` (≥32 chars, fail-closed si falta). `exp` 15 min, `iat`, `jti`, `sub`, `dv` (hash del device).
- **Refresh opaco** de 32 bytes aleatorios; en DB solo se guarda SHA-256; rotación en cada uso; 60 días; revocable por dispositivo; compara con `hash_equals`.
- **Rate limit** por IP (+email en login): login 10/15 min, registro 5/h, resto 120/min. Archivos en `sys_get_temp_dir()`.
- **Solo POST/GET declarados**; cuerpo JSON máximo 1 MB; `Content-Type` validado.
- **Sin confiar en el cliente** para precio, stock, tienda ni total: todo se recalcula en servidor.
- **IDOR**: direcciones y órdenes siempre filtradas por `usuario_id`; producto validado contra la tienda y sucursal publicadas.
- **Pago duplicado**: índice único `(bss_id, numero_operacion_bancaria)` + comprobación previa.
- **Stock**: descuento atómico dentro de transacción (`UPDATE ... WHERE stock >= ?`), rollback completo ante error.
- **Errores**: mensajes genéricos en login; sin trazas al cliente; `Cache-Control: no-store`.
- **Producción**: HTTPS obligatorio, `API_JWT_SECRET` en el `.env` del hosting, no en git; rotación de tokens y limpieza por cron (pendiente).
- El `POS_API_KEY` existente no se usa en la app (no se puede incrustar una llave en un APK).

## 6. App Android

### Stack

Kotlin, Jetpack Compose, Material 3, Navigation, OkHttp, kotlinx.serialization, Coil, FCM, BiometricPrompt, FusedLocationProvider, EncryptedSharedPreferences. Sin Hilt (contenedor manual simple). minSdk 26, target/compile 34.

### Pantallas

1. **Home / Marketplace** — abre primero, sin login: hero global del servidor, buscador, categorías y todos los productos con la tienda de origen. Permite armar el carrito como invitado.
2. **Login / Registro** — aparece al pagar (o desde Perfil/Pedidos), no al abrir. Branding iSeller (logo de la app), nunca el logo/ nombre de una tienda. Tras el primer login se ofrece activar huella.
3. **Desbloqueo biométrico** — BiometricPrompt al abrir si hay sesión y biometría activada; fallback a contraseña.
4. **Carrito** — nativo, persistente, agrupado por tienda. Si hay varias tiendas, se elige cuál pagar; los productos de cada tienda se pagan por separado.
5. **Checkout (wizard de 3 pasos)** — 1) Entrega: delivery/retiro y dirección; 2) Pago: total, **selector Pago Móvil/Transferencia** con los datos bancarios del método elegido, referencia y fecha; 3) Confirmar: resumen del pedido, entrega, método/referencia y total. Validación por paso. Al confirmar: pantalla de éxito con la orden y, si el carrito tiene otra tienda, botón **“Pagar la siguiente tienda”** (replica el flujo de `checkout.php`).
6. **Pedidos** — historial y detalle; destino de los push. Invitado: invitación a iniciar sesión.
7. **Perfil** — invitado: iniciar sesión/crear cuenta; con sesión: datos, huella, volver al marketplace y cerrar sesión.

### Biometría

- Solo se guarda el **refresh token** en `EncryptedSharedPreferences` (clave en Android Keystore).
- Para activar huella se exige una autenticación correcta; para abrir la app se pide huella antes de leer el token.
- La huella nunca se envía al servidor; no se guardan contraseñas.
- Fallback siempre disponible (contraseña) para no dejar al usuario fuera.

### Push

- Canal `pedidos` con importancia alta; permiso `POST_NOTIFICATIONS` (Android 13+).
- Token FCM → `push/token.php` tras login y al renovarse; baja al cerrar sesión.
- Datos esperados: `tipo` (`compra`/`chat`) e `id` para abrir pedidos/chat.
- Requisito externo: `google-services.json` del proyecto Firebase `iseller-e25a8` para `com.iseller.clientes` + SHA-1 de la keystore. Sin ese archivo la app compila y funciona; el push queda inactivo.

### Hero responsive

La API devuelve `hero.image_url`, `logo_url`, `title`, `subtitle`, `badge` y
`aspect_ratio`. En marketplace se usa un hero compacto con imagen de fondo del
servidor y degradado; en tienda se recorta la portada con proporción acotada. En
ambos casos el ancho es completo y la altura tiene límite para no crecer sin
control en tablets.

## 7. Fases

**Fase 1 — base (esta entrega)**
- API v1 + migración `api_refresh_tokens`.
- Proyecto Android aparte, compilable, con tema, login+biometría, hero, catálogo, carrito, checkout con ubicación, pedidos y push.

**Fase 2 — paridad**
- Unificar `checkout.php` y la API en un servicio de órdenes compartido.
- Recuperar contraseña desde la app (deep link al flujo web o endpoint propio).
- Notificaciones in-app (`tienda_cliente_notificaciones`) y chat nativo.
- Favoritos, recompensas y niveles (APIs ya existentes).

**Fase 3 — operación**
- Firma de release (keystore + Play App Signing), `google-services.json` real, SHA-1.
- Versionado remoto (`app_version.json` equivalente para clientes) y actualización forzada.
- Observabilidad: logs de API, alertas de errores 5xx, métricas de checkout.
- Limpieza de refresh tokens vencidos (cron).

## 8. Criterios de aceptación

- Login, refresh automático y logout revocan el refresh en servidor.
- La app abre en el marketplace sin pedir cuenta; el carrito funciona como invitado.
- La cuenta se pide al pagar; al autenticarse se retoma el checkout con el carrito intacto.
- Huella abre la sesión sin volver a pedir contraseña; contraseña siempre como alternativa.
- El hero, logo, colores y categorías del marketplace y de cada tienda cambian desde el servidor.
- Carrito sobrevive al cierre de la app y agrupa productos por tienda.
- Checkout por tienda en 3 pasos (Entrega → Pago → Confirmar), con validación por paso y resumen final.
- Checkout con delivery exige dirección; la ubicación se captura con permiso y se guarda.
- Una orden creada desde la app aparece en el panel y en el perfil web del cliente.
- Referencia de pago duplicada y stock insuficiente se rechazan.
- Push del negocio/cliente llega con la app cerrada.
- Precios y totales coinciden con el carrito web para el mismo producto.

## 9. Riesgos y pendientes

- **Duplicación de la lógica de orden** mientras no se unifique (Fase 2).
- **Push sin `google-services.json`** no se puede probar; requiere registrar la app en Firebase.
- **CORS/CSRF**: no aplican a la app, pero la API v1 no debe aceptar cookies.
- **Ubicación**: si el usuario niega el permiso, se permite dirección manual; el checkout no se bloquea.
- **Moneda**: la app muestra USD como principal (igual que la tienda); BS/COP se actualizan con cada respuesta.
- **Datos locales**: el entorno actual no tiene productos publicados; validar con datos reales antes de release.

## 10. Estructura

```
iseller-tiendas.com/
  iseller_store/api/v1/          API nueva
  iseller_store/core/api_v1.php  helpers de seguridad
  iseller_store/sql/2026_10_24_api_tokens.sql
  docs/PLAN_APP_CLIENTES_ANDROID.md
  docs/API_V1.md
../iseller-app-clientes/         proyecto Android (fuera del webroot)
```

## 11. Estado de la entrega (2026-10-24)

Implementado:

- API v1 completa (`auth`, `store`, `products`, `geo`, `addresses`, `orders`, `push`),
  con `store`/`products` en modo marketplace cuando no se envía `slug`.
- Tabla `api_refresh_tokens` creada en la base local; `API_JWT_SECRET` agregado al
  `.env` local (producción debe agregarlo también).
- App en `C:\xampp\iseller-app-clientes`: compila (`assembleDebug`) y genera
  `app-debug.apk` (~13.1 MB, `com.iseller.clientes.debug`).
- Icono/logo de la app desde `iseller_store/assets/img/logo-tienda.png`; nombre
  **iSeller Store**. El login usa ese logo, no el de una tienda.
- Home global por defecto (marketplace), sin redirección a tienda; carrito de
  invitado y cuenta solicitada al pagar. Checkout por tienda con selector si el
  carrito tiene productos de varias.

Probado en un teléfono **TECNO BG7** (Android 13, 720×1612) contra la API local
(conexión por `adb reverse tcp:8080 tcp:80`), con instalación limpia:

- Abre directamente el marketplace, sin login, con hero global del servidor y
  productos de Loreannys y MiniYolaMarket etiquetados por tienda.
- Carrito de invitado con productos de dos tiendas: agrupado y con selector
  “Elige la tienda a pagar”.
- Al pagar sin cuenta aparece el login con branding iSeller (sin logo de tienda);
  tras iniciar sesión se retoma el checkout de la tienda elegida.
- Checkout por tienda en **wizard de 3 pasos**, con selector de método de pago
  (Pago Móvil tipo 6 / Transferencia tipo 2) y resumen final.
- Pago por separado replicado de `checkout.php`: al confirmar una tienda se muestra
  la orden y el botón **“Pagar la siguiente tienda”** si quedan productos de otra;
  los pedidos #47 (Loreannys, transferencia, $3.15) y #48 (Yola, Pago Móvil, $1.15)
  se crearon en secuencia y el carrito quedó vacío.
- Wizard probado también con validación de método/referencia/fecha y resumen con
  total USD/BS; los datos de prueba (#47/#48, stock, chat, notificaciones) se
  eliminaron al terminar.
- Probado también en la entrega anterior: GPS y alta de dirección, detalle/historial,
  perfil, deep link y gate biométrico.

Verificado también con llamadas reales a la API local:

- `store` y `products` en modo global y por tienda, `geo`, login, refresh
  rotativo, `me`, rechazo por `X-Device-Id` distinto, direcciones, historial y
  detalle de pedidos, registro de token push y errores 401 genéricos.

Pendiente de verificar:

- **Push real**: falta `google-services.json` del proyecto `iseller-e25a8` para
  `com.iseller.clientes` y `com.iseller.clientes.debug`.
- **Retiro en tienda**, referencia de pago duplicada, carrito tras reinicio y
  fallback de huella con contraseña.
- **Creación de pedido en producción** (se probó en local con datos temporales).

Brechas conocidas de esta fase:

- El pedido creado por la API no dispara el correo `enviarNotificacionCompra` que
  sí envía `checkout.php`; sí crea el chat, la notificación in-app y el push al
  comercio. Unificar en Fase 2.
- La lógica de creación de orden está replicada respecto de `checkout.php`
  (precios/stock/duplicados idénticos). Unificar en un servicio compartido.
- Push con app cerrada abre la app; el deep link a pedidos depende de los extras
  de datos de FCM. Unificar a mensajes data-only en Fase 2.
- La tipografía es la del sistema (Roboto); empaquetar Inter para igualar la web.
- El pago es secuencial por tienda (una orden y una confirmación por vez), igual
  que el flujo guiado de la web; si se quiere un solo botón que procese todas las
  sub-compras en lote, es una mejora posterior.


