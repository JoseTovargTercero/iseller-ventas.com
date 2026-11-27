# Backend de membresías · Despostes

Backend PHP para reemplazar las rutas que hoy devuelven 404. Ya está montado
en este equipo en:

```
C:\xampp\htdocs\iseller-tiendas.com\despostes
```

URL base de la app:

```
https://iseller-tiendas.com/inventario/despostes
```

El `.htaccess` del sitio ya mapea `/inventario/...` al root del proyecto. Por
eso `/inventario/despostes/pago.php` sirve el archivo real `despostes/pago.php`.
No se tocó `/inventario`, que ya es la app POS/landing.

## Estructura desplegada

```
despostes/
  pago.php
  activar_membresia.php
  estado.php
  admin.php
  version.json
  .htaccess
  private/
    config.php
    lib.php
  data/            (escribible)
```

Este repositorio tiene la misma estructura en `_backend/`; copiarlo como
`despostes` es suficiente.

## Configuración

Edita `private/config.php`:

- `admin_password`: cámbiala. El panel no deja entrar mientras siga
  `CAMBIA-ESTA-CLAVE`.
- `payment_url`: link de pago (opcional). Si está vacío, la página solo recibe
  la solicitud y coordinas el pago por contacto.
- `whatsapp`: número con código de país, sin `+` ni espacios (opcional).
- `support_email`: correo de soporte (opcional).
- `membership_days`: 365 por defecto.

Da permisos de escritura a `data/` (`0755` o `0775`).

## Uso

- Página pública: `https://iseller-tiendas.com/inventario/despostes/pago.php?id=UUID`
- Panel: `https://iseller-tiendas.com/inventario/despostes/admin.php`
- Activación: `POST /inventario/despostes/activar_membresia.php` con
  `device_id` y `codigo`.
- Estado: `POST /inventario/despostes/estado.php` con `device_id`.
- Actualización: `GET /inventario/despostes/version.json`.

Flujo: el usuario deja la solicitud → confirmas el pago → generas un código en
el panel → el usuario lo activa en Membresía.

Prueba rápida:

```bash
curl -X POST "https://iseller-tiendas.com/inventario/despostes/activar_membresia.php" \
  -d "device_id=11111111-2222-3333-4444-555555555555" \
  -d "codigo=DESP-XXXX-XXXX"

curl -X POST "https://iseller-tiendas.com/inventario/despostes/estado.php" \
  -d "device_id=11111111-2222-3333-4444-555555555555"
```

## Análisis de cargamento con IA

`POST /inventario/despostes/analizar_cargamento.php` recibe:

```json
{
  "device_id": "UUID",
  "cargamento": {
    "tipo": "Bovino",
    "fecha": "2026-11-22",
    "resumen": { "reses": 4, "despostadas": 2, "rendimiento": 50, "ganancia_neta": -200, "perdidas": 300, "merma": 200 },
    "cortes": [{ "nombre": "Carne", "peso": 140, "esperado": 53, "obtenido": 35, "diferencia": -18 }],
    "reses": [{ "identificador": "M001", "sexo": "Macho", "rendimiento": 56, "ganancia": -200 }]
  }
}
```

Devuelve `resumen`, `hallazgos`, `recomendaciones` y `alertas`. Usa caché en
`data/analisis/` (30 días), límite de 60 solicitudes por hora y dispositivo y
modelos de respaldo de Gemini.

La clave se lee de `GEMINI_API_KEY` o de `private/gemini.local.php`
(no versionado):

```php
<?php
return ['api_key' => 'TU_CLAVE'];
```

## App

La app usa `AppConfig.API_BASE_URL`:

```java
https://iseller-tiendas.com/inventario/despostes
```

Si cambias la ruta del backend, cambia solo esa constante y recompila.

## Seguridad

- `private/` y `data/` bloqueados por `.htaccess`. En Nginx agrega:

```nginx
location ~ ^/despostes/(private|data)/ { deny all; }
```

- No publiques `private/config.php`.
- El panel no tiene recuperación de clave: si la pierdes, edita el archivo.
- Los códigos viven en `data/codigos.json`. Protege la carpeta.
- El formulario público guarda solicitudes en `data/solicitudes.json` y limita
  a una por dispositivo cada 60 segundos.

## Pago automático

No está integrado. Para cobrar automáticamente hace falta elegir pasarela
(Stripe, PayPal, Mercado Pago, etc.), agregar su checkout y un webhook que
genere el código al confirmar el pago. Sin esas credenciales no se puede
implementar de forma segura.
