# IMPLEMENTACIÓN: SISTEMA DE ASIGNACIÓN DE MERCANCÍA Y CONTROL DE DEUDAS ENTRE SUCURSALES

## 1. OBJETIVO GENERAL

Implementar dos módulos nuevos dentro del sistema POS para gestionar la asignación de mercancía entre sucursales y llevar el control financiero de las obligaciones generadas por dichas asignaciones.

El sistema debe permitir que una sucursal principal (Sucursal 1) distribuya mercancía a otra sucursal (Sucursal 2), generando automáticamente los movimientos de inventario y una obligación económica basada exclusivamente en el costo de los productos.

La Sucursal 1 será responsable de crear y aplicar las asignaciones, así como de registrar los abonos y pagos realizados por la Sucursal 2.

La Sucursal 2 solamente podrá consultar sus deudas y el historial de sus obligaciones.

**IMPORTANTE:** No se debe utilizar el módulo de ventas, compras ni cuentas por pagar a proveedores para implementar esta funcionalidad. Debe ser un flujo independiente, integrado con el inventario existente y los mecanismos financieros del POS.

El modulo de transferencia debe poder seleccionar en que forma se afecta el inventario origen y destino pudiendo alternar entre dos funciones para cada sucursal, En la sucursal origen: Descontar del inventario/No descontar; para la sucursal destino: adicionar al inventario/no adionar. En el caso de la sucursal origen, esta opcion me permite no tener que registrar el stock antes de traspasarlo.

## MODIFICACIÓN: SELECCIÓN DINÁMICA DE SUCURSAL DE ORIGEN Y DESTINO

### 1. Selección de sucursales

El módulo `Asignación de mercancía` debe permitir que el administrador seleccione libremente la sucursal de origen y la sucursal de destino.

No debe existir una relación fija entre Sucursal 1 y Sucursal 2.

El administrador podrá realizar asignaciones entre cualquier sucursal habilitada en el sistema.

Ejemplos:

- Depósito → Sucursal 2.
- Sucursal 1 → Sucursal 2.
- Sucursal 2 → Sucursal 3.
- Sucursal 3 → Depósito.
- Sucursal 1 → Depósito.

### 2. Interfaz de creación

Al crear una asignación, mostrar dos selectores obligatorios:

- **Sucursal de origen:** desde dónde se descontará la mercancía.
- **Sucursal de destino:** dónde se incrementará el inventario y se generará la obligación económica.

La selección debe realizarse antes de agregar los productos.

Una vez seleccionadas las sucursales, el buscador de productos y las existencias deben corresponder a la sucursal de origen seleccionada.

### 3. Reglas de negocio

1. La sucursal de origen y destino no pueden ser la misma.
2. La sucursal de origen debe tener existencias suficientes.
3. Al aplicar la asignación, se descuenta el inventario de origen y se incrementa el inventario de destino.
4. La obligación económica se genera a nombre de la sucursal destino.
5. La sucursal origen será la acreedora de dicha obligación.
6. El costo de los productos será el registrado al momento de aplicar la asignación, sin porcentajes adicionales.
7. La asignación debe conservar ambas sucursales como parte de su historial.
8. La operación debe funcionar independientemente de cuántas sucursales tenga registradas el negocio.

### 4. Control financiero

Las deudas deben identificarse mediante la relación:

- Sucursal acreedora: sucursal que entrega la mercancía.
- Sucursal deudora: sucursal que recibe la mercancía.

El administrador podrá consultar las obligaciones de todas las sucursales, independientemente de cuál haya sido el origen y destino de cada asignación.

Los pagos y abonos deben poder registrarse sobre la obligación correspondiente, conservando la relación entre las dos sucursales.

### 5. Permisos

El administrador autorizado podrá:

- Seleccionar cualquier sucursal como origen.
- Seleccionar cualquier sucursal como destino.
- Crear y aplicar asignaciones entre sucursales.
- Consultar todas las obligaciones económicas.
- Registrar abonos y pagos sobre cualquier obligación.

Las sucursales operativas solamente podrán consultar las obligaciones en las que figuren como sucursales deudoras, según sus permisos.

**IMPORTANTE:** No implementar condiciones fijas como `sucursal_origen = 1` o `sucursal_destino = 2`. Todas las operaciones deben trabajar con los identificadores dinámicos de las sucursales seleccionadas.

---

# 2. MÓDULO 1: ASIGNACIÓN DE MERCANCÍA

Nombre propuesto: `Asignación de mercancía`

Este módulo estará disponible para los usuarios autorizados de la Sucursal 1.

### 2.1. Creación de una asignación

Permitir crear un documento de asignación seleccionando:

- Sucursal de origen.
- Sucursal de destino.
- Fecha de asignación.
- Observaciones.
- Uno o varios productos.
- Cantidad de cada producto.

Debe ser posible agregar múltiples productos dentro de un mismo documento, sin necesidad de generar una asignación individual por producto.

Cada producto debe mostrar:

- Nombre del producto.
- Existencia actual en la sucursal de origen.
- Cantidad a asignar.
- Costo unitario.
- Subtotal.

El costo utilizado debe corresponder al costo registrado para el producto en el sistema, tomando una copia histórica de dicho valor al momento de aplicar la asignación.

No se debe agregar ningún porcentaje, comisión, margen de ganancia ni recargo al costo del producto.

### 2.2. Aplicación de la asignación

La asignación tendrá inicialmente el estado `BORRADOR`.

Al aplicar la asignación, el sistema deberá ejecutar las siguientes operaciones:

2. Descontar las cantidades asignadas del inventario de la sucursal de origen (solo en caso de que el usuario haya elegido descontar de la sucursal origen)
3. Incrementar automáticamente las mismas cantidades en el inventario de la sucursal de destino (solo en caso de que el usuario haya elegido adicionar a la sucursal destino)
4. Generar automáticamente la obligación económica de la sucursal de destino a favor de la sucursal de origen.
5. Generar el documento definitivo de asignación con su numeración.
6. Cambiar el estado del documento a `APLICADO`.

**No debe existir un proceso adicional de recepción ni confirmación de cantidades por parte de la sucursal de destino.**

La asignación aplicada representa una operación confirmada por la Sucursal 1.

Todas las operaciones anteriores deben ejecutarse dentro de una transacción SQL. Si alguna falla, se debe revertir la operación completa.

una asignacion podra ser modificada en caso de error y el usuario debera indicar la razon del cambio.

### 2.3. Cálculo del monto de la deuda

La deuda debe calcularse utilizando el costo unitario de cada producto.

Ejemplo:

| Producto     | Cantidad | Costo unitario |   Subtotal |
| ------------ | -------: | -------------: | ---------: |
| Pollo entero | 1.000 kg |          $2.00 |     $2,000 |
| Chuleta      |   200 kg |          $3.00 |       $600 |
| Carne molida |   100 kg |          $4.00 |       $400 |
| **Total**    |          |                | **$3,000** |

La sucursal destino adquiere automáticamente una deuda de $3,000 al aplicarse la asignación.

El importe debe quedar almacenado como valor histórico. Si posteriormente cambia el costo del producto en el catálogo, esto no debe modificar las asignaciones anteriores ni sus deudas.

### 2.4. Documentos e historial

Cada asignación debe tener:

- Número de documento.
- Fecha y hora.
- Sucursal de origen.
- Sucursal de destino.
- Usuario que realizó la operación.
- Detalle de productos.
- Cantidades.
- Costos unitarios.
- Total asignado.
- Estado.
- Referencia a los movimientos de inventario.
- Referencia a la cuenta por cobrar generada.

Permitir consultar, pdf y visualizar las asignaciones anteriores.

No permitir eliminar físicamente documentos aplicados.

---

# 3. MÓDULO 2: DEUDAS ENTRE SUCURSALES

Nombre propuesto: `Deudas entre sucursales`

Este módulo tendrá dos niveles de acceso.

### 3.1. Vista de la Sucursal

Las Sucursales tendrán acceso a una vista de consulta.

Podrá visualizar exclusivamente sus propias obligaciones económicas.

Mostrar:

- Número de asignación.
- Fecha.
- Total de la asignación.
- Total abonado.
- Saldo pendiente.
- Estado de la deuda.

También podrá consultar el detalle de los productos incluidos en cada asignación y el historial de pagos registrados.

**La Sucursal 2 no podrá registrar pagos, abonos, modificaciones ni ajustes financieros.**

---

# 4. REGISTRO DE ABONOS Y PAGOS

La administración de los pagos será responsabilidad exclusiva de la Sucursal 1.

No se necesita confirmación de pagos por parte de la Sucursal 2 ni un flujo de aprobación adicional.

### 4.1. Registro de un abono

Desde el módulo de deudas, los usuarios autorizados de la Sucursal 1 podrán registrar un abono indicando:

- Sucursal deudora.
- Importe abonado en dólares (USD).
- Tasa del dia al momento del abono (BCV y dolar/peso)
- Fecha del pago.
- Observaciones (opcional).
- Usuario que registra el pago.

El registro debe permitir múltiples abonos sobre una misma deuda.

### 4.2. Reglas de los pagos

1. Todos los abonos se registran en dólares.
2. Cada abono debe quedar asociado a la obligación correspondiente.
3. Debe existir un historial inalterable de los pagos realizados.
4. No se debe bloquear un pago porque el importe acumulado supere el monto original de la deuda.

### 4.3. Pagos superiores al monto de la deuda

Si el total abonado supera el monto original de la obligación, el sistema debe reconocer la diferencia como un excedente o ganancia.

Ejemplo:

- Deuda original: $3,000.
- Total abonado: $3,200.
- Deuda pendiente: $0.
- Excedente: $200.

El excedente debe mostrarse por separado y no debe generar un saldo de deuda negativo.

Conservar el importe original de la deuda y el historial completo de los pagos.

---

# 5. ESTADOS DE LAS DEUDAS

Manejar los siguientes estados:

- `PENDIENTE`: no se han registrado abonos.
- `PARCIAL`: existen abonos, pero todavía queda saldo pendiente.
- `PAGADA`: el total abonado alcanza o supera el importe original.

El excedente debe mostrarse como un dato independiente.

El estado debe calcularse automáticamente a partir del importe original y los pagos registrados.

## Debe existir un estatus de deuda global por sucursal en la cual pueda consultar la sumatoria de todas deudas de una sucursal sobre la otra.

# 6. ESTRUCTURA DE BASE DE DATOS PROPUESTA

Diseñar las tablas necesarias respetando la arquitectura actual del sistema.

Como referencia funcional:

### Tabla: `asignaciones_sucursales`

Cabecera de los documentos de asignación.

Campos sugeridos:

- id
- numero_documento
- sucursal_origen
- sucursal_destino
- fecha
- usuario_id
- estado
- total
- observaciones
- created_at
- updated_at

### Tabla: `asignaciones_sucursales_detalles`

Productos incluidos en cada asignación.

Campos sugeridos:

- id
- asignacion_id
- producto_id
- cantidad
- costo_unitario
- subtotal

### Tabla: `cuentas_sucursales`

Obligaciones económicas generadas por las asignaciones.

Campos sugeridos:

- id
- asignacion_id
- sucursal_acreedora
- sucursal_deudora
- monto_original
- created_at

Establecer una restricción UNIQUE sobre `asignacion_id` para garantizar que una asignación no genere dos obligaciones económicas.

### Tabla: `pagos_sucursales`

Historial de abonos registrados.

Campos sugeridos:

- id
- cuenta_sucursal_id
- monto_usd
- fecha
- metodo_pago
- referencia
- observaciones
- usuario_id
- created_at

La estructura definitiva debe adaptarse a las convenciones de nombres, tipos de datos, claves primarias y auditoría que ya utiliza el POS.

---

# 7. SEGURIDAD Y PERMISOS

Implementar las siguientes restricciones:

**Sucursal 1:**

- Crear asignaciones.
- Editar asignaciones en borrador.
- Aplicar asignaciones.
- Consultar todas las asignaciones.
- Consultar deudas de todas las sucursales.
- Registrar abonos y pagos.
- Consultar historiales financieros.

**Sucursal 2:**

- Consultar sus propias deudas.
- Consultar sus propias asignaciones.
- Consultar los pagos registrados.
- No puede crear asignaciones.
- No puede aplicar asignaciones.
- No puede registrar pagos.
- No puede modificar información financiera.

Validar los permisos en el backend. No depender exclusivamente de ocultar botones en la interfaz.

---

# 8. INTEGRACIÓN CON EL POS EXISTENTE

Antes de implementar, analizar los módulos existentes de:

- Inventario por sucursal.
- Transferencias de stock.
- Catálogo de productos y costos.
- Usuarios y permisos.
- Sucursales.
- Cajas y métodos de pago.
- Auditoría y movimientos de inventario.

No duplicar la lógica de inventario ni crear un segundo sistema de existencias.

Las asignaciones deben generar movimientos identificables y auditables, permitiendo distinguirlos de las transferencias convencionales.

Si se reutiliza la infraestructura de movimientos existente, incorporar un tipo o referencia que permita identificar el origen como `ASIGNACION_SUCURSAL`.

Los registros financieros de las deudas y pagos deben mantenerse independientes de las ventas y compras convencionales.

---

# 9. INTERFAZ Y EXPERIENCIA DE USUARIO

Mantener el lenguaje visual y los componentes existentes del POS.

El diseño debe ser moderno, limpio y profesional, con especial atención a la facilidad de uso y manteniendo la misma linea visual del pos general (cards sin background, nada de emojis, en general mantener consistencia con el resto de modulos).

En el módulo de asignaciones:

- Selector de sucursal origen.
- Selector de sucursal destino.
- Buscador de productos (tanto detal como al mayor).
- Tabla dinámica para agregar múltiples productos.
- Visualización del costo y subtotal.
- Total general destacado.
- Botón para guardar borrador.
- Botón para aplicar asignación.
- Confirmación antes de aplicar.

En el módulo de deudas:

- Resumen financiero.
- Filtro por sucursal.
- Filtro por estado.
- Tabla de asignaciones y obligaciones.
- Detalle de abonos.
- Acción para registrar un nuevo abono.
- Indicadores de deuda pendiente y excedente.

En la vista de la Sucursal 2, mostrar únicamente la información correspondiente a esa sucursal.

---

# 10. REGLAS TÉCNICAS OBLIGATORIAS

1. Utilizar transacciones SQL para aplicar asignaciones y registrar pagos.
2. Evitar duplicación de documentos y obligaciones.
3. Evitar que una misma asignación aplicada vuelva a procesarse.
4. Conservar los costos históricos.
5. Mantener auditoría de las operaciones.
6. No permitir modificaciones silenciosas de documentos aplicados.
7. Garantizar que las cantidades y montos sean consistentes entre inventario y finanzas.
8. Evitar condiciones de carrera al aplicar asignaciones concurrentes.
9. Implementar índices y restricciones de integridad necesarios.
10. El acceso a estos modulos se debe asignar mediante el modulo de permisos para usuarios estandar, los usuarios administradores podran acceder.
11. Un administrador no podra modificar ni registrar abonos en un registro que agrego otro.

---

# 11. PRUEBAS DE FUNCIONAMIENTO

Antes de considerar terminado el módulo, verificar los siguientes escenarios:

- Crear una asignación con un solo producto.
- Crear una asignación con múltiples productos.
- Aplicar una asignación correctamente.
- Verificar que se descuente el stock de origen.
- Verificar que se incremente el stock de destino.
- Verificar que se genere una única deuda.
- Verificar que el monto corresponda exactamente al costo de los productos.
- Verificar que no se agreguen porcentajes.
- Registrar un abono parcial.
- Registrar varios abonos.
- Registrar pagos superiores a la deuda.
- Verificar el cálculo del excedente.
- Consultar las deudas desde la Sucursal 2.
- Verificar que la Sucursal 2 no pueda registrar pagos.
- Verificar que una asignación aplicada no pueda procesarse nuevamente.
- Verificar que los movimientos de inventario y financieros no se dupliquen.

---

# 12. INSTRUCCIÓN FINAL PARA EL AGENTE

Antes de escribir código, inspeccionar la estructura actual del proyecto y comprender cómo funcionan los módulos existentes de inventario, transferencias, productos, sucursales, permisos y movimientos financieros.

No asumir nombres de tablas, columnas, funciones ni estructuras que no hayan sido verificadas.

Implementar los dos módulos respetando la arquitectura actual del sistema, reutilizando componentes y funciones existentes.

No modificar ni romper el funcionamiento del módulo convencional de transferencias de stock.

No renombrar funcionalidades existentes ni alterar otras operaciones del POS.

La implementación debe quedar completamente funcional, integrada con la base de datos real, con validaciones backend, controles de permisos, transacciones SQL y una interfaz consistente con el resto del sistema.

Entregar los archivos completos modificados y creados, junto con las migraciones SQL necesarias y una descripción de las pruebas realizadas.
