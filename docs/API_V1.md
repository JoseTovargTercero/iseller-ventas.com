# API v1 — app de clientes

Base: `https://iseller-tiendas.com/iseller_store/api/v1/`
Respuestas: `{"ok":true,"data":...}` / `{"ok":false,"error":"...","code":"..."}`.
Errores HTTP: 400 datos inválidos, 401 sesión, 403, 404, 409 conflicto, 429 rate limit, 500.

## Autenticación

- `access_token`: JWT HS256, 15 min, ligado al `X-Device-Id`.
- `refresh_token`: 64 hex, 60 días, rotativo, guardado en BD como SHA-256.
- Cabeceras en rutas protegidas:
  `Authorization: Bearer <token>` y `X-Device-Id: <8-64 chars>`.

### POST auth/login.php

```json
{ "email": "cliente@correo.com", "password": "secreto", "device_name": "Pixel 7" }
```

200: `access_token`, `refresh_token`, `token_type`, `expires_in`, `user`.
Rate limit: 10 intentos/15 min por IP+correo, 40/15 min por IP.

### POST auth/register.php

```json
{ "name": "Ana", "email": "ana@correo.com", "phone": "04141234567",
  "password": "secreto", "password_confirm": "secreto", "device_name": "Pixel 7" }
```

200 con la misma sesión que login. 409 si el correo ya existe.

### POST auth/refresh.php

```json
{ "refresh_token": "..." }
```

Rota el refresh (el anterior queda revocado). 401 si expiró, fue revocado o el
dispositivo no coincide.

### POST auth/logout.php

Revoca el refresh enviado. Requiere Bearer.

### GET auth/me.php

Devuelve `id, name, email, phone, photo_url, level, points, referral_code, created_at`.

## Tienda (público)

### GET store.php?slug=yola

`slug` es opcional. Sin `slug` devuelve el **marketplace**:

```json
{
  "marketplace": true,
  "store": null,
  "hero": { "image_url": "...", "logo_url": "...",
            "title": "Descubre productos de tiendas de tu localidad",
            "subtitle": "...", "badge": "Tu mercado en un solo lugar",
            "aspect_ratio": 2.2 },
  "theme": { "primary": "#36A97C", "primary_dark": "#2A8562", ... },
  "delivery": null,
  "payment": null,
  "categories": [ { "id": 1, "name": "Abarrotes" } ],
  "stores": [ { "id": 2, "slug": "loreannys", "name": "loreannys",
                "logo_url": "...image.php?...w=160&q=78", "category": "..." } ],
  "rates": { "bcv": 0, "usd_cop": 0, "usd_bs": 0 }
}
```

`stores` son las tiendas virtuales activas (máximo 50, orden alfabético) para el
cintillo del marketplace. Con `slug` también se devuelve la lista, para poder
volver al marketplace desde una tienda.

Todas las URLs de imagen (`hero`, `logo`, `cover`, productos, `photo_url`) son
**comprimidas**: apuntan a `GET image.php?src=...&w=...&q=...`, que redimensiona y
sirve WebP cacheado (30 días, ETag). `src` solo acepta rutas dentro de
`iseller_store/assets/img`; cualquier otra ruta o intento de traversal da 404.
El tipo real se detecta con `getimagesize`, así que también corrige archivos con
extensión equivocada. Las URLs externas (`http(s)://`) no se proxyfican.

Con `slug`, `marketplace` es `false`, `store` trae el branding, y `delivery`/`payment`
son la configuración de esa tienda.

### GET products.php?slug=yola&page=1&limit=20&category=5&q=arroz

`slug` es opcional. Sin `slug` devuelve productos de **todas** las tiendas activas
y agrega `store_slug`/`store_name` a cada ítem (también en modo tienda).

200: `items[]` con `id, name, image_url, price_usd, price_bs, price_cop, stock,
in_stock, pack_size, bulk, category_id, category_name, store_id, store_slug,
store_name, branch_id`, más `page, limit, total, has_more, marketplace`. Los
precios se recalculan con las tasas de la tienda de cada producto.

### GET geo.php

`{ "states": { "Amazonas": ["Atures", ...], ... } }`### GET image.php?src=assets/img/tiendas/2/logo.webp&w=160&q=78

Proxy público de imágenes: redimensiona a `w` (64–1600) y comprime en WebP con
calidad `q` (40–85). Responde `image/webp`, `ETag` y `Cache-Control` de 30 días;
el archivo generado se cachea en el temporal del servidor. Solo rutas bajo
`assets/img`; el resto responde 404.

## Direcciones (Bearer)

### GET addresses.php

`{ "items": [ { "id", "name", "phone", "address", "reference", "state",
"municipality", "community", "lat", "lng", "is_primary" } ] }`

### POST addresses.php

```json
{ "name": "Ana", "phone": "04141234567", "address": "Calle 1, casa 2",
  "reference": "Frente a la plaza", "state": "Amazonas", "municipality": "Atures",
  "community": "Centro", "lat": 5.66, "lng": -67.62, "is_primary": true }
```

`state`/`municipality` se validan contra `core/venezuela_geo.php`. `lat`/`lng` son
opcionales (la app los pide, pero el permiso puede denegarse).

## Pedidos (Bearer)

### GET orders.php

Últimos 50 del cliente: `items[]` con `id, created_at, total_usd, total_bs,
total_cop, delivery_type, status, payment_reference, store_id, store_name,
store_slug, item_count`.

### GET orders.php?id=41

`{ order, items[], address, store }`. Solo el dueño puede verlo (IDOR filtrado).

### POST orders.php

```json
{ "slug": "yola",
  "items": [ { "product_id": 10, "branch_id": 2, "quantity": 2 } ],
  "delivery_type": "delivery",
  "address_id": 5,
  "payment_reference": "123456",
  "payment_hour": "2026-10-24 10:30",
  "payment_method": 2 }
```

`payment_method`: **6 = Pago Móvil**, **2 = Transferencia bancaria** (los mismos
códigos que usa `checkout.php`). Cada tienda se procesa en una petición separada;
el flujo de la app repite el wizard para la siguiente tienda y la referencia puede
repetirse entre tiendas (la unicidad es por `bss_id`).

El servidor:
1. valida tienda/producto/sucursal publicados,
2. recalcula precios y envío,
3. rechaza referencias de pago repetidas (`bss_id + numero_operacion_bancaria`),
4. descuenta stock de forma atómica en una transacción,
5. crea `tienda_ordenes`, `tienda_compras_por_usuarios` y `tienda_orden_articulos`,
6. crea el chat de compra y avisa al comercio (in-app + push).

200: `{ order_id, total_usd, total_bs, total_cop, delivery_usd, status }`.

## Chat (Bearer)

### GET chat.php

Conversaciones del cliente:

```json
{ "items": [ { "id": 26, "order_id": 42, "subject": "Pedido #42",
  "status": "abierta", "store_name": "loreannys", "store_logo": "...",
  "unread": 0, "last_message": "...", "last_message_at": "...",
  "created_at": "..." } ] }
```

### GET chat.php?id=26

`{ conversation, messages[] }` con `messages[] = { id, mine, message, created_at }`.
Al abrirla se marcan como leídos los mensajes de la tienda.

### GET chat.php?order_id=42

Abre (o crea, si el pedido aún no tiene) la conversación del pedido. Mismo
formato que `?id=`. Solo el dueño del pedido puede abrirla.

### POST chat.php

```json
{ "conversation_id": 26, "message": "Hola, ¿ya salió mi pedido?" }
```

Inserta el mensaje como cliente, actualiza `ultimo_mensaje_en` y avisa al
comercio (in-app/push). No permite escribir en conversaciones cerradas.

## Push (Bearer)

### POST push/token.php

```json
{ "token": "<FCM token>", "action": "register", "platform": "android" }
```

`action: "unregister"` da de baja el dispositivo. Se guarda en
`push_dispositivos` con `tipo='cliente'`.

## Operación

- Crear `api_refresh_tokens`:
  `mysql ... < iseller_store/sql/2026_10_24_api_tokens.sql`
- Añadir al `.env` del hosting: `API_JWT_SECRET=<64 caracteres aleatorios>`.
  Sin ese valor la API responde 503 (fail-closed).
- HTTPS obligatorio; el certificado se valida en la app.
- Cron recomendado: borrar refresh expirados/revocados con más de 30 días.
