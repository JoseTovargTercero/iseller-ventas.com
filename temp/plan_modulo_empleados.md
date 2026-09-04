# Plan de Implementación — Módulo de Empleados (iSeller POS)

> Sistema: PHP + mysqli · Multitenant (`bss_id`) · Multisucursal (`id_sucursal`)
> Dependencia: Módulo de Gastos (FASE 1-10 completada)

---

## 1. Objetivo

Crear un módulo de gestión de empleados donde cada empleado se comporta como un gasto recurrente. Al registrar un empleado, se crea automáticamente una regla en `gastos_recurrentes`. El auto-apply mensual existente genera el gasto real sin intervención manual.

---

## 2. Diseño de datos

### 2.1 Tabla `empleados`

```sql
CREATE TABLE IF NOT EXISTS `empleados` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`            VARCHAR(150) NOT NULL,
  `rol`               VARCHAR(100) NOT NULL,
  `tipo_pago`         ENUM('SEMANAL','MENSUAL') NOT NULL,
  `monto_pago`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `moneda`            ENUM('USD','VES','COP') NOT NULL DEFAULT 'USD',
  `dia_ejecucion`     VARCHAR(50)  DEFAULT NULL COMMENT 'SEMANAL: 1-7 (lun-dom); MENSUAL: 1-31 (dia mes)',
  `recurrente_id`     INT(11)      DEFAULT NULL COMMENT 'FK a gastos_recurrentes',
  `activo`            TINYINT(1)   NOT NULL DEFAULT 1,
  `observacion`       TEXT         DEFAULT NULL,
  `usuario_id`        INT(11)      NOT NULL,
  `id_sucursal`       INT(11)      NOT NULL,
  `bss_id`            INT(11)      NOT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bss_suc` (`bss_id`, `id_sucursal`),
  FOREIGN KEY (`recurrente_id`) REFERENCES `gastos_recurrentes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2 Categoría "Personal"

Se verifica si existe la categoría `Personal` en `gastos_categorias` para cada `bss_id`. Si no existe, se crea automáticamente al registrar el primer empleado de ese negocio.

### 2.3 Relación con gastos_recurrentes

Al crear un empleado:

```
empleados.nombre        →  gastos_recurrentes.concepto = "Pago: {nombre}"
empleados.tipo_pago     →  gastos_recurrentes.frecuencia (SEMANAL | MENSUAL)
empleados.monto_pago    →  gastos_recurrentes.monto_estimado
empleados.moneda        →  gastos_recurrentes.moneda
empleados.dia_ejecucion →  gastos_recurrentes.dia_ejecucion
                          gastos_recurrentes.tipo = 'FIJO'
                          gastos_recurrentes.categoria_id = id de "Personal"
                          gastos_recurrentes.fecha_inicio = hoy
                          gastos_recurrentes.activo = 1
```

Al desactivar un empleado → se desactiva la regla recurrente asociada.
Al activar un empleado → se activa la regla recurrente asociada.
Al eliminar un empleado → `ON DELETE SET NULL` en `recurrente_id` (el gasto historic sigue existiendo).

---

## 3. Flujo de vida del empleado

```
1. Admin crea empleado
   → INSERT empleados
   → INSERT gastos_recurrentes (FK linked)
   → Regla activa lista para auto-apply

2. Admin carga gastos.php
   → aplicar_recurrentes_mes.php ejecuta
   → Detecta regla activa SIN gasto para este mes
   → INSERT gastos (recurrente_id = id de la regla)
   → Gasto aparece en tabla de gastos

3. Admin desactiva empleado
   → UPDATE empleados SET activo=0
   → UPDATE gastos_recurrentes SET activo=0
   → Auto-apply no crea más gastos para esta regla
   → Gastos históricos se conservan
```

---

## 4. Archivos a crear

| # | Archivo | Tipo | Propósito |
|---|---------|------|-----------|
| 1 | `configurar/migrations/002_empleados.sql` | Migración | Tabla empleados + categoría + menú |
| 2 | `configurar/empleados_crud.php` | Endpoint | CRUD: listar, crear, editar, activar, desactivar |
| 3 | `publico/production/empleados.php` | Vista | Página principal con tabla, modal, filtros |
| 4 | `publico/production/consulta_tablaEmpleados.php` | Endpoint | Renderer de tabla HTML server-side |

### Archivos NO se modifican

| Archivo | Razón |
|---------|-------|
| `gastos.php` | Auto-apply ya cubre las nuevas reglas |
| `aplicar_recurrentes_mes.php` | Ya procesa todas las reglas activas |
| `consulta_proyeccionRecurrentes.php` | Ya proyecta todas las reglas activas |
| `gastos_recurrentes.php` | Módulo independiente, no se toca |
| `gastos_recurrentes_crud.php` | CRUD independiente, no se toca |

---

## 5. FASE 1 — Migración SQL

**Archivo:** `configurar/migrations/002_empleados.sql`

### 5.1 Crear tabla empleados

```sql
CREATE TABLE IF NOT EXISTS `empleados` ( ... );
```

### 5.2 Verificar categoría "Personal"

```sql
-- Solo crear si no existe para el bss_id
INSERT IGNORE INTO `gastos_categorias` (`nombre`, `bss_id`)
SELECT 'Personal', n.id FROM `negocio` n
WHERE NOT EXISTS (
  SELECT 1 FROM gastos_categorias gc WHERE gc.nombre='Personal' AND gc.bss_id=n.id
);
```

### 5.3 Agregar menú

```sql
INSERT INTO menu (categoria, nombre, dir, icono)
VALUES ('Gastos', 'Empleados', 'empleados.php', 'people-outline');
```

### 5.4 Verificación

```sql
SHOW TABLES LIKE 'empleado%';
DESCRIBE empleados;
SELECT m.id, m.categoria, m.nombre, m.dir FROM menu m WHERE m.categoria='Gastos';
SELECT gc.id, gc.nombre, gc.bss_id FROM gastos_categorias gc WHERE gc.nombre='Personal';
```

---

## 6. FASE 2 — CRUD Endpoint

**Archivo:** `configurar/empleados_crud.php`

### 6.1 Acciones

| Acción | Método | Parámetros | Respuesta |
|--------|--------|------------|-----------|
| `listar` | POST | `id_sucursal` (opcional) | `{status:'ok', data:[{id,nombre,rol,...}]}` |
| `crear` | POST | `nombre, rol, tipo_pago, monto_pago, moneda, dia_ejecucion, observacion` | `{status:'ok', id:N, msg:'...'}` |
| `editar` | POST | `id, nombre, rol, tipo_pago, monto_pago, moneda, dia_ejecucion, observacion` | `{status:'ok', msg:'...'}` |
| `desactivar` | POST | `id` | `{status:'ok', msg:'...'}` |
| `activar` | POST | `id` | `{status:'ok', msg:'...'}` |

### 6.2 Lógica de crear

```php
case 'crear':
    // 1. Validar campos obligatorios
    $nombre = trim(strip_tags($_POST['nombre'] ?? ''));
    $rol = trim(strip_tags($_POST['rol'] ?? ''));
    $tipo_pago = in_array($_POST['tipo_pago'] ?? '', ['SEMANAL','MENSUAL']) ? $_POST['tipo_pago'] : null;
    $monto = floatval($_POST['monto_pago'] ?? 0);
    $moneda = in_array($_POST['moneda'] ?? '', ['USD','VES','COP']) ? $_POST['moneda'] : 'USD';
    $dia_ej = trim($_POST['dia_ejecucion'] ?? '');
    $obs = trim(strip_tags($_POST['observacion'] ?? ''));

    if (!$nombre || !$rol || !$tipo_pago || $monto <= 0 || !$dia_ej) {
        // error: campos incompletos
    }

    // 2. Verificar/crear categoría "Personal"
    $cat_id = obtenerOCrearCategoriaPersonal($conexion, $bss_id);

    // 3. INSERT en gastos_recurrentes
    $stmt = $conexion->prepare(
        "INSERT INTO gastos_recurrentes
         (concepto, categoria_id, tipo, frecuencia, monto_estimado, moneda,
          dia_ejecucion, fecha_inicio, activo, usuario_id, id_sucursal, bss_id)
         VALUES (?, ?, 'FIJO', ?, ?, ?, ?, CURDATE(), 1, ?, ?, ?)"
    );
    // bind: concepto="Pago: {nombre}", cat_id, frecuencia=tipo_pago, monto, moneda, dia_ej, usuario_id, sucursal, bss_id
    $stmt->execute();
    $recurrente_id = $stmt->insert_id;
    $stmt->close();

    // 4. INSERT en empleados
    $stmt2 = $conexion->prepare(
        "INSERT INTO empleados
         (nombre, rol, tipo_pago, monto_pago, moneda, dia_ejecucion,
          recurrente_id, activo, observacion, usuario_id, id_sucursal, bss_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)"
    );
    // bind
    $stmt2->execute();
    $empleado_id = $stmt2->insert_id;
    $stmt2->close();

    echo json_encode(['status' => 'ok', 'id' => $empleado_id, 'msg' => 'Empleado registrado']);
```

### 6.3 Lógica de editar

```php
case 'editar':
    // 1. Validar campos + verificar que el empleado pertenece a bss_id
    // 2. UPDATE empleados (nombre, rol, tipo_pago, monto_pago, moneda, dia_ejecucion, observacion)
    // 3. UPDATE gastos_recurrentes SET concepto, frecuencia, monto_estimado, moneda, dia_ejecucion WHERE id=recurrente_id
    // 4. Respuesta: ok
```

### 6.4 Lógica de activar/desactivar

```php
case 'desactivar':
    // UPDATE empleados SET activo=0 WHERE id=? AND bss_id=?
    // UPDATE gastos_recurrentes SET activo=0 WHERE id=recurrente_id
    // Respuesta: ok

case 'activar':
    // UPDATE empleados SET activo=1 WHERE id=? AND bss_id=?
    // UPDATE gastos_recurrentes SET activo=1 WHERE id=recurrente_id
    // Respuesta: ok
```

### 6.5 Función auxiliar: obtenerOCrearCategoriaPersonal

```php
function obtenerOCrearCategoriaPersonal($conexion, $bss_id) {
    // Buscar categoría "Personal" para este bss_id
    $stmt = $conexion->prepare("SELECT id FROM gastos_categorias WHERE nombre='Personal' AND bss_id=?");
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) return $row['id'];

    // Crear si no existe
    $stmt = $conexion->prepare("INSERT INTO gastos_categorias (nombre, bss_id) VALUES ('Personal', ?)");
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}
```

---

## 7. FASE 3 — Tabla HTML (endpoint)

**Archivo:** `publico/production/consulta_tablaEmpleados.php`

### 7.1 Patrón

Mismo patrón que `consulta_tablaPagos.php`:
- Recibe POST: `id_sucursal` (opcional)
- Query con `$conexion->query()` + `real_escape_string()`
- Retorna HTML `<table>` con Bootstrap classes
- Fila con `data-detalle='JSON'` para modal de detalle

### 7.2 Columnas de la tabla

| # | Columna | Campo | Ancho |
|---|---------|-------|-------|
| 1 | # | contador | 3% |
| 2 | Nombre | nombre | 20% |
| 3 | Rol | rol | 15% |
| 4 | Tipo pago | tipo_pago (badge) | 10% |
| 5 | Monto | monto_pago + moneda | 12% |
| 6 | Frecuencia | frecuencia (derivada de tipo_pago) | 10% |
| 7 | Día ejecución | dia_ejecucion (formateado) | 10% |
| 8 | Sucursal | id_sucursal (nombre) | 10% |
| 9 | Estado | activo (badge) | 5% |
| 10 | Acciones | Editar + Activar/Desactivar | 5% |

### 7.3 Formateo de día de ejecución

```php
// SEMANAL: 1=Lun, 2=Mar, ..., 7=Dom
$dias_semana = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo'];
// MENSUAL: 1-31
$dia_texto = ($tipo_pago === 'SEMANAL') ? ($dias_semana[$dia_ej] ?? $dia_ej) : 'Día ' . $dia_ej;
```

---

## 8. FASE 4 — Vista principal

**Archivo:** `publico/production/empleados.php`

### 8.1 Estructura HTML

```
<?php require_once('includes/requires.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Empleados</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>/* estilos copiados de gastos.php */</style>
</head>
<body>
    <div class="container body">
        <div class="main_container">
            <?php echo $menu; ?>
            <?php echo $topnav; ?>
            <div class="right_col">
                <!-- Header: "Empleados" + botón "Nuevo empleado" -->
                <!-- KPI cards: Total activos, Gasto mensual estimado, Gasto semanal estimado -->
                <!-- Panel: tabla de empleados -->
            </div>
        </div>
    </div>
    <!-- Scripts: jQuery, Bootstrap, custom.js -->
    <!-- Modal: Nuevo/Editar empleado -->
    <script>/* AJAX CRUD */</script>
</body>
</html>
```

### 8.2 KPI Cards

| Card | Valor | Fuente |
|------|-------|--------|
| Total empleados activos | COUNT(empleados WHERE activo=1) | AJAX a `empleados_crud.php` accion `listar` |
| Gasto mensual estimado | SUM(monto) de empleados MENSUAL | Calculado del listado |
| Gasto semanal estimado | SUM(monto) de empleados SEMANAL × 4 | Calculado del listado |

### 8.3 Modal Nuevo/Editar

```html
<div class="modal fade" id="modal-empleado">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-empleado-title">Nuevo empleado</h5>
      </div>
      <form id="form-empleado">
        <input type="hidden" id="emp-id">
        <div class="modal-body">
          <!-- Fila 1: Nombre* | Rol* -->
          <!-- Fila 2: Tipo pago* (SEMANAL/MENSUAL) | Monto* | Moneda -->
          <!-- Fila 3: Día de ejecución* (select dinámico) -->
          <!-- Fila 4: Observación -->
        </div>
        <div class="modal-footer">
          <button class="btn-dash-close">Cancelar</button>
          <button type="submit" class="btn-dash-submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
```

### 8.4 Día de ejecución dinámico

```javascript
function toggleDiaEjecucion() {
    const tipo = $('#empTipoPago').val();
    const $dia = $('#empDiaEjecucion');
    $dia.empty();

    if (tipo === 'SEMANAL') {
        $dia.append('<option value="">Seleccione día</option>');
        $dia.append('<option value="1">Lunes</option>');
        $dia.append('<option value="2">Martes</option>');
        // ... hasta Domingo (7)
    } else if (tipo === 'MENSUAL') {
        $dia.append('<option value="">Seleccione día</option>');
        for (let i = 1; i <= 31; i++) {
            $dia.append('<option value="' + i + '">' + i + '</option>');
        }
    }
}
```

### 8.5 JS CRUD

```javascript
const AJAX_URL = '../../configurar/empleados_crud.php';

// Cargar tabla al iniciar
function cargarTabla() {
    $.ajax({ url: AJAX_URL, type: 'POST', dataType: 'json',
             data: { accion: 'listar', id_sucursal: getSucursalId() } })
    .done(function(resp) {
        if (resp.status === 'ok') renderTabla(resp.data);
    });
}

// Form submit: crear o editar
$('#form-empleado').on('submit', function(e) {
    e.preventDefault();
    const id = $('#emp-id').val();
    const data = {
        accion: id ? 'editar' : 'crear',
        nombre: $('#empNombre').val(),
        rol: $('#empRol').val(),
        tipo_pago: $('#empTipoPago').val(),
        monto_pago: $('#empMonto').val(),
        moneda: $('#empMoneda').val(),
        dia_ejecucion: $('#empDiaEjecucion').val(),
        observacion: $('#empObservacion').val()
    };
    if (id) data.id = id;

    $.ajax({ url: AJAX_URL, type: 'POST', dataType: 'json', data: data })
    .done(function(resp) {
        if (resp.status === 'ok') {
            Toast.fire({ icon: 'success', title: resp.msg });
            $('#modal-empleado').modal('hide');
            cargarTabla();
        } else {
            Toast.fire({ icon: 'error', title: resp.msg });
        }
    });
});

// Editar: abrir modal con datos
function editarEmpleado(id, data) {
    $('#emp-id').val(id);
    $('#empNombre').val(data.nombre);
    $('#empRol').val(data.rol);
    $('#empTipoPago').val(data.tipo_pago);
    toggleDiaEjecucion();
    $('#empDiaEjecucion').val(data.dia_ejecucion);
    $('#empMonto').val(data.monto_pago);
    $('#empMoneda').val(data.moneda);
    $('#empObservacion').val(data.observacion);
    $('#modal-empleado-title').text('Editar empleado');
    $('#modal-empleado').modal('show');
}

// Activar/Desactivar
function toggleEmpleado(id, activar) {
    const accion = activar ? 'activar' : 'desactivar';
    Swal.fire({
        title: activar ? '¿Activar empleado?' : '¿Desactivar empleado?',
        icon: 'question', showCancelButton: true,
        confirmButtonColor: '#2dd4a0', cancelButtonColor: '#666'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({ url: AJAX_URL, type: 'POST', dataType: 'json',
                     data: { accion: accion, id: id } })
            .done(function(resp) {
                Toast.fire({ icon: 'success', title: resp.msg });
                cargarTabla();
            });
        }
    });
}
```

---

## 9. FASE 5 — Pruebas

### 9.1 Crear empleado semanal

```
1. Ir a empleados.php
2. Clic "Nuevo empleado"
3. Completar: Nombre="Juan Pérez", Rol="Cajero", Tipo=MENSUAL, Monto=500, Moneda=USD, Día=15
4. Guardar
5. Verificar: empleado aparece en tabla
6. Verificar en BD: gastos_recurrentes tiene registro con concepto="Pago: Juan Pérez", frecuencia=MENSUAL
7. Ir a gastos.php → auto-apply aplica el gasto → verificar que aparece "Pago: Juan Pérez" con monto $500
```

### 9.2 Desactivar empleado

```
1. Desactivar "Juan Pérez"
2. Verificar: empleado aparece como inactivo en tabla
3. Verificar: gastos_recurrentes.activo = 0
4. Recargar gastos.php → auto-apply NO crea gasto nuevo para este mes
5. Verificar: gastos anteriores se conservan
```

### 9.3 Editar empleado

```
1. Editar "Juan Pérez": cambiar monto a $600
2. Verificar: gastos_recurrentes.monto_estimado = 600
3. Recargar gastos.php → auto-apply crea gasto con $600 (si no existía para este mes)
```

### 9.4 Proyección

```
1. Crear 3 empleados (2 mensuales, 1 semanal)
2. Ir a gastos.php
3. Verificar: proyección muestra solo empleados SIN gasto aplicado este mes
4. Auto-apply aplica → proyección se reduce
```

### 9.5 Multisucursal

```
1. Login como nivel 1 (admin)
2. Crear empleado en sucursal A
3. Cambiar a sucursal B → empleado no aparece
4. Seleccionar "Todas" → empleado aparece
```

---

## 10. Orden de ejecución

```
PASO 1 — SQL: crear migración 002_empleados.sql
          + Ejecutar migración
          + Verificar tablas y menú

PASO 2 — Crear: configurar/empleados_crud.php
          (listar, crear, editar, activar, desactivar)

PASO 3 — Crear: publico/production/consulta_tablaEmpleados.php
          (tabla HTML server-side)

PASO 4 — Crear: publico/production/empleados.php
          (vista completa: header, KPIs, tabla, modal, JS)

PASO 5 — PHP lint todos los archivos nuevos

PASO 6 — Pruebas funcionales (ver sección 9)
          + Verificar integration con gastos.php (auto-apply + proyección)
```

---

## 11. Anti-patrones (NO hacer)

| Acción prohibida | Razón |
|------------------|-------|
| Crear tabla `gastos` directamente desde empleados | El gasto lo crea `aplicar_recurrentes_mes.php` |
| Insertar en `gastos_recurrentes` sin `categoria_id` de "Personal" | Los gastos deben tener categoría para reportes |
| Usar `get_result()` en empleados_crud.php | No hay mysqlnd — usar `$conexion->query()` o prepared statements con fetch |
| Queries sin `bss_id` AND `id_sucursal` | Rompe multitenant y multisucursal |
| Usar PDO | El proyecto usa `mysqli` |
| Modificar `gastos_recurrentes_crud.php` | CRUD independiente, no se toca |
| Generar gastos directamente al crear empleado | La aplicación es vía auto-apply al cargar gastos.php |
