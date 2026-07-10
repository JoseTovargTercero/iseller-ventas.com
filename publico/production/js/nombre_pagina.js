// 1. Definimos solo los datos variables por página
const configPaginas = {
  "ventas.php": { titulo: "Ventas", subtitulo: "Caja de despacho" },
  "index.php": { titulo: "Dashboard", subtitulo: "Resumen y estadísticas" },
  "listaVentas.php": { titulo: "Ventas", subtitulo: "Ventas del dia" },
  "creditos.php": { titulo: "Creditos", subtitulo: "Lista de creditos" },
  "productos.php": { titulo: "Productos", subtitulo: "Lista de productos" },
  "clientes.php": { titulo: "Clientes", subtitulo: "Gestión de clientes" },
  "users.php": { titulo: "Usuarios", subtitulo: "Registro de usuarios" },
  "permisos.php": { titulo: "Permisos", subtitulo: "Permisos de usuarios" },
  "ficha.php": {
    titulo: "Detalles del producto",
    subtitulo: "Consulte los detalles de ventas del producto",
  },
  "seleccion_sucursal.php": {
    titulo: "Selección de sucursal",
    subtitulo: "Seleccione una sucursal antes de proceder con la ventas",
  },
  "configuracion.php": {
    titulo: "Configuracion",
    subtitulo: "Configuracion del sistema",
  },
  "consultaHistorica.php": {
    titulo: "Consultas",
    subtitulo: "Reportes y consultas",
  },
  "stock_critico.php": {
    titulo: "Stock crítico",
    subtitulo: "Lista de productos con stock crítico",
  },
  "productos_al_mayor.php": {
    titulo: "Agregar producto al mayor",
    subtitulo: "Registre la version de venta al mayor de un producto",
  },
  "transferir.php": {
    titulo: "Transferir stock",
    subtitulo: "Transfiera stock entre sucursales",
  },
  "nuevaCompra.php": {
    titulo: "Compras",
    subtitulo: "Registro de productos agregados al stock",
  },
  "nuevo_producto_sucursal.php": {
    titulo: "Agregar producto",
    subtitulo: "Agregue un producto existente a la sucursal",
  },
  "modificar_stock.php": {
    titulo: "Modificar stock",
    subtitulo: "Modifique las cantidades en stock",
  },
  "nuevoProducto.php": {
    titulo: "Nuevo producto",
    subtitulo: "Agregar un producto nuevo al stock",
  },
  "listaVentas_semana.php": {
    titulo: "Ventas",
    subtitulo: "Ventas de la semana",
  },
  "listaVentas_mes.php": { titulo: "Ventas", subtitulo: "Ventas del mes" },

  "cierres_caja.php": {
    titulo: "Cierres de Caja",
    subtitulo: "Cortes de caja por usuario",
  },
  "sucursales.php": {
    titulo: "Sucursales",
    subtitulo: "Gestión de sucursales",
  },
  "tutoriales.php": {
    titulo: "Dashboard",
    subtitulo: "Aprende a usar el sistema con estos videos guía",
  },
};

// 2. Obtenemos el nombre del archivo
const nombreArchivo = window.location.pathname.split("/").pop();
const container = document.getElementById("nombre_pagina");

// 3. Función para generar el HTML dinámicamente
function renderizarEncabezado(nombre) {
  const data = configPaginas[nombre];

  if (!data) return null;

  return `
    <div class="mb-2">
      <h3 class="mb-0" style="font-size: 18px; color: var(--dash-text); font-weight: 700; letter-spacing: -0.3px;">
        ${data.titulo}
      </h3>
      <p style="color: var(--dash-text-muted); margin-bottom: 0;">
        ${data.subtitulo}
      </p>
    </div>
  `;
}

// 4. Aplicamos al DOM
if (container) {
  const htmlGenerado = renderizarEncabezado(nombreArchivo);
  if (htmlGenerado) {
    container.innerHTML = htmlGenerado;
  } else {
    console.warn(`No se encontró configuración para: ${nombreArchivo}`);
  }
}
