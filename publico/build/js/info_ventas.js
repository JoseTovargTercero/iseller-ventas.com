["fechaSolic", "sucursal_selector", "usuario"].forEach((id) => {
  document.getElementById(id).addEventListener("change", cargarInfo);
});
function cargarInfo() {
  const fechaSolic = document.getElementById("fechaSolic").value;
  const sucursal = document.getElementById("sucursal_selector").value;
  let usuario = document.getElementById("usuario").value;

  if (usuario === "") {
    usuario = "todos";
  }

  fetch("../../configurar/listaVentas_back.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      periodo: periodo,
      fechaSolic: fechaSolic,
      sucursal: sucursal,
      usuario: usuario,
    }),
  })
    /*.then((response) => response.text()) // Primero obtenemos el texto plano
    .then((text) => {
      console.log(text);
    })*/
    .then((response) => response.json())
    .then((data) => {
      table.clear();

      document.getElementById("ganacias_Bolivares").innerHTML =
        data.ganacias_Bolivares;
      document.getElementById("valor_Bolivares").innerHTML =
        data.valor_Bolivares;
      document.getElementById("ganacias_Dolares").innerHTML =
        data.ganacias_Dolares;
      document.getElementById("valor_Dolares").innerHTML = data.valor_Dolares;
      document.getElementById("ganacias_Pesos").innerHTML = data.ganacias_Pesos;
      document.getElementById("valor_Pesos").innerHTML = data.valor_Pesos;
      document.getElementById("ganacias_Mayor").innerHTML = data.ganacias_Mayor;
      document.getElementById("valor_Mayor").innerHTML =
        "$ " + data.valor_Mayor;
      document.getElementById("ganacias_Detal").innerHTML = data.ganacias_Detal;
      document.getElementById("valor_Detal").innerHTML =
        "$ " + data.valor_Detal;
      document.getElementById("total_Pmovil").innerHTML = data.total_Pmovil;
      document.getElementById("total_Transferencia").innerHTML =
        data.total_Transferencia;
      document.getElementById("total_Biopago").innerHTML = data.total_Biopago;
      document.getElementById("total_Efectivo").innerHTML = data.total_Efectivo;
      document.getElementById("total_Dolares").innerHTML = data.total_Dolares;
      document.getElementById("total_pesos").innerHTML = data.total_pesos;
      document.getElementById("total_Punto").innerHTML = data.total_Punto;

      data.tabla.forEach((row) => {
        table.row.add([
          row.contador,
          row.tVenta,
          row.tipoPago,
          row.usuario,
          row.created,
          row.total_price,
          row.total_price_cop,
          row.total_price_bs,
          `<a href='${row.detallesLink}' title='${row.productosTexto}'>Detalles</a>`,
        ]);
      });
      table.draw();
    })
    .catch((error) => {
      console.error("Error en la solicitud:", error);
      alert("Hubo un problema con la conexión al servidor.");
    });
}
cargarInfo();
