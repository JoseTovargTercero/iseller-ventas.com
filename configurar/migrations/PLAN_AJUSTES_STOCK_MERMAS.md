# PLAN: MÓDULO "AJUSTES DE STOCK Y MERMAS"

## 1. Objetivo

Registrar y consultar las pérdidas de inventario del negocio (mermas y ajustes de
conteo) con su valor monetario, clasificadas por sucursal, categoría de producto
y producto específico, con cortes semanales y mensuales.

El módulo reutiliza lo que ya existe:

- Tabla `stock` y cálculo de costo de `valor_stock.php`.
- Categorías de producto de `categorias_producto` (columna `productos.categoria`).
- Patrón de permisos por menú (`menu` + `users_permisos`) de `valor_stock.php`.
- Estilos de `theme.css`, ApexCharts y Fuse ya incluidos en el proyecto.

No se crea una dependencia nueva.

---

## 2. Decisiones de diseño

1. **Un solo libro de movimientos**, tabla `stock_ajustes`. Cada fila es un
   producto afectado. No hay cabecera + detalle: registrar 3 productos son 3
   filas. Esto hace triviales los reportes por producto, categoría, sucursal,
   semana y mes.
2. **Tipo y dirección**:
   - `tipo = 'MERMA'` siempre `direccion = 'SALIDA'`.
   - `tipo = 'AJUSTE'` puede ser `SALIDA` (conteo menor al sistema) o `ENTRADA`
     (conteo mayor al sistema).
   - "Pérdida" en los reportes = toda fila con `direccion = 'SALIDA'` y
     `estado = 'ACTIVO'`. El desglose separa MERMA de AJUSTE.
3. **Costo congelado al registrar**: se guarda `costo_unitario` y `monto_usd`
   históricos, igual que `asignaciones_sucursales_detalles`. Cambiar el precio
   del producto después no altera los reportes pasados.
4. **Fórmula de costo**: `precio_compra / IFNULL(NULLIF(cantidad_unidades,0),1)`,
   la misma de `valor_stock.php`. El monto se expresa en USD, igual que el resto
   del sistema.
5. **Trazabilidad completa de cada registro**:
   - `motivo`: clasificación fija (`VENCIMIENTO`, `ROTURA`, etc.).
   - `razon`: explicación libre que escribe el usuario, obligatoria.
   - `usuario_id` + `usuario_nombre`: quién hizo el ajuste, tomado de la
     sesión; el navegador no puede enviar otro usuario.
   - `monto_usd`: valor en dólares de la pérdida
     (`cantidad × costo_unitario`), visible en la tabla y en todos los reportes.
6. **Solo productos base** (`productos.mayor IS NULL OR mayor = 0`). Los productos
   "al mayor" son punteros a la fila base (`stock.mayor = 1`, `stock.id_stock`);
   sus mermas se registran sobre el producto base. `shortcut: no se ajustan
   punteros al mayor, ampliar si se necesita mermar pacas completas`.
7. **Motivos fijos** en un arreglo PHP. No se crea tabla de motivos.
   `shortcut: motivos fijos, crear tabla si el negocio necesita configurarlos`.

### 2.1 Aclaración de las decisiones operativas

**"Un producto por registro (sin carga masiva)"**

Cada vez que se guarda el formulario se crea **una fila de `stock_ajustes` para
un solo producto**. Si se dañaron 3 productos, se registran 3 veces. No existe
un documento con cabecera y varias líneas.

Por qué: el 90% de las mermas se registra en el momento en que ocurren y sobre
un producto concreto; el flujo de una fila por producto permite guardar cada
una de forma atómica (stock + registro en la misma transacción) sin inventar un
estado "borrador" ni una tabla de detalle. Los reportes por producto, categoría
y sucursal no necesitan agrupar cabeceras. Si más adelante se quiere cargar un
inventario completo de una vez, se agrega un documento con detalle; la tabla de
movimientos no cambia.

**Consulta de un producto específico:** como cada fila guarda `producto_id`,
`producto_nombre` y `categoria_nombre`, el filtro "Producto" de la pantalla
agrega `m.producto_id = ?` a todas las consultas (KPIs, series, categorías,
top productos y tabla). Ejemplo: seleccionar "Muslo" muestra únicamente las
mermas y ajustes de ese producto, con sus fechas, cantidades y valor en $.

**"Los ajustes negativos cuentan como pérdida pero se separan de las mermas"**

- **Merma** (`tipo = MERMA`): pérdida conocida y con causa (se venció, se
  rompió, se lo robaron). Siempre resta stock.
- **Ajuste** (`tipo = AJUSTE`): corrección porque el conteo físico no coincide
  con el sistema. Si el conteo da menos (`SALIDA`), también es una pérdida
  real de inventario; si da más (`ENTRADA`), es un sobrante y no es pérdida.
- En los totales, **"Pérdida total" suma mermas + ajustes de SALIDA**, porque
  ambas reducen el valor del inventario. Pero el reporte y la tabla muestran el
  desglose separado (`MERMA` vs `AJUSTE`) para saber cuánto se perdió por causa
  conocida y cuánto por descuadre de conteo. Así no se mezcla una merma de
  pollo vencido con un error de inventario.
- La tarjeta "Pérdida de mermas" puede filtrarse a `tipo = MERMA` si el negocio
  solo quiere ver esa causa.

**"Solo se ajustan productos base (los al mayor se registran sobre su producto base)"**

En este sistema, un producto "al mayor" es un **puntero**: su fila en `stock`
tiene `mayor = 1` e `id_stock` apuntando a la fila del producto base, y su
contador propio arranca en 0. El stock real vive en la fila base. Por eso:

- El buscador del módulo solo muestra productos base (`mayor IS NULL OR mayor = 0`).
- Si se dañó una paca, se registra la merma sobre el producto base indicando la
  cantidad de unidades sueltas que representa (por ejemplo, 1 paca de 12
  unidades = 12 unidades).
- Así se descuenta una sola fila de stock y el valor de la pérdida se calcula
  con el costo unitario real, sin arriesgar descuadres entre el puntero y la
  fila base.

Si el negocio necesita mermar pacas como unidad, se amplía el módulo para
resolver el puntero y aplicar el factor de conversión, igual que hace
`asig_aplicar_stock()` en las asignaciones entre sucursales.

---

## 3. Modelo de datos

Archivo: `configurar/migrations/020_ajustes_stock_mermas.sql`

```sql
CREATE TABLE IF NOT EXISTS `stock_ajustes` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `bss_id`            INT(11)       NOT NULL,
  `id_sucursal`       INT(11)       NOT NULL,
  `producto_id`       INT(11)       NOT NULL,
  `producto_nombre`   VARCHAR(255)  NOT NULL COMMENT 'Copia histórica',
  `categoria_id`      INT(11)       DEFAULT NULL,
  `categoria_nombre`  VARCHAR(150)  NOT NULL DEFAULT 'Sin categoría' COMMENT 'Copia histórica',
  `tipo`              ENUM('MERMA','AJUSTE') NOT NULL,
  `direccion`         ENUM('SALIDA','ENTRADA') NOT NULL,
  `motivo`            VARCHAR(40)   NOT NULL COMMENT 'Clasificación fija: VENCIMIENTO, ROTURA, etc.',
  `razon`             TEXT          NOT NULL COMMENT 'Explicación escrita por el usuario',
  `cantidad`          DECIMAL(15,4) NOT NULL COMMENT 'Siempre positiva; la dirección indica el signo',
  `costo_unitario`    DECIMAL(15,4) NOT NULL DEFAULT 0,
  `monto_usd`         DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Valor en $ de la pérdida = cantidad * costo_unitario',
  `stock_antes`       DECIMAL(15,4) NOT NULL DEFAULT 0,
  `stock_despues`     DECIMAL(15,4) NOT NULL DEFAULT 0,
  `fecha`             DATE          NOT NULL,
  `estado`            ENUM('ACTIVO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `usuario_id`        INT(11)       NOT NULL COMMENT 'Quién registra el ajuste ($_SESSION[id])',
  `usuario_nombre`    VARCHAR(150)  NOT NULL COMMENT 'Copia histórica del usuario',
  `usuario_anulacion` INT(11)       DEFAULT NULL,
  `fecha_anulacion`   DATETIME      DEFAULT NULL,
  `motivo_anulacion`  TEXT          DEFAULT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aj_bss_fecha`      (`bss_id`, `fecha`),
  KEY `idx_aj_bss_suc_fecha`  (`bss_id`, `id_sucursal`, `fecha`),
  KEY `idx_aj_bss_prod_fecha` (`bss_id`, `producto_id`, `fecha`),
  KEY `idx_aj_bss_cat_fecha`  (`bss_id`, `categoria_id`, `fecha`),
  KEY `idx_aj_bss_tipo`       (`bss_id`, `tipo`, `direccion`),
  CONSTRAINT `chk_aj_cantidad` CHECK (`cantidad` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Menú (misma migración):

```sql
INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'stock', 'Ajustes de stock y mermas', 'ajustes_stock.php',
       'trending-down-outline', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'ajustes_stock.php'
);
```

Notas:

- No hay FK a `productos` ni a `categorias_producto` porque los nombres son
  copias históricas y el producto puede desactivarse o eliminarse.
- `bss_id` y `id_sucursal` se validan siempre contra la sesión.
- Los permisos de nivel 2 se asignan después desde `permisos.php` (el menú ya
  alimenta esa pantalla).

---

## 4. Reglas de negocio

### 4.1 Registrar merma

1. Producto obligatorio, activo (`productos.activo = 0`), del negocio y con fila
   de `stock` en la sucursal.
2. `cantidad > 0`, en unidades de stock.
3. `cantidad <= stock actual`; nunca dejar stock negativo.
4. Motivos: `VENCIMIENTO`, `DETERIORO`, `ROTURA`, `ROBO_HURTO`,
   `CONTAMINACION`, `CONSUMO_INTERNO`, `ERROR_INVENTARIO`, `OTRO`.
5. `razon` obligatoria: explicación del usuario (por qué se perdió, qué pasó).
   No es lo mismo que el motivo: el motivo clasifica, la razón explica. Se
   guarda tal cual, con `strip_tags` y máximo 500 caracteres.
6. `monto_usd = ROUND(cantidad * costo_unitario, 2)`.
7. `stock_despues = stock_antes - cantidad`.
8. `usuario_id` y `usuario_nombre` se toman de `$_SESSION`; el POST no puede
   cambiarlos.

### 4.2 Registrar ajuste por conteo

1. Recibe `stock_real >= 0`.
2. `diferencia = stock_real - stock_actual`.
3. Si `|diferencia| < 0.0001`, se rechaza: no hay nada que ajustar.
4. `direccion = ENTRADA` si `diferencia > 0`, `SALIDA` si es menor.
5. `cantidad = |diferencia|`; `stock_despues = stock_real`.
6. Motivos: `CONTEO_FISICO`, `CORRECCION`, `OTRO`.
7. `razon` obligatoria: el usuario explica por qué el conteo no coincidía.
8. `monto_usd = ROUND(cantidad * costo_unitario, 2)`.
9. Los ajustes de SALIDA cuentan como pérdida; los de ENTRADA no.
10. `usuario_id` y `usuario_nombre` se toman de `$_SESSION`.

### 4.3 Valor en dólares de la pérdida

- Toda fila de `SALIDA` tiene un `monto_usd` que **es** el valor en dólares de
  la pérdida de ese movimiento.
- Toda fila de `ENTRADA` también guarda su `monto_usd`, pero se muestra como
  "valor del sobrante", nunca como pérdida.
- El símbolo `$` se agrega al mostrar (no se guarda en la base), con
  `number_format($valor, 2, '.', ',')`.
- El monto se calcula y se guarda al registrar; no se recalcula al listar. Así
  el reporte histórico no cambia si luego cambia el precio del producto.

### 4.4 Anulación

- Solo sobre registros `ACTIVO`.
- Revierte el inventario en la misma transacción:
  - Si era `SALIDA`, suma `cantidad` al stock.
  - Si era `ENTRADA`, resta `cantidad`; si el stock actual no alcanza, se
    rechaza con mensaje.
- Guarda `usuario_anulacion`, `fecha_anulacion`, `motivo_anulacion` y
  `estado = 'ANULADO'`. No se borra la fila. El usuario original
  (`usuario_nombre`) se conserva intacto.
- Mismo alcance de sucursal que el registro: nivel 1 cualquiera del negocio,
  nivel 2 solo la suya.

### 4.5 Fecha

- `fecha` no puede ser futura.
- Se permite registrar con fecha pasada (conteos hechos antes de cargarlos).
- Las semanas son ISO (`lunes` a `domingo`), igual que `date('o-W')` y
  `DATE_FORMAT(fecha, '%x-W%v')`.

---

## 5. Archivos

| Archivo | Rol |
|---|---|
| `configurar/migrations/020_ajustes_stock_mermas.sql` | Tablas + menú |
| `publico/production/ajustes_stock.php` | Página: KPIs, filtros, gráficos, tabla, modales |
| `publico/production/db/ajustes_stock_api.php` | JSON: `resumen`, `listar`, `productos`, `registrar`, `anular` |

No se agregan archivos JS ni CSS: el JS va inline como en `gastos.php` y
`rendimiento_categoria.php`.

---

## 6. API

`POST publico/production/db/ajustes_stock_api.php`
(sesión válida, `bss_id` obligatorio, permisos de nivel 2 igual que
`db/rendimiento_categoria_api.php`).

Parámetros comunes de filtro:

| Parámetro | Valores |
|---|---|
| `accion` | `resumen`, `listar`, `productos`, `registrar`, `anular` |
| `sucursal` | `0` = todas (solo nivel 1); nivel 2 se fuerza a la suya |
| `desde`, `hasta` | `YYYY-MM-DD` |
| `categoria_id` | `0` = todas |
| `producto_id` | `0` = todos |
| `tipo` | `TODAS`, `MERMA`, `AJUSTE` |
| `pagina` | entero, 50 filas por página (`listar`) |

### `resumen`

Devuelve todo lo de la parte superior de la pantalla en una sola llamada:

```json
{
  "ok": true,
  "kpis": {
    "perdida_semana_usd": 0,
    "perdida_mes_usd": 0,
    "unidades_mes": 0,
    "registros_mes": 0,
    "variacion_mes_pct": 0,
    "merma_mes_usd": 0,
    "ajuste_negativo_mes_usd": 0
  },
  "serie_semanal": [{ "periodo": "2026-W45", "monto_usd": 0, "unidades": 0 }],
  "serie_mensual": [{ "periodo": "2026-10", "monto_usd": 0, "unidades": 0 }],
  "por_categoria": [{ "categoria_id": 3, "categoria": "Pollo", "monto_usd": 0, "unidades": 0 }],
  "por_sucursal": [{ "id_sucursal": 1, "sucursal": "Principal", "monto_usd": 0 }],
  "top_productos": [{ "producto_id": 8, "producto": "Pollo entero", "monto_usd": 0, "unidades": 0 }]
}
```

- KPIs de semana y mes actuales con los filtros de sucursal/categoría/producto/
  tipo aplicados. Todos los valores monetarios son el **valor en $ de la
  pérdida**; el desglose `merma_mes_usd` + `ajuste_negativo_mes_usd` debe sumar
  `perdida_mes_usd`.
- `serie_semanal`: últimas 8 semanas ISO.
- `serie_mensual`: últimos 6 meses.
- `por_categoria`: periodo `desde`/`hasta` (por defecto mes actual).
- `por_sucursal`: solo si el usuario es nivel 1.
- `top_productos`: 10 primeros del periodo por monto.

### `listar`

Devuelve `{ ok, total, filas }` con:
`id, fecha, sucursal, producto, categoria, tipo, direccion, motivo, razon,
cantidad, costo_unitario, monto_usd, stock_antes, stock_despues, usuario,
estado`.

Orden: `fecha DESC, id DESC`. Paginación con `LIMIT ? OFFSET ?`.

### `productos`

Lista para el buscador del modal (se carga completa una vez y se filtra con
Fuse en el navegador, igual que `modificar_stock.php`):

```json
[{ "id": 8, "nombre": "Pollo entero", "categoria": "Pollo",
   "stock": 14, "costo_unitario": 3.25 }]
```

```sql
SELECT p.id, p.nombre, s.stock,
       COALESCE(cp.nombre, 'Sin categoría') AS categoria,
       ROUND(p.precio_compra / IFNULL(NULLIF(p.cantidad_unidades,0),1), 4) AS costo_unitario
FROM productos p
INNER JOIN stock s
  ON s.id_producto = p.id
 AND s.id_sucursal = ?
 AND s.bss_id = ?
LEFT JOIN categorias_producto cp ON cp.id = p.categoria
WHERE p.bss_id = ? AND p.activo = 0
  AND (p.mayor IS NULL OR p.mayor = 0)
ORDER BY p.nombre ASC
```

La fila de stock se toma como en `asig_get_stock()`:
`ORDER BY id ASC LIMIT 1 FOR UPDATE` dentro de la transacción.

### `registrar`

```json
{ "accion": "registrar", "tipo": "MERMA", "sucursal": 1,
  "producto_id": 8, "cantidad": 2, "motivo": "VENCIMIENTO",
  "razon": "Se venció el lote que estaba al fondo de la nevera",
  "fecha": "2026-11-07" }
```

Para `AJUSTE` se envía `stock_real` en lugar de `cantidad`.

Flujo del servidor:

1. Validar sesión, permiso y sucursal.
2. `begin_transaction()`.
3. Leer producto con `SELECT ... FOR UPDATE` sobre su fila de stock.
4. Validar cantidades, motivo y que `razon` no venga vacía.
5. `UPDATE stock SET stock = ? WHERE id = ?`.
6. `INSERT INTO stock_ajustes (...)` con `usuario_id` y `usuario_nombre`
   tomados de `$_SESSION`, no del POST.
7. `commit()`; en cualquier fallo `rollback()` y mensaje claro.
8. Devolver `{ ok, id, stock_despues, monto_usd, usuario }` para refrescar la
   pantalla sin recargar.

### `anular`

`{ accion: "anular", id: 123, motivo: "Registrado por error" }`

Lee el registro `FOR UPDATE`, verifica alcance, revierte stock y marca
`ANULADO` en la misma transacción.

---

## 7. Pantalla (`ajustes_stock.php`)

### 7.1 Estructura

```
right_col
├── encabezado: título + botón "Registrar merma"
├── filtros: [Sucursal] [Periodo: semana actual|mes actual|rango] [Categoría]
│            [Producto (Fuse)] [Tipo] [Limpiar]
├── 4 tarjetas KPI
│   ├── Pérdida esta semana ($)
│   ├── Pérdida este mes ($, desglose merma vs ajuste, + variación vs mes anterior)
│   ├── Unidades perdidas este mes
│   └── Categoría con mayor pérdida (mes, $)
├── fila de gráficos
│   ├── ApexCharts barras: semanal / mensual (toggle), eje en $
│   └── ApexCharts dona: por categoría, en $
├── fila de análisis
│   ├── barras horizontales: por sucursal (solo nivel 1), en $
│   └── tabla Top 10 productos con pérdida, en $
├── tabla de movimientos + paginación
└── modales: registrar, anular
```

### 7.2 Modal "Registrar"

- Selector `tipo` (MERMA / AJUSTE).
- Sucursal: select si nivel 1; fija y oculta si nivel 2 (mismo patrón que
  `modificar_stock.php`).
- Producto: input de filtro + select alimentado por Fuse.
- Al elegir producto muestra stock actual y costo unitario (solo lectura).
- Si MERMA: campo cantidad + **valor en $ de la pérdida** calculado en vivo
  (`cantidad × costo_unitario`).
- Si AJUSTE: campo "stock real" + diferencia, dirección y valor en $ calculados
  en vivo.
- Motivo: select que cambia según el tipo.
- **Razón / explicación** (`textarea` obligatorio, máximo 500 caracteres):
  "¿Por qué se produjo la merma o el descuadre?".
- **Usuario que registra**: se muestra en solo lectura, tomado de la sesión
  (`$_SESSION['nombre']`); no es editable.
- Fecha (máximo hoy).
- Botón guardar deshabilitado hasta que los campos sean válidos.

### 7.3 Filtros y tabla

- Periodo por defecto: mes actual. Atajos "Semana actual" y "Mes actual".
- Nivel 2: no ve el selector de sucursal ni la gráfica por sucursal.
- Columnas de la tabla: Fecha, Sucursal, Producto, Categoría, Tipo, Motivo,
  Razón (truncada con tooltip), Cantidad, Costo unitario, **Valor pérdida ($)**,
  Usuario, Estado, Acciones.
- Badges de color: rojo para SALIDA, verde para ENTRADA; los ajustes de
  ENTRADA muestran su valor como "sobrante", no como pérdida.
- Botón anular solo en filas activas; muestra el usuario original y pide el
  motivo de anulación.
- Totales al pie: unidades y **valor total en $** de las filas visibles.
- Responsive con las variables de `theme.css`; en móvil la tabla se convierte
  en tarjetas, como en `permisos.php` y `gastos.php`.

---

## 8. Consultas de reporte

Filtro base reutilizable:

```php
$where  = ["m.bss_id = ?", "m.estado = 'ACTIVO'"];
$params = [$bss_id];
// + m.id_sucursal = ?, m.fecha BETWEEN ? AND ?,
//   m.categoria_id = ?, m.producto_id = ?, m.tipo = ?
```

KPI semana / mes (fechas calculadas en PHP para no depender de la zona horaria
de MySQL):

```sql
SELECT
  COALESCE(SUM(CASE WHEN direccion='SALIDA' THEN monto_usd END), 0) AS perdida_usd,
  COALESCE(SUM(CASE WHEN direccion='SALIDA' THEN cantidad END), 0) AS unidades,
  COALESCE(SUM(CASE WHEN tipo='MERMA' AND direccion='SALIDA' THEN monto_usd END), 0) AS merma_usd,
  COALESCE(SUM(CASE WHEN tipo='AJUSTE' AND direccion='SALIDA' THEN monto_usd END), 0) AS ajuste_usd
FROM stock_ajustes
WHERE <filtros> AND fecha BETWEEN ? AND ?;
```

Serie semanal (8 semanas):

```sql
SELECT DATE_FORMAT(fecha, '%x-W%v') AS periodo,
       SUM(monto_usd) AS monto_usd,
       SUM(cantidad) AS unidades
FROM stock_ajustes
WHERE <filtros> AND direccion='SALIDA'
  AND fecha >= ?
GROUP BY periodo
ORDER BY periodo ASC;
```

Serie mensual (6 meses):

```sql
SELECT DATE_FORMAT(fecha, '%Y-%m') AS periodo,
       SUM(monto_usd) AS monto_usd,
       SUM(cantidad) AS unidades
FROM stock_ajustes
WHERE <filtros> AND direccion='SALIDA'
  AND fecha >= ?
GROUP BY periodo
ORDER BY periodo ASC;
```

Por categoría / por sucursal / top productos: mismo filtro, cambiando
`GROUP BY m.categoria_id, m.categoria_nombre` /
`m.id_sucursal` /
`m.producto_id, m.producto_nombre`, con `ORDER BY monto_usd DESC`.

El listado agrega `m.razon` y `m.usuario_nombre` directamente de la tabla; no
necesita `JOIN` con `usuarios` porque el nombre quedó congelado al registrar.

Todas con `prepare()` y `bind_param()`, sin concatenar valores del usuario.
La tabla no tiene `semana`/`mes` almacenados: se derivan de `fecha`, que es la
única fuente de verdad.

---

## 9. Seguridad y multi-negocio

- Toda consulta filtra por `bss_id` de sesión.
- Nivel 2: `id_sucursal` se fuerza desde la sesión, se ignora el POST.
- Nivel 1: la sucursal enviada se valida contra `sucursales.bss_id`.
- Validar que el producto pertenece al negocio y está activo.
- `mysqli_report` está apagado: revisar siempre el retorno de prepare/execute y
  usar transacciones para no dejar stock y registro desincronizados.
- Escapar/serializar JSON con `JSON_UNESCAPED_UNICODE` como el resto del panel.
- No se agrega CSRF: el proyecto usa `SameSite=Strict` en la cookie de sesión y
  los módulos existentes no envían token.

---

## 10. Fases de implementación

### Fase 1 — Base de datos y API

1. Crear `020_ajustes_stock_mermas.sql` y ejecutarlo.
2. Crear `db/ajustes_stock_api.php` con `productos`, `registrar`, `listar`.
3. Probar con `curl`/Postman: merma resta stock, ajuste fija stock, montos en $
   correctos, `razon` obligatoria, `usuario_nombre` tomado de la sesión y
   transacción revertida ante error.

### Fase 2 — Página y filtros

1. Crear `ajustes_stock.php` con encabezado, filtros, tabla y paginación.
2. Implementar `anular` y su modal.
3. Verificar alcance de sucursal para nivel 1 y nivel 2.

### Fase 3 — KPIs y reportes

1. Implementar `resumen` (KPIs, series, categorías, sucursales, top productos).
2. Pintar las 4 tarjetas y las 3 visualizaciones con ApexCharts.
3. Validar que los totales de la tabla coincidan con los KPIs del periodo.

### Fase 4 — Pulido

1. Responsive en 390 px (tabla como tarjetas, filtros apilados).
2. Mensajes de error claros (stock insuficiente, producto sin stock, sin
   diferencia, ya anulado).
3. Revisar permisos desde `permisos.php` con un usuario nivel 2.

---

## 11. Pruebas de aceptación

- [ ] Registrar merma descuenta exactamente la cantidad y guarda
      `monto_usd = cantidad * costo_unitario`.
- [ ] La razón es obligatoria: sin explicación no se guarda el registro.
- [ ] El usuario guardado es el de la sesión; enviar otro `usuario_id` por POST
      no cambia el resultado.
- [ ] El valor en $ de la pérdida coincide con cantidad × costo unitario y se
      muestra con formato de moneda.
- [ ] El desglose merma + ajuste negativo suma la pérdida total del mes.
- [ ] No se puede mermar más que el stock actual; el stock nunca queda negativo.
- [ ] Ajuste con `stock_real` mayor crea ENTRADA y no suma a pérdidas.
- [ ] Ajuste con `stock_real` menor crea SALIDA y sí suma a pérdidas.
- [ ] Ajuste sin diferencia se rechaza.
- [ ] Anular una merma devuelve el stock; anular dos veces se bloquea.
- [ ] Anular una entrada que dejaría stock negativo se bloquea.
- [ ] Anular conserva la razón y el usuario originales del registro.
- [ ] Nivel 2 solo ve y registra en su sucursal aunque manipule el POST.
- [ ] Filtros por sucursal, categoría, producto, tipo y rango coinciden con las
      sumas de la tabla.
- [ ] Semana ISO correcta en domingo y en cambio de año.
- [ ] Producto sin categoría aparece como "Sin categoría".
- [ ] Producto desactivado conserva su historial y no aparece para registrar.
- [ ] Dos mermas simultáneas no sobrepasan el stock (`FOR UPDATE`).
- [ ] La página se ve bien a 390 px y 1280 px.
- [ ] Usuario nivel 2 sin permiso es redirigido; con permiso ve el módulo.

---

## 12. Fuera de alcance (por ahora)

- Exportar a Excel/PDF (se puede copiar el patrón de `valor_stock.php` luego).
- Motivos configurables por negocio.
- Registro masivo de varios productos en un solo documento.
- Mermas de productos "al mayor" sobre su fila puntero.
- Alertas automáticas o envío por push.
- Integración en el cálculo de ganancias del dashboard: este módulo es un
  reporte independiente; si se quiere restar la merma de la ganancia neta, se
  agrega después en `consulta_gastosCount.php`.
- Endpoints para la app Android (`api/v1`).

---

## 13. Riesgos y notas

- El valor usa el costo de compra en USD tal como lo hace `valor_stock.php`; no
  reconvierte tasas de cambio. Si el negocio carga `precio_compra` en otra
  moneda, el monto de la merma hereda esa inconsistencia existente.
- Un ajuste de ENTRADA crea stock sin una compra asociada: es una corrección de
  inventario, no una compra; no aparecerá en reportes de compras.
- Si se elimina la fila de `stock` de un producto, anular su merma fallará; el
  registro histórico se conserva en estado ACTIVO con el stock no revertido.
  Aceptable para el alcance actual.
- `razon` es texto libre: se escapa con `htmlspecialchars` al pintarlo en la
  tabla y en los tooltips para evitar XSS. No se permite HTML.
- El nombre del usuario se guarda como copia histórica; si luego se renombra o
  elimina la cuenta, el registro sigue mostrando quién lo hizo.
- Los motivos y la fórmula de costo deben confirmarse con el negocio antes de
  implementar si usan precios por caja/paca de forma distinta a `valor_stock.php`.
