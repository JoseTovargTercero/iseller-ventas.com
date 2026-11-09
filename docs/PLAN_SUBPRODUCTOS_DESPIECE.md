# Plan: subproductos, control por canal y seguimiento estricto desde la última compra

## 0. Cómo usarlo (implementación)

1. **Configuración → Seguimiento estricto de inventario:** activarlo.
2. **Productos:** marcar la columna "Estricto" en la res y en los productos que suelan tener merma.
3. **Res en canal / Subproductos:** seleccionar la res y pulsar "Usar como res en canal".
4. Agregar los cortes (nuevo o vincular existente). Si se vende al mayor, marcar "Crear también variante al mayor".
5. **Registrar res:** kilaje total, costo y proveedor. Opcionalmente sumar al stock y reactivar cortes.
6. **Vender:** los cortes aparecen en el POS; muestran "Res: X kg" y aceptan cantidades decimales.
7. **Interruptor:** pausar el corte que se agotó. Los demás siguen vendiendo contra la misma res.
8. **Cerrar res:** confirmar el monto actual o ir al módulo de ajuste de stock para registrar la diferencia.
9. **Nueva compra:** si el producto es estricto, el sistema bloquea la compra hasta confirmar la cantidad anterior; luego suma al lote abierto.

## 1. Resumen

Se mantienen los dos modos del mismo módulo:

- **CANAL (automático):** se registra la res con su kilaje total; los cortes se venden y cada venta descuenta kilos de la res. El carnicero no pesa ni asigna cortes a diario.
- **DETALLADO (despiece):** se pesa y asigna kg a cada corte al despostar; cada corte tiene stock propio.

La res no asume merma. Al cerrar, el sistema solo informa la diferencia pendiente; el usuario decide si esa diferencia se registra como **merma** o como **ajuste** usando el módulo de mermas/ajustes que ya existe.

Además, se agrega una opción global de **seguimiento estricto**: en las nuevas compras de los productos marcados, el sistema muestra la cantidad anterior, lo vendido desde la última compra y obliga a confirmar o ajustar el conteo real antes de sumar la nueva mercancía.

## 2. Modo CANAL (recomendado para carne)

```
1. Registrar res: 1000 kg  → abre lote, suma 1000 kg al producto padre
2. Cortes con stock 0, pero vendibles
3. Cada venta descuenta de la res:
       carne 10 kg    → res 990
       costilla 5 kg  → res 985
       hueso 20 kg    → res 965
4. El carnicero pausa el corte que se acabó
5. Al cerrar la res, el sistema muestra:
       kg iniciales: 1000
       kg vendidos:   940
       diferencia:     60  → pendiente, NO merma automática
6. El usuario decide:
       - registrar merma en el módulo existente
       - registrar ajuste de inventario
       - dejar pendiente para la próxima compra
```

Reglas:

- **1 kg vendido = 1 kg menos de res.** Es conservación de masa, no importa si el corte tiene hueso.
- La res es el **tope global**: no se pueden vender más kg entre todos los cortes que los que quedan en la res.
- El corte no usa stock propio en CANAL; queda en 0. El POS muestra **"Res: X kg"**.
- El **interruptor** (`stock.visible_venta`) es el control humano de "este corte se acabó".
- **Cerrar la res no modifica stock ni crea merma.** Solo congela el lote y deja la diferencia pendiente.
- La merma se registra únicamente cuando el usuario lo decide, en `ajustes_stock.php` / `stock_ajustes`.
- El resultado del desposte se ve al cerrar y se calcula con las ventas; nadie tiene que pesarlo a diario.

### 2.1 Mejoras sobre la idea original

1. **La res es una bolsa de kg, no un stock por corte.**
   Evita stock negativo y el "0 kg" confuso. El corte muestra la disponibilidad de la res.

2. **Un lote por res y sucursal.**
   Cada res guarda `kg_inicial`, `kg_restante`, proveedor, costo y fechas. Queda historial para comparar rendimiento.

3. **Tope global.**
   La suma de ventas de todos los cortes no puede superar el lote. Evita vender 1200 kg de una res de 1000 kg.

4. **Rendimiento esperado como alerta, no como tarea.**
   Cada corte puede tener un `rendimiento` de referencia (carne 40 %, hueso 22 %). Al cerrar se compara lo vendido contra lo esperado. No se captura a diario.

5. **Interruptor por corte y sucursal.**
   El corte agotado se pausa y desaparece del POS; los demás siguen vendiendo contra la misma res.

6. **Cierre sin suposiciones.**
   El cierre no escribe `MERMA` ni toca el stock. Solo deja la diferencia pendiente y muestra el acceso al módulo de mermas.

7. **Conteo final opcional.**
   Si quieren precisión, al cerrar escriben los kg reales por corte. No es obligatorio.

8. **Venta de la res completa o media res.**
   La propia res puede venderse; su venta también descuenta del lote.

## 3. Modo DETALLADO (despiece)

```
Res 1000 kg
  └─ Desposte 1000 kg
       ├─ Carne        400 kg → stock propio
       ├─ Molida       150 kg → stock propio
       ├─ Costilla     120 kg → stock propio
       ├─ Hueso        250 kg → stock propio
       └─ Merma         80 kg
```

- Se pesa una vez al despostar y se asigna kg real a cada corte.
- Cada corte tiene stock propio; la venta descuenta solo su stock.
- La res baja al despostar, no al vender.
- La merma del despiece la decide el usuario (no se asume).
- Sirve para quesos, rollos de tela o cortes de alto valor.

Es el mismo producto padre, los mismos cortes, el mismo interruptor y las mismas tablas. Cambia `modo_control` y la lógica de descuento.

## 4. Seguimiento estricto desde la última compra

### 4.1 Qué resuelve

Si ingresaste 1000 kg, vendiste 640 kg y luego registras una compra nueva, el sistema no debe sumar a ciegas. Debe mostrar:

- Cantidad anterior en sistema: **360 kg**.
- Última compra: fecha y kg.
- Vendido desde la última compra: **640 kg**.
- Conteo real: **355 kg** (editable).
- Diferencia: **-5 kg**.

El usuario confirma o ajusta. Solo después se suma la nueva compra.

### 4.2 Configuración

**Global (por negocio):**

- `negocio.seguimiento_estricto = 1` activa el módulo.
- Se expone como toggle en la página de configuración del sistema.
- Si está en 0, los productos marcados no se controlan y el flujo de compra sigue igual que hoy.

**Por producto:**

- `productos.seguimiento_estricto = 1` marca el producto.
- Checkbox en crear/editar producto y acción rápida en el listado.
- Filtro "Seguimiento estricto" en `productos.php` para encontrar los productos marcados.
- Se puede aplicar a res, cerdo, pollo, queso, o cualquier producto que suela tener merma.

**Por sucursal (datos de la última compra):**

- `stock.ultima_compra_at DATETIME NULL`
- `stock.ultima_compra_kg DECIMAL(15,4) NOT NULL DEFAULT 0`

### 4.3 Flujo en compra simple (`nuevaCompra.php`)

1. El usuario selecciona el producto.
2. El backend devuelve el contexto:
   - `seguimiento_estricto`
   - `stock_actual`
   - `ultima_compra_at`, `ultima_compra_kg`
   - `vendido_desde_ultima_compra`
   - `lote_abierto` (si aplica)
3. Si es estricto, la compra **se bloquea** hasta abrir el modal de conciliación.
4. El modal muestra los datos y ofrece dos caminos:
   - **Confirmar monto actual:** el stock actual es correcto; continúa la compra.
   - **Ir a ajuste de stock:** abre el módulo existente (`ajustes_stock.php`). Allí el usuario registra la merma o el ajuste; luego vuelve y confirma.
5. El backend **no registra la diferencia por su cuenta**. La diferencia la registra el usuario en el módulo de ajustes.
6. Al confirmar, el backend:
   - Bloquea la fila de stock (`FOR UPDATE`) y compara el stock actual con el que el usuario vio (`stock_visto`). Si cambió, pide confirmar de nuevo.
   - Suma la nueva compra al stock y al lote abierto: `stock.stock = stock_confirmado + cantidad_comprada`.
   - Si no hay lote abierto y el producto es CANAL, crea uno con el stock confirmado + la compra.
   - Actualiza `ultima_compra_at = NOW()` y `ultima_compra_kg = cantidad_comprada`.
   - Registra la compra en `compras` como hoy.

**El backend no confía en el frontend:** si el producto es estricto y no llega la confirmación, responde `requiere_conciliacion` con el contexto y no modifica nada.

### 4.4 Flujo en compra con factura (`factura_compra_acciones.php`)

- La factura puede tener varias líneas.
- Al guardar, el backend revisa qué líneas son de productos estrictos.
- Si falta alguna confirmación, responde con la lista de productos que la requieren y su contexto; no crea la factura.
- El frontend muestra un modal con los productos estrictos y dos opciones: confirmar el monto actual o ir al módulo de ajuste de stock.
- Se reenvía la factura con `conciliaciones[producto_id] = { confirmado: 1, stock_visto: X }`.
- El backend aplica, en la misma transacción:
  1. Valida que `stock_visto` coincida con el stock actual.
  2. Suma la cantidad comprada al stock y al lote abierto.
  3. Actualiza la última compra.
  4. Inserta la factura y sus líneas.
- La diferencia la registra el usuario en el módulo de ajustes; la factura no la inventa.

### 4.5 Ejemplo numérico

```
Compra inicial:        1000 kg
Ventas desde entonces:  640 kg
Stock esperado:         360 kg
El usuario confirma 360 o va al módulo de ajuste y registra la diferencia.
Nueva compra:           500 kg
Stock final:            860 kg  (360 + 500), o el stock confirmado que haya quedado.
Última compra:          ahora, 500 kg
```

### 4.6 Productos con lote CANAL

Si el producto estricto tiene un lote abierto:

- El "stock esperado" es el stock actual de la res; el último lote (abierto o cerrado) explica las ventas desde la última compra.
- Si el usuario va al módulo de ajuste y registra una merma/ajuste sobre la res, el módulo sincroniza `lotes.kg_restante` y deja el detalle en `lotes_detalle` con `origen='MERMA'` o `'AJUSTE'`.
- La nueva compra se suma al lote abierto: `kg_inicial += compra`, `kg_restante += compra`.
- Si no hay lote abierto, se crea uno nuevo con `kg_inicial = stock_confirmado + compra`. El stock confirmado incluye lo que haya quedado de la res anterior.
- Si hay un lote cerrado con diferencia pendiente, la compra estricta la muestra igual y obliga a confirmarla o ajustarla.

### 4.7 Qué pasa si el producto no es estricto

Nada cambia. La compra sigue como hoy: `stock_actual + cantidad_comprada`. El módulo de mermas sigue disponible para registrar pérdidas cuando el usuario lo decida.

### 4.8 Puerta trasera

`modificar_stock.php` permite fijar el stock directamente. Para que el seguimiento estricto no se pueda saltar:

- Si el producto es estricto, exigir un motivo y registrar el cambio en `stock_ajustes` como `AJUSTE`/`CONTEO_FISICO`.
- No permitir "stock = X" sin dejar el movimiento.
- Esto también aplica a transferencias y ajustes masivos.

## 5. Modelo de datos

### 5.1 Migración `configurar/migrations/022_canal_subproductos.sql`

```sql
-- Opción global del negocio.
ALTER TABLE negocio
  ADD COLUMN seguimiento_estricto TINYINT(1) NOT NULL DEFAULT 0;

-- Producto: unidad, rendimiento, modo y bandera de seguimiento estricto.
ALTER TABLE productos
  ADD COLUMN unidad VARCHAR(8) NOT NULL DEFAULT 'UND' AFTER nombre,
  ADD COLUMN rendimiento DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER id_producto_relacionado,
  ADD COLUMN modo_control ENUM('NORMAL','CANAL','DETALLADO') NOT NULL DEFAULT 'NORMAL'
      AFTER rendimiento,
  ADD COLUMN seguimiento_estricto TINYINT(1) NOT NULL DEFAULT 0 AFTER modo_control,
  ADD KEY idx_prod_relacionado (bss_id, id_producto_relacionado),
  ADD KEY idx_prod_estricto (bss_id, seguimiento_estricto);

-- Interruptor de venta y datos de la última compra.
ALTER TABLE stock
  ADD COLUMN visible_venta TINYINT(1) NOT NULL DEFAULT 1 AFTER stock,
  ADD COLUMN ultima_compra_at DATETIME NULL AFTER visible_venta,
  ADD COLUMN ultima_compra_kg DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER ultima_compra_at,
  ADD KEY idx_stock_visible (bss_id, id_sucursal, visible_venta),
  ADD KEY idx_stock_estricto (bss_id, id_sucursal, ultima_compra_at);

-- Lotes de canal/res.
CREATE TABLE IF NOT EXISTS lotes (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  bss_id                INT NOT NULL,
  id_sucursal           INT NOT NULL,
  padre_id              INT NOT NULL,
  modo                  ENUM('CANAL','DETALLADO') NOT NULL DEFAULT 'CANAL',
  kg_inicial            DECIMAL(15,4) NOT NULL,
  kg_restante           DECIMAL(15,4) NOT NULL,
  diferencia_pendiente  DECIMAL(15,4) NOT NULL DEFAULT 0,
  conciliado            TINYINT(1) NOT NULL DEFAULT 0,
  costo_total           DECIMAL(15,4) NOT NULL DEFAULT 0,
  proveedor             VARCHAR(150) NULL,
  notas                 TEXT NULL,
  estado                ENUM('ABIERTO','CERRADO','ANULADO') NOT NULL DEFAULT 'ABIERTO',
  fecha_apertura        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_cierre          DATETIME NULL,
  usuario_id            INT NOT NULL,
  KEY idx_lote_bss_suc (bss_id, id_sucursal, estado),
  KEY idx_lote_padre (bss_id, padre_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Movimientos del lote.
CREATE TABLE IF NOT EXISTS lotes_detalle (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  lote_id         INT NOT NULL,
  producto_id     INT NULL,
  cantidad        DECIMAL(15,4) NOT NULL,
  costo_unitario  DECIMAL(15,4) NOT NULL DEFAULT 0,
  origen          ENUM('DESPIECE','VENTA','MERMA','AJUSTE') NOT NULL,
  orden_id        INT NULL,
  usuario_id      INT NOT NULL,
  fecha           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lote_det (lote_id, origen),
  KEY idx_lote_prod (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cantidades de venta como número, no texto. Necesario para kg y reportes.
ALTER TABLE orden_articulos
  MODIFY quantity DECIMAL(15,3) NOT NULL DEFAULT 0;
```

### 5.2 Enlace padre → subproducto

Se reutiliza `productos.id_producto_relacionado`:

- `mayor = 1` → variante al mayor; stock desde el producto base por `stock.id_stock`.
- `mayor = 0 AND id_producto_relacionado > 0` → corte/subproducto de ese padre.
- `rendimiento` es referencia en CANAL y cantidad sugerida en DETALLADO.

Es seguro porque el código actual que usa `id_producto_relacionado` filtra `mayor = 1`.

### 5.3 Por qué no usar `combo_items` ni `stock_ajustes` como cabecera

- `combo_items` calcula stock como el mínimo de los hijos y descuenta hijos al vender. Es lo inverso.
- `stock_ajustes` es el libro de mermas/ajustes; se usa para registrar la diferencia, no como cabecera del lote.

## 6. Flujos del modo CANAL

### 6.1 Registrar la res

Pantalla `publico/production/canal.php` + API `publico/production/db/canal_api.php`.

Formulario:

- Producto padre, sucursal, kg totales, costo total, proveedor.
- Checkbox "sumar al stock" (marcado por defecto; desmarcar si la compra ya lo sumó).
- Checkbox "poner cortes en venta".

Efectos en transacción:

- Crear `lotes` con `kg_inicial = kg_restante = kg`, `estado='ABIERTO'`.
- Sumar kg al `stock` del padre (si aplica).
- Guardar costo por kg en el padre.
- Reactivar cortes (`visible_venta=1`) si se marcó.
- Bloquear si ya hay un lote abierto del mismo padre/sucursal.

### 6.2 Vender un corte

Para el cajero no cambia la pantalla. Cambia el descuento:

- Si el producto es corte y el padre está en CANAL:
  1. Buscar el lote abierto del padre/sucursal con `FOR UPDATE`.
  2. Sin lote abierto → rechazar: "No hay res abierta".
  3. `kg_restante < cantidad` → rechazar: "La res no tiene kilos suficientes".
  4. `kg_restante -= cantidad`.
  5. `stock` del padre `-= cantidad`.
  6. Insertar `lotes_detalle` (`origen='VENTA'`, `producto_id=corte`, `orden_id`).
  7. No descontar el stock del corte.
- Si el producto es el padre y tiene lote abierto: descontar del lote. Si llega a 0, cerrarlo.
- Si es normal (sin padre): comportamiento actual.
- Al mayor: el factor es `cantidad × cantidad_unidades`; si el empaque es de 1 kg, es venta por kg.

Anulación: devolver la cantidad al lote y al stock del padre; si el lote ya se cerró, bloquear y resolver con ajuste.

### 6.3 Interruptor "En venta" / "Pausado"

- `stock.visible_venta` por corte y sucursal.
- No cambia los kg de la res; solo oculta y bloquea la venta.
- Al abrir una res nueva se reactivan los cortes (checkbox).
- "Agotar" = stock del corte a 0 + pausa + registro en `stock_ajustes` (motivo `AGOTADO`).

### 6.4 Cerrar la res (sin asumir merma)

Botón **Cerrar res**:

- Calcula `diferencia = kg_restante`.
- `lotes.diferencia_pendiente = diferencia`.
- `lotes.estado='CERRADO'`, `fecha_cierre`.
- **No** inserta `MERMA`.
- **No** modifica `stock`.
- Los cortes se pausan.
- La pantalla muestra:
  - "Res cerrada. Diferencia pendiente: X kg."
  - Botón **Registrar merma** → abre `ajustes_stock.php` con producto, sucursal, cantidad y motivo precargados.
  - Botón **Registrar ajuste** → mismo módulo, tipo ajuste.
  - Botón **Dejar pendiente** → queda visible en el listado de lotes.
- Cuando el usuario registra la merma/ajuste, el stock baja y el lote se puede marcar `conciliado=1` (manual o automático al coincidir).

Si `kg_restante = 0`, el cierre no tiene pendiente y no hay nada que conciliar.

### 6.5 Conteo final opcional

Al cerrar, un acordeón permite escribir kg reales por corte. Si se llena:

- Esos kg se pueden registrar como merma o devolver a stock como producto independiente.
- Si no se llena, no se asume nada: solo queda la diferencia pendiente.

### 6.6 Reporte de resultado

Mientras el lote está abierto:

- Kg iniciales, vendidos, restantes.
- Por corte: vendidos, esperados (`rendimiento × kg_inicial`), diferencia.
- Alertas de sobreventa o de corte sin ventas.
- Acceso directo al módulo de mermas para la diferencia.

Al cerrar queda el histórico: inicial, vendido, diferencia, conciliación, costo y margen.

## 7. Flujos del modo DETALLADO

1. Registrar la res igual que en CANAL.
2. **Despostar:** kg a procesar, cortes precargados con `rendimiento × kg`, editar kg reales, merma.
3. Validar `suma(cortes) + merma <= kg_entrada`.
4. `stock` padre `-= kg_entrada`; `kg_restante -= kg_entrada`; `stock` de cada corte `+= kg`; insertar `lotes_detalle` (`origen='DESPIECE'`).
5. La venta descuenta el stock del corte, no el lote.
6. Cerrar: la diferencia pendiente es `kg_restante` + merma de despieces; no se asume merma.
7. Anular despiece: solo si los cortes tienen stock suficiente para devolver.

## 8. Cambios por archivo

| Archivo | Cambio |
|---|---|
| `configurar/migrations/022_canal_subproductos.sql` | Columnas, tablas, índices y menú |
| `configurar/_seguimiento_estricto.php` | Helper: contexto, validación, registro de conciliación |
| `publico/production/canal.php` | Registrar res, lotes, cierre, pendientes, reporte |
| `publico/production/db/canal_api.php` | Abrir/cerrar lote, despostar, anular |
| `publico/production/nuevoProducto.php` | Unidad, modo, rendimiento, checkbox estricto |
| `configurar/agregarProducto.php` | Guardar unidad/modo/estricto; validar KG |
| `publico/production/productos.php` | Switch "En venta", filtro y edición de "Estricto" |
| `configurar/productos_gestor.php` | Guardar `seguimiento_estricto` |
| `publico/production/configuracion.php` | Toggle global de seguimiento estricto |
| `configurar/configuracion_sistema.php` | Actualizar `negocio.seguimiento_estricto` |
| `publico/production/nuevaCompra.php` | Modal de conciliación, etiqueta kg, cantidades decimales |
| `configurar/nuevaCompra.php` | Validar estricto, ajustar a conteo real, sumar compra, `floatval` |
| `publico/production/compras_facturas.php` | Modal de conciliación para líneas estrictas |
| `configurar/factura_compra_acciones.php` | Conciliaciones, `floatval`, subtotal decimal |
| `configurar/_factura_compra.php` | `fc_linea_total` con cantidades decimales |
| `configurar/modificar_stock.php` | Exigir motivo/registro en productos estrictos |
| `configurar/_ajustes_stock.php` | Motivos `AGOTADO`, `SOBRANTE_CANAL`, `CONTEO_FISICO` |
| `publico/production/ventas.php` | Filtro visible, "Res: X kg", decimales |
| `configurar/accion_carta.php` | Consumo del lote, validar visible/tope |
| `api/pos_catalogo.php` | Filtro visible, stock del lote, unidad/paso |
| `api/pos_ventas.php` | Consumo del lote, rechazar pausados, decimales |
| `api/pos_venta_anular.php` | Devolver al lote |
| `iseller_store/api/productos.php` | Filtro visible, stock del lote |
| `iseller_store/checkout.php` | Revalidar visible/tope |
| `iseller_store/core/accion_carta.php` | Consumo del lote |
| `iseller_store/api/v1/orders.php` | Validación de lote |
| `configurar/deleteProAjax.php` | Bloquear borrado de padre con cortes/lotes |
| `admin/inactivos.php` | Limpiar `lotes` y `lotes_detalle` |

## 9. Consulta del catálogo para CANAL

```sql
SELECT p.*, s.stock,
       pp.modo_control,
       l.kg_restante AS kg_res,
       CASE
         WHEN pp.modo_control = 'CANAL'
           THEN COALESCE(l.kg_restante, 0)
         ELSE s.stock
       END AS stock_visible
FROM productos p
INNER JOIN stock s ON s.id_producto = p.id AND s.id_sucursal = ? AND s.bss_id = ?
LEFT JOIN productos pp ON pp.id = p.id_producto_relacionado AND pp.mayor = 0
LEFT JOIN lotes l ON l.padre_id = pp.id
     AND l.id_sucursal = s.id_sucursal
     AND l.bss_id = s.bss_id
     AND l.estado = 'ABIERTO'
WHERE p.activo = 0 AND s.visible_venta = 1;
```

En el POS, un corte CANAL muestra "Res: X kg". La variante al mayor divide `kg_res / cantidad_unidades`.

## 10. Reportes

- **Stock por corte:** kg y valor, por sucursal.
- **Ventas por corte:** kg, $, margen. `orden_articulos` ya guarda producto y cantidad; ahora con `quantity` decimal.
- **Resultado del lote:** inicial, vendido, diferencia pendiente, conciliación, rendimiento real vs esperado.
- **Diferencias pendientes:** listado de lotes cerrados con `diferencia_pendiente > 0` y `conciliado = 0`, con enlace al módulo de mermas.
- **Seguimiento estricto:** últimas compras, conteos, diferencias y clasificación (ajuste/merma) por producto.
- **Mermas:** sigue siendo `stock_ajustes` con `tipo='MERMA'`; el cierre no inserta nada automáticamente.

## 11. Ideas para carne sin trabajo diario

1. **Rendimiento real por proveedor:** comparar kg vendidos vs esperados al cerrar. En 3 reses se ve qué proveedor rinde más.
2. **Días de stock por corte:** con ventas de 30 días y rendimiento esperado, estimar cuántos días queda.
3. **Sugerencia de próximo desposte:** si la res está por cerrarse y la velocidad de venta indica 2 días, avisar.
4. **Alerta de sobreventa por corte:** si vendió más de su rendimiento esperado, revisar venta no registrada o error.
5. **Merma esperada vs real:** la plantilla da una merma esperada; el cierre da la real; la diferencia es el faltante no explicado.
6. **Pausa asistida:** al llegar al rendimiento esperado, sugerir pausar; el carnicero confirma.
7. **Precio por kg vs por pieza:** unidad `KG` o `UND` por producto.
8. **Báscula:** si es teclado HID, el input ya recibe el peso; serial en fase 3.
9. **Etiquetas por peso:** fase 3.
10. **Parrilladas:** se siguen usando los combos actuales.

## 12. Fases

### Fase 0 — Preparación (1 día)

- Migración 022, backfill `visible_venta=1`, `unidad='UND'`, `modo_control='NORMAL'`, `seguimiento_estricto=0`.
- Migrar `orden_articulos.quantity` a decimal.
- Backfill de `ultima_compra_at`/`ultima_compra_kg` desde la última compra de `compras` por producto/sucursal (script).
- Entrada de menú.

### Fase 1 — CANAL + seguimiento estricto (5 a 7 días)

- Registrar res y abrir lote.
- Configurar cortes con rendimiento de referencia.
- Vender cortes consumiendo el lote y el tope global.
- Interruptor por corte.
- Cerrar res con diferencia pendiente, sin merma automática.
- Enlace al módulo de mermas/ajustes.
- Toggle global + checkbox por producto.
- Conciliación en compra simple y con factura.
- Reporte en vivo y al cierre.
- POS web, POS nativo (endpoints) y tienda con visibilidad y decimales.

### Fase 2 — DETALLADO (3 a 5 días)

- Pantalla de desposte con kg reales y merma.
- Stock propio por corte; venta descuenta corte.
- Anulación de despiece.
- Costeo por lote y margen por corte.

### Fase 3 — Opcional

- Varios lotes abiertos con FIFO.
- Báscula serial y etiquetas por peso.
- Trazabilidad formal por proveedor/lote.
- Planeación de producción.

## 13. Pruebas de aceptación

**CANAL**

1. Registrar res de 1000 kg: lote abierto, padre 1000, cortes en 0 y visibles.
2. Vender 10 kg de carne: res 990, carne sigue en 0, detalle `VENTA` de 10 kg.
3. Intentar vender 995 kg más: se rechaza por tope (máximo total 1000).
4. Pausar costilla: desaparece del POS y se rechaza su venta; los demás siguen.
5. Cerrar res con 60 kg restantes: `diferencia_pendiente=60`, **no** hay fila `MERMA` nueva, stock sigue en 60.
6. Registrar la merma en el módulo existente: stock baja a 0, lote conciliado.
7. Abrir res nueva: cortes reactivados.
8. Vender al mayor una caja de 10 kg: el lote baja 10 kg.
9. Sin lote abierto: la venta de un corte se rechaza.

**Seguimiento estricto**

10. Producto estricto con stock 360, última compra 1000 kg y 640 vendidos: al comprar, el modal muestra 360 esperados.
11. Conteo real 355 y clasificación AJUSTE: se registra `stock_ajustes` tipo AJUSTE por -5; stock final 355 + compra.
12. Conteo real 355 y clasificación MERMA: se registra tipo MERMA por -5; aparece en el módulo de mermas.
13. Cancelar la conciliación: la compra no se guarda.
14. Producto no estricto: la compra no muestra modal.
15. Global apagado: los productos marcados no se controlan.
16. Factura con dos líneas estrictas: exige las dos conciliaciones antes de crear la factura.
17. `modificar_stock` sobre un producto estricto: exige motivo y registra el ajuste.
18. Compra de 0.5 kg: no se trunca a 0; el stock y el subtotal son correctos.

**DETALLADO**

19. Despostar 1000 kg: cortes con stock, padre 0, lote 0.
20. Vender 0.750 kg: baja el stock del corte, no el lote.
21. Anular despiece: restaura padre y descuenta cortes; se bloquea si ya se vendió.

**General**

22. Combo existente sigue igual.
23. Producto al mayor existente sigue descontando del base.
24. Sucursal A pausa un corte; sucursal B sigue vendiéndolo.

## 14. Decisiones a confirmar

1. ¿Una res abierta por sucursal o varias? Recomendado: una.
2. ¿La res se vende directamente? El plan lo soporta.
3. ¿El mayor es por kg o por empaque? `cantidad_unidades=1` para kg.
4. ¿La diferencia de conciliación se clasifica como AJUSTE por defecto o se pregunta siempre? Recomendado: preguntar, con AJUSTE preseleccionado.
5. ¿Se permite dejar pendiente una compra estricta? Recomendado: no; la compra se bloquea hasta conciliar. El cierre de res sí puede quedar pendiente.
6. ¿La nueva compra se suma al lote abierto o abre uno nuevo? Preguntar; por defecto, lote nuevo si la res anterior ya tuvo ventas.
7. ¿Se actualiza la app nativa en Fase 1? El servidor queda listo; la UI de kg requiere release.

## 15. Riesgos

- **Ventas por fuera del POS:** se reflejan como diferencia pendiente. El reporte de rendimiento las delata; no las impide.
- **Corte agotado sin pausar:** el tope global permite vender hasta agotar la res. El interruptor es la defensa; la alerta de sobreventa lo detecta.
- **Desincronización padre/lote:** actualizar `stock` y `kg_restante` en la misma transacción con `FOR UPDATE`.
- **POS offline:** el catálogo cacheado puede mostrar kg viejos; el servidor rechaza al sincronizar si el lote ya no alcanza.
- **Puerta trasera de `modificar_stock`:** si no se registra el ajuste, el seguimiento estricto se puede saltar.
- **Datos históricos:** `ultima_compra_at` nace vacío; el primer control estricto no tendrá contexto hasta la primera compra nueva. El backfill desde `compras` reduce el hueco.
- **Costo global por producto:** `productos.precio_compra` no es por sucursal; el margen es aproximado hasta fase 2.
