# Plan de Implementación — Módulo de Gastos (iSeller POS)

> Sistema: PHP + mysqli · Multitenant (`bss_id`) · Multisucursal (`id_sucursal`)

---

## 1. Diagnóstico técnico del sistema existente

### 1.1 Base de datos (`iseller`)

#### Tablas relevantes

| Tabla | Propósito | Columnas clave |
|---|---|---|
| `usuarios` | Usuarios del sistema | `id`, `nombre`, `usuario`, `nivel`, `bss_id`, `id_sucursal`, `creado` |
| `orden` | Ventas | `id`, `status`, `usuario`, `total_price` (USD), `fecha` (Y-m), `semana` (Y-W), `dia` (Y-m-d), `id_sucursal`, `bss_id` |
| `orden_articulos` | Detalle de ventas | `id`, `order_id`, `product_id`, `quantity`, `precio` (costo USD), `precio_venta_dolar`, `id_sucursal`, `bss_id` |
| `sucursales` | Sucursales | `id`, `bss_id`, `tipo`, `nombre`, `principal` |
| `cambio` | Tasas activas | `id`, `pesoDolar`, `DolarBolivar`, `bss_id` |
| `cambios_bcv_historico` | Histórico BCV | `id`, `valor`, `time` |
| `categorias` | Categorías de productos | `id`, `nombre`, `activo` — **NO usar para gastos** |
| `menu` | Menú de navegación dinámico | `id`, `categoria`, `nombre`, `dir`, `icono`, `admin` |
| `users_permisos` | Permisos por usuario | `id`, `id_user`, `id_item_menu` |
| `cierres` | Cierres de caja | `id`, `semana`, `mes`, `dia`, `dolares`, `pesos`, `bolivarDolar`, `pesoDolar` |

> [!IMPORTANT]
> **Las tablas `gastos`, `gastosfijos` y `gastosEmpleados` NO existen aún en la BD.**
> Todo el código anterior que las referencia está roto. Se descarta y reescribe desde cero.

#### Multitenant + Multisucursal

- Cada negocio → `bss_id`
- Cada negocio tiene varias sucursales → `id_sucursal`
- **Todas las tablas nuevas y todas las queries deben filtrar por `bss_id` AND `id_sucursal`**
- La sucursal activa se obtiene de `$_SESSION['sucursal']`

#### Variables de sesión relevantes

| Variable | Contenido |
|---|---|
| `$_SESSION['id']` | ID numérico del usuario logueado |
| `$_SESSION['bss_id']` | ID del negocio |
| `$_SESSION['sucursal']` | ID de la sucursal activa |
| `$_SESSION['nivel']` | `1` = admin, `2` = usuario con permisos |
| `$_SESSION['permisos']` | Array de `id_item_menu` permitidos |

#### Monedas

No existe tabla `monedas`. El sistema maneja implícitamente:

| Moneda | Fuente de tasa | Campos en BD |
|---|---|---|
| **USD** | Base del sistema | `total_price`, `precio_compra`, `precio` |
| **VES** | `cambio.DolarBolivar` o BCV | `total_price_bs` |
| **COP** | `cambio.pesoDolar` | `total_price_cop` |

La clase `TasasCambio` en `configurar/_tasas_cambio.php` expone `$dolarBolivar` (VES/USD) y `$pesoDolar` (COP/USD) como variables globales luego de ser instanciada.

---

### 1.2 Backend

| Aspecto | Detalle |
|---|---|
| Lenguaje | PHP sin framework |
| Conexión BD | `mysqli` OO · variable `$conexion` |
| Endpoints AJAX | Archivos en `configurar/` · reciben POST |
| Respuestas | HTML crudo o texto con separadores (ej. `*`) |
| Prepared statements | Usar `$conexion->prepare()` + `bind_param()` en todo código nuevo |
| Validación | `intval()`, `floatval()`, `trim()` + sanitización en backend — nunca confiar en JS |

> [!WARNING]
> No usar PDO. El proyecto usa `mysqli` en todas partes.

---

### 1.3 Frontend

| Aspecto | Detalle |
|---|---|
| CSS | Bootstrap + `theme.css` con variables CSS (`--dash-bg`, `--dash-text`) |
| Dark mode | Variables CSS automáticas |
| Iconos | Ionicons, Font Awesome, Simple Line Icons |
| JS | jQuery (ya cargado en `headers.php`) |
| Alertas | SweetAlert2 (`Swal`, `Toast`) |
| DataTables | CDN 1.13.6 + botones (Excel, PDF, CSV) — ver `listaVentas.php` como referencia |
| Menú | Dinámico desde tabla `menu` — no hardcodeado |
| Modales | Custom (`.modal-container5`) — no Bootstrap Modal |
| Patrón AJAX | `$.ajax({ url, type:'POST', dataType:'html' }).done(fn)` |
| Layout | `.container.body > .main_container > #navbar + .right_col` |

---

### 1.4 Cálculo actual de ganancias (a conservar intacto)

#### Ganancia bruta

```
SUM(orden.total_price) WHERE semana='Y-W' AND status IN (1,4) AND bss_id=?
- SUM(orden_articulos.precio × quantity) para esas órdenes
```

El `total_price` es en USD. El `precio` de `orden_articulos` es el costo de compra en USD.

#### Ganancia neta actual (código roto — tablas inexistentes)

```
Ganancia neta semana = ganancia bruta - SUM(gastos.importe) WHERE semana='Y-W'
```

Una vez creadas las tablas, esto se reemplazará por:

```sql
SELECT SUM(monto_usd) FROM gastos
WHERE semana=? AND bss_id=? AND id_sucursal=? AND estado='ACTIVO'
```

---

### 1.5 Estado del código existente de gastos

Todo el código anterior de gastos es **obsoleto y se descarta completamente**:

| Archivo | Estado |
|---|---|
| `configurar/aggregarGasto.php` | **Reescribir** |
| `configurar/aggregarGastoLista.php` | **Eliminar** (lógica descartada) |
| `configurar/aggregarGastoListaEmpleado.php` | **Eliminar** (lógica descartada) |
| `configurar/deleteGastoAjax.php` | **Reescribir** (cambiar a anulación lógica) |
| `configurar/setGastoAjax.php` | **Eliminar** (lógica de toggle descartada) |
| `configurar/addEmpleadoAjax.php` | Mantener si la gestión de empleados sigue siendo útil — evaluar por separado |
| `publico/production/consulta_tablaPagos.php` | **Reescribir** |
| `publico/production/consulta_gastosCount.php` | **Reescribir** |
| `publico/production/consulta_listaFijosSemana.php` | **Eliminar** |
| `publico/production/consulta_listaFijosSemanaModal.php` | **Eliminar** |
| `publico/production/consulta_listaEmpleados.php` | Evaluar por separado |
| `publico/production/gastos.php` | **Reescribir** (conservar layout general) |

> [!NOTE]
> El ítem de menú `id=27` (`Gestión de Gastos → gastos.php`) se conserva y sigue apuntando al mismo archivo. Solo se reescribe el contenido de `gastos.php`.

---

## 2. Regla arquitectónica fundamental

> **Gastos ≠ Cuentas por Pagar ≠ Caja/Bancos.**
>
> El módulo registra y clasifica en qué se consume dinero, por negocio y por sucursal.
> No crea movimientos de caja, no genera obligaciones con terceros, no toca `cierres`.

---

## 3. Diseño funcional

### 3.1 Tipos de gasto

| Tipo | Descripción | Ejemplos |
|---|---|---|
| **FIJO** | Previsible, recurrente | Alquiler, nómina, internet, seguridad, software |
| **VARIABLE** | Importe o aparición no predecible | Reparaciones, mantenimiento extraordinario, transporte |

### 3.2 Frecuencias

`UNICO` · `DIARIO` · `SEMANAL` · `QUINCENAL` · `MENSUAL` · `BIMESTRAL` · `TRIMESTRAL` · `SEMESTRAL` · `ANUAL`

> Tipo y frecuencia son independientes. Una electricidad puede ser VARIABLE + MENSUAL. Una reparación puede ser VARIABLE + UNICO.

### 3.3 Multimoneda

El usuario selecciona la moneda al registrar el gasto. El sistema:

1. Guarda el `monto` original en la moneda elegida
2. Guarda la `tasa_cambio` vigente en ese momento (tomada de `$dolarBolivar` o `$pesoDolar`)
3. Calcula y guarda `monto_usd = monto / tasa_cambio` (o `monto` directo si ya es USD)

Ejemplo:

```
Gasto en VES: Bs. 3,650
Tasa vigente: 36.50 Bs/USD
monto_usd = 3650 / 36.50 = $100.00
```

```
Gasto en COP: $360,000 COP
Tasa vigente: 4,000 COP/USD
monto_usd = 360000 / 4000 = $90.00
```

El `monto_usd` es siempre el que afecta el cálculo de ganancias.

---

## 4. Modelo de base de datos

> [!IMPORTANT]
> Verificar antes de ejecutar: `SHOW TABLES LIKE 'gasto%';`
> Todas las tablas incluyen `bss_id` e `id_sucursal`.

### 4.1 `gastos_categorias` — Categorías de gastos

Se crea separada de `categorias` (que es para productos).

```sql
CREATE TABLE `gastos_categorias` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100) NOT NULL,
  `padre_id`    INT(11)      DEFAULT NULL COMMENT 'NULL = raíz; ID = subcategoría',
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `bss_id`      INT(11)      NOT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bss`   (`bss_id`),
  INDEX `idx_padre` (`padre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Categorías iniciales** (se insertan por `bss_id` al activar el módulo):

```
Personal · Alquiler · Servicios públicos · Mantenimiento · Reparaciones
Transporte · Publicidad · Papelería · Limpieza · Tecnología
Comisiones · Impuestos · Gastos administrativos · Otros
```

---

### 4.2 `gastos_recurrentes` — Reglas de recurrencia

Reemplaza y elimina la antigua tabla `gastosfijos`.

```sql
CREATE TABLE `gastos_recurrentes` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `concepto`        VARCHAR(255) NOT NULL,
  `categoria_id`    INT(11)      DEFAULT NULL,
  `tipo`            ENUM('FIJO','VARIABLE') NOT NULL DEFAULT 'FIJO',
  `frecuencia`      ENUM('DIARIO','SEMANAL','QUINCENAL','MENSUAL',
                         'BIMESTRAL','TRIMESTRAL','SEMESTRAL','ANUAL') NOT NULL,
  `monto_estimado`  DECIMAL(15,2) DEFAULT NULL COMMENT 'Referencial, no obligatorio',
  `moneda`          ENUM('USD','VES','COP') NOT NULL DEFAULT 'USD',
  `dia_ejecucion`   VARCHAR(50)  DEFAULT NULL COMMENT 'Ej: "15,30" para quincenal; "1" para mensual',
  `fecha_inicio`    DATE         NOT NULL,
  `fecha_fin`       DATE         DEFAULT NULL COMMENT 'NULL = sin vencimiento',
  `activo`          TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `observacion`     TEXT         DEFAULT NULL,
  `usuario_id`      INT(11)      NOT NULL,
  `bss_id`          INT(11)      NOT NULL,
  `id_sucursal`     INT(11)      NOT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activo_bss` (`activo`, `bss_id`, `id_sucursal`),
  FOREIGN KEY (`categoria_id`) REFERENCES `gastos_categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.3 `gastos` — Gastos registrados

```sql
CREATE TABLE `gastos` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `codigo`            VARCHAR(20)   NOT NULL COMMENT 'G-000001, secuencial por bss_id',
  `fecha`             DATE          NOT NULL,
  `semana`            VARCHAR(10)   NOT NULL COMMENT 'Y-W — para cálculos de ganancias',
  `mes`               VARCHAR(7)    NOT NULL COMMENT 'Y-m — para cálculos de ganancias',
  `concepto`          VARCHAR(255)  NOT NULL,
  `categoria_id`      INT(11)       DEFAULT NULL,
  `tipo`              ENUM('FIJO','VARIABLE') NOT NULL,
  `frecuencia`        ENUM('UNICO','DIARIO','SEMANAL','QUINCENAL','MENSUAL',
                           'BIMESTRAL','TRIMESTRAL','SEMESTRAL','ANUAL') NOT NULL,
  `moneda`            ENUM('USD','VES','COP') NOT NULL DEFAULT 'USD',
  `monto`             DECIMAL(15,2) NOT NULL COMMENT 'Monto en la moneda original registrada',
  `tasa_cambio`       DECIMAL(15,4) NOT NULL DEFAULT 1.0000 COMMENT 'Tasa vigente al registrar',
  `monto_usd`         DECIMAL(15,2) NOT NULL COMMENT 'Equivalente en USD — afecta cálculo de ganancias',
  `estado`            ENUM('ACTIVO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `observacion`       TEXT          DEFAULT NULL,
  `recurrente_id`     INT(11)       DEFAULT NULL COMMENT 'FK a gastos_recurrentes si fue generado desde allí',
  `usuario_id`        INT(11)       NOT NULL COMMENT '$_SESSION[id]',
  `usuario_anulacion` INT(11)       DEFAULT NULL,
  `fecha_anulacion`   DATETIME      DEFAULT NULL,
  `motivo_anulacion`  TEXT          DEFAULT NULL,
  `id_sucursal`       INT(11)       NOT NULL COMMENT '$_SESSION[sucursal]',
  `bss_id`            INT(11)       NOT NULL COMMENT '$_SESSION[bss_id]',
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_semana`  (`semana`, `bss_id`, `id_sucursal`),
  INDEX `idx_mes`     (`mes`, `bss_id`, `id_sucursal`),
  INDEX `idx_fecha`   (`fecha`, `bss_id`, `id_sucursal`),
  INDEX `idx_estado`  (`estado`),
  FOREIGN KEY (`categoria_id`)  REFERENCES `gastos_categorias`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recurrente_id`) REFERENCES `gastos_recurrentes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.4 Generación de código autoincremental

El `codigo` se genera en PHP antes de insertar:

```php
// Obtener el último número para ese bss_id
$stmt = $conexion->prepare(
    "SELECT codigo FROM gastos WHERE bss_id=? ORDER BY id DESC LIMIT 1"
);
$stmt->bind_param('i', $bss_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$numero = $row ? (int) substr($row['codigo'], 2) + 1 : 1;
$codigo = 'G-' . str_pad($numero, 6, '0', STR_PAD_LEFT); // G-000001
```

---

### 4.5 Cálculo de `monto_usd` en backend

```php
// $tasas viene de TasasCambio (ya inicializado en requires.php vía _tasas_cambio.php)
$moneda       = $_POST['moneda'];    // USD, VES, COP
$monto        = floatval($_POST['monto']);
$dolarBolivar = floatval($tasas['data']['DolarBolivar']); // VES por 1 USD
$pesoDolar    = floatval($tasas['data']['pesoDolar']);     // COP por 1 USD

switch ($moneda) {
    case 'USD':
        $tasa_cambio = 1.0;
        $monto_usd   = $monto;
        break;
    case 'VES':
        $tasa_cambio = $dolarBolivar;
        $monto_usd   = ($dolarBolivar > 0) ? round($monto / $dolarBolivar, 2) : 0;
        break;
    case 'COP':
        $tasa_cambio = $pesoDolar;
        $monto_usd   = ($pesoDolar > 0) ? round($monto / $pesoDolar, 2) : 0;
        break;
}
```

---

## 5. Fases de implementación

### FASE 1 — Crear las tablas SQL

**Archivo a crear:** `configurar/migrations/001_gastos.sql`

Ejecutar en orden:
1. `gastos_categorias`
2. `gastos_recurrentes`
3. `gastos`

Verificar:
```sql
SHOW TABLES LIKE 'gasto%';
DESCRIBE gastos;
DESCRIBE gastos_recurrentes;
DESCRIBE gastos_categorias;
```

Insertar categorías iniciales para todos los negocios existentes:
```sql
INSERT INTO gastos_categorias (nombre, bss_id)
SELECT c.nombre, n.id
FROM (
  SELECT 'Personal'               AS nombre UNION ALL
  SELECT 'Alquiler'               UNION ALL
  SELECT 'Servicios públicos'     UNION ALL
  SELECT 'Mantenimiento'          UNION ALL
  SELECT 'Reparaciones'           UNION ALL
  SELECT 'Transporte'             UNION ALL
  SELECT 'Publicidad'             UNION ALL
  SELECT 'Papelería'              UNION ALL
  SELECT 'Limpieza'               UNION ALL
  SELECT 'Tecnología'             UNION ALL
  SELECT 'Comisiones'             UNION ALL
  SELECT 'Impuestos'              UNION ALL
  SELECT 'Gastos administrativos' UNION ALL
  SELECT 'Otros'
) c
CROSS JOIN negocio n;
```

---

### FASE 2 — Reescribir los endpoints de gastos

#### `configurar/aggregarGasto.php` (reescribir completo)

Recibe: `concepto`, `categoria_id`, `tipo`, `frecuencia`, `moneda`, `monto`, `fecha`, `observacion`

Proceso:
1. Validar sesión (`bss_id`, `id`, `sucursal`)
2. Sanitizar y validar todos los campos
3. Calcular `tasa_cambio` y `monto_usd` según moneda
4. Calcular `semana` (formato `Y-W`) y `mes` (formato `Y-m`) desde `fecha`
5. Generar `codigo`
6. `INSERT INTO gastos` con prepared statement
7. Devolver JSON `{ "status": "ok", "codigo": "G-000001" }`

```php
<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_tasas_cambio.php'); // expone $dolarBolivar, $pesoDolar

header('Content-Type: application/json');

$bss_id      = (int) $_SESSION['bss_id'];
$usuario_id  = (int) $_SESSION['id'];
$id_sucursal = (int) $_SESSION['sucursal'];

// Validar y sanitizar
$concepto    = trim(strip_tags($_POST['concepto'] ?? ''));
$categoria_id = intval($_POST['categoria_id'] ?? 0) ?: null;
$tipo        = in_array($_POST['tipo'] ?? '', ['FIJO','VARIABLE']) ? $_POST['tipo'] : null;
$frecuencia  = in_array($_POST['frecuencia'] ?? '', ['UNICO','DIARIO','SEMANAL','QUINCENAL',
               'MENSUAL','BIMESTRAL','TRIMESTRAL','SEMESTRAL','ANUAL']) ? $_POST['frecuencia'] : null;
$moneda      = in_array($_POST['moneda'] ?? '', ['USD','VES','COP']) ? $_POST['moneda'] : 'USD';
$monto       = floatval($_POST['monto'] ?? 0);
$fecha       = $_POST['fecha'] ?? '';
$observacion = trim(strip_tags($_POST['observacion'] ?? ''));

// Validaciones
if (!$concepto || !$tipo || !$frecuencia || $monto <= 0 || !$fecha) {
    echo json_encode(['status' => 'error', 'msg' => 'Campos obligatorios incompletos']);
    exit;
}

$dt = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$dt) {
    echo json_encode(['status' => 'error', 'msg' => 'Fecha inválida']);
    exit;
}

// Calcular tasa y monto_usd
switch ($moneda) {
    case 'VES':
        $tasa_cambio = floatval($dolarBolivar);
        $monto_usd   = ($tasa_cambio > 0) ? round($monto / $tasa_cambio, 2) : 0;
        break;
    case 'COP':
        $tasa_cambio = floatval($pesoDolar);
        $monto_usd   = ($tasa_cambio > 0) ? round($monto / $tasa_cambio, 2) : 0;
        break;
    default:
        $tasa_cambio = 1.0;
        $monto_usd   = $monto;
}

// Calcular semana y mes
$semana = $dt->format('Y-W');
$mes    = $dt->format('Y-m');

// Generar código
$stmt = $conexion->prepare("SELECT codigo FROM gastos WHERE bss_id=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('i', $bss_id);
$stmt->execute();
$row    = $stmt->get_result()->fetch_assoc();
$numero = $row ? (int) substr($row['codigo'], 2) + 1 : 1;
$codigo = 'G-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

// Insertar
$stmt = $conexion->prepare(
    "INSERT INTO gastos
     (codigo, fecha, semana, mes, concepto, categoria_id, tipo, frecuencia,
      moneda, monto, tasa_cambio, monto_usd, observacion, usuario_id, id_sucursal, bss_id)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
);
$stmt->bind_param(
    'sssssissssddsiiii',  // ajustar tipos exactos
    $codigo, $fecha, $semana, $mes, $concepto, $categoria_id, $tipo, $frecuencia,
    $moneda, $monto, $tasa_cambio, $monto_usd, $observacion, $usuario_id, $id_sucursal, $bss_id
);

if ($stmt->execute()) {
    echo json_encode(['status' => 'ok', 'codigo' => $codigo]);
} else {
    echo json_encode(['status' => 'error', 'msg' => $stmt->error]);
}
```

#### `configurar/anular_gasto.php` (nuevo — reemplaza deleteGastoAjax.php)

```php
// Recibe: id, motivo
// Verifica que el gasto pertenezca al bss_id e id_sucursal de la sesión
// UPDATE gastos SET estado='ANULADO', usuario_anulacion=?, fecha_anulacion=NOW(), motivo_anulacion=?
// WHERE id=? AND bss_id=? AND id_sucursal=?
```

> `deleteGastoAjax.php` se deja como archivo vacío o con redirección para no romper bookmarks, pero ya no se usa en el frontend.

---

### FASE 3 — Reescribir `consulta_tablaPagos.php`

El nuevo endpoint devuelve la tabla de gastos con:
- Código, Fecha, Concepto, Categoría, Tipo, Frecuencia, Monto (con moneda), Monto USD, Estado, Usuario

Parámetros de filtro (POST): `fecha_desde`, `fecha_hasta`, `categoria_id`, `tipo`, `frecuencia`, `estado`, `semana` (para preset)

Siempre filtra por `bss_id` e `id_sucursal`.

```sql
SELECT g.*, gc.nombre AS categoria_nombre, u.nombre AS usuario_nombre
FROM gastos g
LEFT JOIN gastos_categorias gc ON g.categoria_id = gc.id
LEFT JOIN usuarios u ON g.usuario_id = u.id
WHERE g.bss_id = ? AND g.id_sucursal = ?
  AND g.fecha BETWEEN ? AND ?
  AND g.estado = IF(? != '', ?, g.estado)
  -- ... filtros adicionales
ORDER BY g.fecha DESC
```

---

### FASE 4 — Reescribir `consulta_gastosCount.php`

```php
// Totales por semana y mes (solo gastos ACTIVOS, en USD, por bss_id + id_sucursal)

$stmt = $conexion->prepare(
    "SELECT COALESCE(SUM(monto_usd), 0) AS total FROM gastos
     WHERE semana=? AND bss_id=? AND id_sucursal=? AND estado='ACTIVO'"
);

$stmt = $conexion->prepare(
    "SELECT COALESCE(SUM(monto_usd), 0) AS total FROM gastos
     WHERE mes=? AND bss_id=? AND id_sucursal=? AND estado='ACTIVO'"
);

// Devolver en el mismo formato separado por * para no romper el JS existente:
echo $gastosSemana . '*' . $gastosMes . '*' . $gananciaNetaSemana . '*' . $gananciaNetaMes;
```

---

### FASE 5 — Reescribir `gastos.php`

Se conserva el layout general del archivo. Se reescribe el contenido funcional.

#### Formulario de nuevo gasto (reemplaza el actual)

```
Fecha            [input date]          ← visible siempre
Concepto         [input text]
Categoría        [select gastos_categorias WHERE bss_id=?]
Tipo             [select: FIJO / VARIABLE]
Frecuencia       [select: 9 opciones]
Moneda           [select: USD / VES / COP]
Monto            [input number]
Monto USD        [readonly, calculado en JS al cambiar monto/moneda]
Observación      [textarea]
```

El campo "Monto USD" se calcula en tiempo real en JS usando la tasa actual (inyectada desde PHP en la vista con `$dolarBolivar` y `$pesoDolar`).

#### Tabla de gastos (reemplaza la actual)

Columnas: `#` | Código | Fecha | Concepto | Categoría | Tipo | Frecuencia | Moneda | Monto | Monto USD | Estado | Acciones

Acciones:
- 👁 Ver detalle (modal)
- 🚫 Anular (pide motivo con `Swal.fire` con input, llama `anular_gasto.php`)

#### Tarjetas de resumen (reemplaza las actuales)

```
Gastos hoy         Gastos esta semana      Gastos este mes
[monto_usd]        [monto_usd]             [monto_usd]
                                    
Beneficio neto semana               Beneficio neto mes
[ganancia bruta - gastos]           [ganancia bruta - gastos]
```

#### Panel lateral de recurrentes

El panel de "Gastos recurrentes" con toggles se elimina. En su lugar, un enlace a `gastos_recurrentes.php`.

#### Filtros

```
[Hoy] [Esta semana] [Este mes] [Este año] [Personalizado: fecha desde — hasta]
[Categoría ▾] [Tipo ▾] [Frecuencia ▾] [Estado ▾] [Moneda ▾]
```

---

### FASE 6 — CRUD de categorías

**Archivo:** `publico/production/gastos_categorias.php`  
**Endpoint:** `configurar/gastos_categorias_crud.php`

Funcionalidad:
- Listar categorías y subcategorías activas del `bss_id`
- Crear (nombre + padre_id opcional)
- Editar nombre
- Desactivar (`activo=0`) — nunca borrar físicamente

Respetar diseño de `users.php` o `productos.php` (panel + tabla + modal).

**Agregar al menú:**
```sql
INSERT INTO menu (categoria, nombre, dir, icono)
VALUES ('Gastos', 'Categorías', 'gastos_categorias.php', 'pricetag-outline');
```

---

### FASE 7 — CRUD de gastos recurrentes

**Archivo:** `publico/production/gastos_recurrentes.php`  
**Endpoint:** `configurar/gastos_recurrentes_crud.php`

Campos del formulario:
```
Concepto, Categoría, Tipo, Frecuencia
Monto estimado, Moneda
Día(s) de ejecución (ej: 15,30)
Fecha inicio, Fecha fin (opcional)
Observación
```

Listado: tabla con columnas Concepto | Categoría | Tipo | Frecuencia | Monto est. | Estado | Acciones

Acciones: Editar | Activar/Desactivar

**Lógica de aplicación manual** (botón "Aplicar recurrentes" en `gastos.php`):
```text
Para cada regla en gastos_recurrentes WHERE activo=1 AND bss_id=? AND id_sucursal=?:
  1. Verificar que NO exista ya un gasto con recurrente_id=? AND semana=?
  2. Si no existe → INSERT en gastos con recurrente_id=id_recurrente y monto_estimado
  3. Si ya existe → no hacer nada (no duplicar)
```

No se generan gastos futuros automáticamente. El gasto se crea cuando el usuario lo aplica.

**Agregar al menú:**
```sql
INSERT INTO menu (categoria, nombre, dir, icono)
VALUES ('Gastos', 'Recurrentes', 'gastos_recurrentes.php', 'repeat-outline');
```

---

### FASE 8 — Menú y permisos

#### Estado actual del menú

```
id=27  Gestión de Gastos → gastos.php  (se conserva tal cual, apunta al módulo reescrito)
```

#### Nuevas entradas

```sql
-- Convertir "Gastos" en categoría agrupadora (si el menú lo permite)
-- O bien agregar como items sueltos bajo la categoría "Gastos":

INSERT INTO menu (categoria, nombre, dir, icono) VALUES
  ('Gastos', 'Categorías de gastos', 'gastos_categorias.php',   'pricetag-outline'),
  ('Gastos', 'Gastos recurrentes',   'gastos_recurrentes.php',  'repeat-outline');
```

> [!NOTE]
> El ítem `id=27` puede actualizarse para que `categoria='Gastos'` si se desea agruparlo en el menú desplegable. Verificar la estructura del `generarMenuAdaptado()` antes de modificar.

#### Permisos

Los permisos del sistema se gestionan por `id_item_menu` en `users_permisos`. Al crear los nuevos ítems de menú, su `id` se registra automáticamente como permiso disponible en `permisos.php` (admin nivel 1 los asigna).

No se requiere ningún cambio en el sistema de permisos.

---

### FASE 9 — Integración con ganancias (Fase quirúrgica)

La única modificación al cálculo de ganancias es en `consulta_gastosCount.php`:

#### Antes (roto)
```php
$query = "SELECT * FROM gastos WHERE semana='$semana'";
// ... acumulación manual
```

#### Después
```php
$stmt = $conexion->prepare(
    "SELECT COALESCE(SUM(monto_usd), 0) AS total
     FROM gastos
     WHERE semana=? AND bss_id=? AND id_sucursal=? AND estado='ACTIVO'"
);
$stmt->bind_param('sii', $semana, $bss_id, $id_sucursal);
$stmt->execute();
$gastosSemana = $stmt->get_result()->fetch_assoc()['total'];

// Igual para mes
```

**No se toca** la lógica de `orden` + `orden_articulos` (ganancia bruta).

El formato de respuesta `echo $a . '*' . $b . '*' . $c . '*' . $d;` se conserva para no romper el JS de `gastos.php`.

---

### FASE 10 — Gastos por sucursal en el dashboard principal

En `gastos.php`, el contexto de sucursal viene de `$_SESSION['sucursal']`.

Si el usuario es nivel 1 (admin), mostrar un selector de sucursal que permita ver gastos de cualquier sucursal o de todas las sucursales del negocio.

Si el usuario es nivel 2, solo ver los gastos de su sucursal asignada.

---

### FASE 11 — Reportes

**Archivo:** `publico/production/gastos_reportes.php`

Reutilizar la implementación de DataTables + botones de exportación de `listaVentas.php`.

Reportes disponibles:
- Gastos por período (fecha desde/hasta)
- Gastos por categoría
- Gastos FIJO vs VARIABLE (con totales)
- Gastos recurrentes pendientes
- Gastos anulados (con motivo y usuario que anuló)
- Comparativo mensual (mes actual vs mes anterior)
- Resumen por sucursal (nivel 1 únicamente)

**Columnas clave en exportación:** Código, Fecha, Concepto, Categoría, Tipo, Frecuencia, Moneda, Monto original, Tasa, Monto USD, Estado, Usuario, Sucursal

**Agregar al menú:**
```sql
INSERT INTO menu (categoria, nombre, dir, icono)
VALUES ('Gastos', 'Reportes', 'gastos_reportes.php', 'bar-chart-outline');
```

---

## 6. Lo que NO se debe hacer

| Prohibición | Razón |
|---|---|
| Borrar físicamente gastos registrados | Rompe auditoría y ganancias históricas |
| Crear movimientos en `cierres` por cada gasto | `cierres` es para caja diaria |
| Restar gastos futuros de la ganancia real | Contamina métricas actuales |
| Distribuir gasto quincenal `$800/15` como real | Incorrecto conceptualmente |
| Usar `gastos_categorias` para productos o viceversa | Catálogos separados |
| Queries sin `bss_id` AND `id_sucursal` | Rompe multitenant y multisucursal |
| Usar PDO | El proyecto usa `mysqli` — no mezclar |
| Modificar `orden` u `orden_articulos` | Tablas de ventas — no tocar |
| Generar gastos futuros automáticamente | La aplicación es siempre manual o explícita |
| Eliminar el ítem `id=27` del menú | Se conserva apuntando a `gastos.php` |

---

## 7. Orden de ejecución

```
PASO 1 — SQL: crear gastos_categorias, gastos_recurrentes, gastos
          + INSERT categorías iniciales
          + DESCRIBE para verificar

PASO 2 — Reescribir: aggregarGasto.php
          + Nuevo: anular_gasto.php

PASO 3 — Reescribir: consulta_tablaPagos.php
          + Reescribir: consulta_gastosCount.php

PASO 4 — Reescribir: gastos.php
          (formulario nuevo, tabla nueva, tarjetas nuevas, JS adaptado)

PASO 5 — Crear: gastos_categorias.php + configurar/gastos_categorias_crud.php
          + INSERT menú

PASO 6 — Crear: gastos_recurrentes.php + configurar/gastos_recurrentes_crud.php
          + INSERT menú

PASO 7 — Crear: gastos_reportes.php
          + INSERT menú

PASO 8 — Pruebas funcionales (ver sección 8)

PASO 9 — Revisión de seguridad (ver sección 9)

PASO 10 — Eliminar archivos obsoletos confirmados:
           aggregarGastoLista.php, aggregarGastoListaEmpleado.php,
           setGastoAjax.php, consulta_listaFijosSemana.php,
           consulta_listaFijosSemanaModal.php
```

---

## 8. Pruebas funcionales

### 8.1 Gasto único en VES

```
Fecha: 2026-08-10
Concepto: Reparación AC
Tipo: VARIABLE · Frecuencia: UNICO
Moneda: VES · Monto: 3,650
Tasa: 36.50 → monto_usd debe ser $100.00
```

Verificar:
- `gastos.semana = '2026-32'`
- `gastos.mes = '2026-08'`
- `consulta_gastosCount` devuelve $100 en gastos de esa semana
- NO afecta semana 33 ni septiembre

### 8.2 Gasto recurrente quincenal en USD

```
Concepto: Nómina · Tipo: FIJO · Frecuencia: QUINCENAL
Días: 15, 30 · Monto estimado: $800
```

Al aplicar manualmente para la semana de fecha 15/08:
- Se crea gasto con `fecha=2026-08-15`, `monto_usd=800`, `recurrente_id=X`
- Al aplicar para semana de fecha 30/08:
  - Se crea gasto con `fecha=2026-08-30`, `monto_usd=800`
- Agosto total nómina = `$1,600` en `consulta_gastosCount`
- Aplicar segunda vez → **no duplica** (verifica `recurrente_id + semana`)

### 8.3 Gasto variable mensual en distintos períodos

```
Agosto: Electricidad $150 (COP 600,000 / tasa 4000)
Septiembre: Electricidad $180 (COP 720,000 / tasa 4000)
```

Cada mes conserva su importe real. No se mezclan.

### 8.4 Anulación con motivo

```
Anular gasto G-000005 · Motivo: "Duplicado por error"
```

Verificar:
- `estado = 'ANULADO'`
- `motivo_anulacion = 'Duplicado por error'`
- `usuario_anulacion = $_SESSION['id']`
- NO aparece en `SUM(monto_usd)` de ganancias
- SÍ aparece en reporte de anulados

### 8.5 Integración con ganancias

```
Ventas semana 35:     $10,000
Costo mercancía:       $6,000
→ Ganancia bruta:      $4,000

Gastos semana 35:      $1,000 (estado=ACTIVO, monto_usd)
→ gananciaNetaSemana:  $3,000
```

Verificar que un gasto `estado=ANULADO` de esa semana NO sume.

### 8.6 Multisucursal

Registrar gasto en sucursal A. Cambiar sesión a sucursal B. El gasto NO debe aparecer en los totales ni en la tabla de sucursal B.

---

## 9. Seguridad

Checklist para cada endpoint en `configurar/`:

- [ ] Verificar `$_SESSION['bss_id']` → redirigir si vacío
- [ ] Verificar `$_SESSION['id']` → redirigir si vacío
- [ ] Verificar `$_SESSION['sucursal']` → redirigir si vacío
- [ ] Todo campo POST: `intval()` para IDs, `floatval()` para montos, `trim(strip_tags())` para texto
- [ ] Fechas: validar con `DateTime::createFromFormat('Y-m-d', $fecha)`
- [ ] Moneda: validar con `in_array($moneda, ['USD','VES','COP'])`
- [ ] Tipo: validar con `in_array($tipo, ['FIJO','VARIABLE'])`
- [ ] Todas las queries: `$conexion->prepare()` + `bind_param()` — cero concatenación directa
- [ ] En anulación: verificar `bss_id` e `id_sucursal` del gasto antes de anular (no anular gastos de otro negocio)
- [ ] Respuestas: nunca exponer mensajes de error de MySQL al cliente

---

## 10. Resumen de archivos

### Crear desde cero

| Archivo | Descripción |
|---|---|
| `configurar/migrations/001_gastos.sql` | Script SQL completo |
| `configurar/anular_gasto.php` | Anulación lógica con motivo |
| `configurar/gastos_categorias_crud.php` | CRUD categorías |
| `configurar/gastos_recurrentes_crud.php` | CRUD recurrentes |
| `publico/production/gastos_categorias.php` | Vista categorías |
| `publico/production/gastos_recurrentes.php` | Vista recurrentes |
| `publico/production/gastos_reportes.php` | Vista reportes |

### Reescribir completamente

| Archivo | Descripción |
|---|---|
| `configurar/aggregarGasto.php` | Nuevo endpoint de registro |
| `publico/production/consulta_tablaPagos.php` | Nueva tabla de gastos |
| `publico/production/consulta_gastosCount.php` | Nuevos totales con monto_usd |
| `publico/production/gastos.php` | Vista principal reescrita |

### Eliminar (después de pruebas)

```
configurar/aggregarGastoLista.php
configurar/aggregarGastoListaEmpleado.php
configurar/setGastoAjax.php
publico/production/consulta_listaFijosSemana.php
publico/production/consulta_listaFijosSemanaModal.php
```

### No tocar

```
orden, orden_articulos, cierres (tablas de BD)
configurar/_tasas_cambio.php (se reutiliza)
configurar/configuracion.php
configurar/session.php
includes/requires.php, headers.php, menu.php, header.php
```

---

*Plan v2 — iSeller POS · Módulo de Gastos · 28/08/2026*
