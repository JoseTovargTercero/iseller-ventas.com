# Plan: tasas de cambio personalizadas por producto y par

## 1. Objetivo

- En `publico/production/cambiar_tasas.php` se crean tasas personalizadas por
  par de conversión. No hay tasas activas/inactivas: son definiciones
  reutilizables.
- Desde `publico/production/productos.php` se asignan tasas personalizadas a un
  producto, **una por cada par**. Un producto puede tener las 4, algunas o
  ninguna.
- Los pares sin tasa personalizada siguen usando la tasa principal y el tipo de
  cambio mostrado, sin cambios.
- La resolución de tasas por producto se centraliza en
  `configurar/_calculadrora_precios.php` y su copia
  `iseller_store/core/_calculadrora_precios.php`.
- La asignación la puede hacer cualquier usuario con acceso a `productos.php`
  (niveles 1 y 2). El endpoint valida el mismo permiso.

## 2. Modelo de datos

### 2.1 Definiciones de tasas

```sql
CREATE TABLE IF NOT EXISTS cambio_tasas_personalizadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bss_id INT NOT NULL,
    par ENUM('usd_bs','usd_cop','cop_bs','bs_cop') NOT NULL,
    nombre VARCHAR(60) NOT NULL,
    valor DECIMAL(18,6) NOT NULL,
    creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tasa (bss_id, par, nombre),
    KEY idx_bss_par (bss_id, par)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Sin columna `activa`. La tasa principal sigue en `cambio`.

| `par`    | Tasa que reemplaza |
|----------|--------------------|
| `usd_bs` | `dolarBolivar` + `bcv` |
| `usd_cop`| `pesoDolar`        |
| `cop_bs` | `peso_bolivar`     |
| `bs_cop` | `bolivar_peso`     |

`usd_bs` reemplaza también `bcv` **solo para ese producto**, para que el precio
en Bs y el equivalente en USD sean coherentes.

### 2.2 Asignación por producto

```sql
CREATE TABLE IF NOT EXISTS producto_tasas_personalizadas (
    producto_id INT NOT NULL,
    par ENUM('usd_bs','usd_cop','cop_bs','bs_cop') NOT NULL,
    tasa_id INT NOT NULL,
    bss_id INT NOT NULL,
    PRIMARY KEY (producto_id, par),
    UNIQUE KEY uq_producto_tasa (producto_id, tasa_id),
    KEY idx_tasa (tasa_id),
    KEY idx_bss (bss_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

- `PRIMARY KEY (producto_id, par)`: una sola tasa por par y producto.
- `UNIQUE (producto_id, tasa_id)`: la misma tasa no se asigna dos veces.
- `par` se guarda para facilitar la lectura y validar que coincida con la tasa.
- No se usan claves foráneas, igual que el resto del esquema; la integridad se
  valida en los endpoints y se limpia al borrar producto o tasa.

No se agrega ninguna columna a `productos`.

## 3. Cálculo centralizado en `CalculadoraPrecios`

### 3.1 Contexto y mapa de overrides

La calculadora recibe opcionalmente la conexión y el `bss_id`:

```php
public function __construct(
    float $pesoDolar,
    float $peso_bolivar,
    float $dolarBolivar,
    float $bolivar_peso,
    float $bcv,
    array $data_monedas,
    $conexion = null,
    int $bssId = 0
)
```

La primera vez que calcula un producto carga, en una sola consulta y con caché
por `bss_id`:

```sql
SELECT pt.producto_id, pt.par, tp.valor
FROM producto_tasas_personalizadas pt
INNER JOIN cambio_tasas_personalizadas tp
    ON tp.id = pt.tasa_id
   AND tp.bss_id = pt.bss_id
   AND tp.par = pt.par
WHERE pt.bss_id = ?
```

Mapa resultante:

```php
$mapa[producto_id] = [
    'usd_bs'  => 12.50,
    'cop_bs'  => 0.0041,
    // solo los pares asignados
];
```

### 3.2 Tasas efectivas del producto

Agregar:

```php
public function tasasDeProducto(array $producto): array
```

- Parte de las tasas principales de la instancia.
- Busca el producto por `producto_id` y, si no existe, por `id`.
  En `productos_list.php` el `id` de la fila es el de `stock`; el id real es
  `producto_id`.
- Aplica **todos** los overrides del producto:
  - `usd_bs` → `dolarBolivar` y `bcv`.
  - `usd_cop` → `pesoDolar`.
  - `cop_bs` → `peso_bolivar`.
  - `bs_cop` → `bolivar_peso`.
- Si un valor es 0, negativo o el par no existe, se ignora.
- Si el producto no tiene overrides, devuelve exactamente las principales.

`calcularPrecios()` debe usar las tasas devueltas por `tasasDeProducto()` en
todas sus ramas, no las propiedades directas `$this->...`.

`convertirMonto()` recibe un cuarto parámetro opcional con el producto o su id:

```php
public function convertirMonto($monto, $monedaOrigen, $origen, $producto = null)
```

Si se pasa el producto, aplica sus overrides; si no, usa las principales.
Actualizar los llamados que ya tienen contexto de producto
(`creditos_por_cliente*.php`, checkout, carrito).

Agregar también:

```php
public function getTasaProducto(string $nombre, array $producto): float
```

para los módulos que necesitan una tasa puntual del producto (por ejemplo
`price_C_Bs` / `price_C_Cop` del POS).

### 3.3 Copias de la calculadora

Actualizar las dos copias con la misma lógica:

- `configurar/_calculadrora_precios.php`
- `iseller_store/core/_calculadrora_precios.php`

La copia de tienda tiene `$recompensas` y `getTasa()`; agregar `$conexion` y
`$bssId` al final del constructor para no romper llamadas existentes.

## 4. Respetar el tipo de cambio mostrado

No se cambia la lógica que decide qué par se usa. `data_monedas`, `origen` y las
ramas actuales de `calcularPrecios()` / `convertirMonto()` siguen mandando.

La tasa personalizada solo reemplaza el **valor** del par que ese cálculo ya iba
a usar. Ejemplo:

- Producto colombiano: su precio en Bs se calcula con `pesoDolar` y
  `peso_bolivar`. Si tiene override de `cop_bs`, se usa ese divisor; si tiene
  override de `usd_cop`, se usa ese multiplicador; si tiene ambos, se usan ambos.
- Producto venezolano: su precio en Bs se calcula con `dolarBolivar`. Si tiene
  override `usd_bs`, se usa ese valor (y también como `bcv` del producto).
- Si el tipo de cambio mostrado hace que el COP visible salga por `bolivar_peso`,
  se aplica el override `bs_cop`; si sale por `pesoDolar`, se aplica `usd_cop`.
- Un override de un par que ese origen/tipo de cambio no usa no altera el
  resultado. Es esperado.

En la UI del producto se pueden mostrar los 4 pares, con una nota de que solo se
aplicará el par que use el cálculo según el origen y las tasas mostradas.

## 5. Backend de tasas personalizadas

Archivo nuevo: `configurar/tasas_personalizadas.php` (`application/json`).

| Acción       | Datos                    | Resultado |
|--------------|--------------------------|-----------|
| `listar`     | —                        | Tasas agrupadas por par + cantidad de productos que usan cada una |
| `crear`      | `par`, `nombre`, `valor` | Inserta y devuelve la fila |
| `actualizar` | `id`, `nombre`, `valor`  | Edita nombre/valor; el `par` no se cambia |
| `eliminar`   | `id`, `forzar`           | Borra la definición y sus asignaciones |

Reglas:

- `bss_id` desde `$_SESSION['bss_id']`.
- `par` contra lista blanca.
- `valor` numérico > 0, 6 decimales.
- Nombre obligatorio, `trim`, máximo 60, sin HTML.
- El `id` debe pertenecer al `bss_id`.
- Nombre duplicado en el mismo par: mensaje claro.
- `par` inmutable al actualizar: cambiarlo dejaría asignaciones inconsistentes.
- `eliminar`:
  1. Contar asignaciones en `producto_tasas_personalizadas`.
  2. Sin `forzar`: error con el conteo.
  3. Con `forzar`: transacción → borrar asignaciones → borrar tasa.
- Al editar el valor, todos los productos asignados lo toman automáticamente
  porque el mapa se lee en cada request.

## 6. Asignación desde el listado de productos

### 6.1 Endpoint

Archivo nuevo: `configurar/producto_tasas.php`.

Acciones:

| Acción    | Datos | Resultado |
|-----------|-------|-----------|
| `obtener` | `producto_id` | Asignaciones actuales + tasas disponibles agrupadas por par |
| `guardar` | `producto_id`, `tasas[par] = id` | Reemplaza las asignaciones del producto |

Validaciones:

- Permitir solo niveles 1 y 2, igual que `productos.php`.
- Producto perteneciente al `bss_id`.
- Cada `tasa_id` perteneciente al `bss_id`.
- El `par` de la clave debe coincidir con el `par` de la tasa.
- Valores vacíos o `0` significan “principal” para ese par.
- `guardar` en transacción:
  1. `DELETE FROM producto_tasas_personalizadas WHERE producto_id = ? AND bss_id = ?`
  2. Insertar solo los pares con tasa seleccionada.
- Devolver las asignaciones guardadas y sus nombres para refrescar la fila.

### 6.2 UI en `productos.php`

- Agregar un botón de tasas junto al lápiz de edición, en la celda de acciones.
  No se agrega una columna nueva para no reindexar DataTables.
- Si el producto tiene al menos una tasa, mostrar un badge junto al nombre:
  `Tasas: 2`.
- Al hacer clic, abrir un modal con 4 selectores, uno por par:
  - `USD → Bs`
  - `USD → COP`
  - `COP → Bs`
  - `Bs → COP`
  - Cada uno con `Principal` + las tasas personalizadas de ese par.
  - Mostrar nombre y valor: `Paralelo · 12,50`.
  - Si no hay tasas para un par, el selector queda solo con `Principal` y una
    nota para crearlas en Tasas de cambio.
- Guardar todo junto con `accion=guardar`; al terminar, recargar el listado para
  recalcular precios y badges.
- El botón está disponible para niveles 1 y 2, igual que el resto del listado.
- En móvil el botón va dentro de la celda de acciones, que ya se muestra.

### 6.3 Datos del listado

En `configurar/productos_list.php` agregar un contador por producto:

```sql
(SELECT COUNT(*)
 FROM producto_tasas_personalizadas pt
 WHERE pt.producto_id = P.id AND pt.bss_id = P.bss_id) AS tasas_count
```

No hacer `JOIN` con `producto_tasas_personalizadas` en la consulta principal:
multiplicaría las filas y alteraría `SUM(S.stock)`. El contador va como subconsulta
correlacionada o en una segunda consulta por los ids del listado.

### 6.4 Producto al mayor, combos y borrado

- El producto al mayor es otra fila de `productos`; se le asignan tasas por
  separado con el mismo modal.
- Los combos calculan el precio de cada hijo; si el hijo tiene tasas, la
  calculadora las aplica al calcular ese hijo.
- Al copiar un producto (`buscar_productos_copiar.php`), no copiar asignaciones;
  el nuevo producto inicia sin tasas.
- Al borrar un producto (`deleteProAjax.php` y cualquier otro borrado), eliminar
  sus filas de `producto_tasas_personalizadas`.

## 7. UI de `cambiar_tasas.php`

Se mantiene el carrusel por card, sin estado activo:

- Índice `0` = tasa principal; índices `1..N` = personalizadas.
- Flechas `‹ 1/3 ›` para navegar, con wrap-around.
- Menú `⋮` arriba a la derecha:
  - `Agregar tasa`
  - `Renombrar`
  - `Eliminar`
- Badge: `Principal` en el índice 0; el nombre de la tasa en los demás.
- Input editable al mostrar una personalizada; se oculta la nota de BCV.
- `Guardar cambios` sigue guardando la principal (tipo, margen, redondeo y los
  4 valores base). Cada personalizada se guarda con su botón inline.
- Al crear una tasa, se agrega al carrusel y se navega a ella. No se activa nada.
- Al eliminar una tasa asignada, la UI muestra cuántos productos la usan y
  ofrece `Eliminar y dejar esos pares en tasa principal`.
- Texto de ayuda: “Estas tasas se asignan por producto y par desde Productos”.

No hay badge de “Activa” ni acción de activar.

## 8. Dónde debe aplicarse la calculadora

Pasar `$conexion` y `$bss_id` en las instanciaciones que calculan precios de
producto:

- `configurar/productos_list.php`
- `configurar/consulta_producto.php`
- `configurar/consultar_precio.php`
- `configurar/creditos_por_cliente.php`
- `configurar/creditos_por_cliente_total_abonado.php`
- `configurar/combos_back.php`
- `publico/production/ventas.php`
- `publico/production/ventas_estable.php`
- `iseller_store/api/productos.php`
- `iseller_store/api/productos_socios.php`
- `iseller_store/checkout.php`
- `iseller_store/core/accion_carta.php`
- `iseller_store/core/api_v1.php`
- `iseller_store/api/v1/products.php`
- `iseller_store/api/v1/orders.php`
- `index.php` (tienda)
- `configurar/text_calc.php` (prueba)

Los módulos que usan tasas para gastos, cron, proyecciones o estadísticas no
calculan productos: siguen con las tasas principales.

### 8.1 POS y precios especiales

`publico/production/ventas.php`:

- `price_C_Bs` y `price_C_Cop` deben usar `getTasaProducto()` para el producto.
- Enviar al JS el mapa de overrides del producto (`{par: valor, ...}`) y hacer
  que `calcularPreciosDesdeUsd()` mezcle ese mapa con `tasasCalculo`.
- Los precios normales del catálogo ya vienen calculados por el servidor.
- Revisar `productos_al_mayor.php`, `stock_critico.php` y `nuevoProducto.php`:
  sus vistas previas en JS usan tasas globales. El servidor calculará bien, pero
  la vista previa debe recibir los overrides si se edita un producto con tasas.

### 8.2 Tienda, checkout y carrito

- Los precios que se muestran salen de la calculadora; al pasarle la conexión,
  quedan corregidos.
- `convertirMonto()` debe recibir el producto cuando exista.
- El envío y los cálculos globales siguen usando la tasa principal.

## 9. Casos borde

- Producto sin tasas: comportamiento actual, sin cambios.
- Producto con una, varias o las 4 tasas: se aplican solo los pares asignados.
- Tasa de otro negocio: se ignora.
- Tasa eliminada: sus pares vuelven a principal.
- Tasa con valor 0 o inválido: se ignora.
- Override de un par no usado por el origen/tipo de cambio: no altera el precio.
- `usd_bs`: reemplaza `dolarBolivar` y `bcv` del producto.
- `usd_cop`: cambia el COP y, por arrastre, el Bs de productos colombianos.
- `cop_bs`: cambia el Bs de productos colombianos.
- `bs_cop`: cambia el COP visible de productos venezolanos.
- `productos_list.php` debe resolver por `producto_id`, no por el id de `stock`.
- El producto al mayor y los combos se resuelven por su propio id.
- Borrar un producto limpia sus asignaciones.
- Par inmutable en una tasa ya creada.

## 10. Pruebas

Selftest CLI (`configurar/tasas_personalizadas_selftest.php`, solo `php-cli`):

1. Crear tasas de prueba para los 4 pares.
2. Asignar las 4 a un producto y verificar `tasasDeProducto()`.
3. Verificar `calcularPrecios()` para orígenes `v`, `c` y `m`.
4. Verificar que un producto sin asignaciones devuelve las principales.
5. Verificar que un producto con solo `cop_bs` no cambia su COP.
6. Verificar que otro `bss_id` no se ve afectado.
7. Eliminar una tasa con `forzar` y verificar que sus pares vuelven a principal.
8. Limpiar los datos.

Matriz manual:

- Asignar solo `USD → Bs` a un producto venezolano: cambia Bs y USD; COP sigue
  según el tipo de cambio mostrado.
- Asignar solo `COP → Bs` a un producto colombiano: cambia Bs.
- Asignar `USD → COP` y `COP → Bs` a un producto colombiano: cambian ambos.
- Asignar `Bs → COP` a un producto venezolano cuyo COP visible sale por esa tasa:
  cambia COP.
- Producto con las 4 tasas: se aplican todas.
- Producto sin tasas: precios idénticos a los actuales.
- Verificar listado, POS, tienda, checkout, carrito y precio especial del POS.
- Editar el valor de una tasa asignada: el producto se actualiza.
- Eliminar una tasa asignada: el par vuelve a principal.
- Probar con usuario nivel 2.
- Móvil: botón y modal utilizables.

## 11. Orden de implementación

1. Migraciones: tabla de tasas + tabla de asignaciones.
2. Endpoint CRUD de tasas personalizadas.
3. UI de carrusel en `cambiar_tasas.php`.
4. Calculadora: mapa por producto, `tasasDeProducto()`, `calcularPrecios()`,
   `convertirMonto()` y `getTasaProducto()`.
5. Endpoint `producto_tasas.php` + botón/modal en `productos.php` + contador en
   `productos_list.php`.
6. Pasar conexión/bss a las instanciaciones de la calculadora.
7. Overrides en el JS del POS y vistas previas.
8. Limpieza de asignaciones al borrar productos/tasas.
9. Selftest y matriz manual.

Despliegue: migraciones primero, código después. Sin asignaciones, el sistema se
comporta igual que hoy. Rollback funcional: vaciar
`producto_tasas_personalizadas`.

## 12. Decisiones restantes

1. ¿La tasa personalizada se puede editar en nombre y valor, pero no en par?
   (Recomendado: sí, para no romper asignaciones).
2. ¿Al asignar tasas a un producto se debe ofrecer replicarlas al producto al
   mayor relacionado? (Recomendado: preguntar, no automático).
