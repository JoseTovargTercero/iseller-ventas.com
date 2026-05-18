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
                let monto = '';
                switch (row.tipoPago) {
                    case 'Dólares':
                        monto = '$ ' + row.total_price;
                        break;
                    case 'Pesos':
                        monto = row.total_price_cop + ' Cop';
                        break;
                    default:
                        monto = row.total_price_bs + ' Bs';
                        break;
                }

                table.row.add([
                    row.contador,
                    row.tVenta,
                    row.tipoPago,
                    row.usuario,
                    row.created,
                    monto,
                    row.cliente,
                    `<a href='#' onclick='verDetalles("${row.id}")' title='${row.productosTexto}'>Detalles</a>`,
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


function verDetalles(id) {
    Swal.fire({
        title: "Cargando detalles...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    fetch(`detallesVenta.php?id=${id}`)
        .then((response) => response.text())
        .then((html) => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, "text/html");
            const content = doc.querySelector(".x_content");

            if (content) {
                // Quitar el título repetido si existe
                const title = content.querySelector(".x_title");
                if (title) title.remove();

                Swal.fire({
                    title: '<h1><i class="bx bx-receipt"></i> Detalles de la Venta</h1>',
                    html: `<div class="text-start" style="font-size: 14px;">${content.innerHTML}</div>`,
                    width: "80%",
                    showCloseButton: true,
                    showDenyButton: true,
                    denyButtonText: "Eliminar Venta",
                    denyButtonColor: "#d63530ff",
                    confirmButtonText: "Cerrar",
                    confirmButtonColor: "#3085d6",
                }).then((result) => {
                    if (result.isDenied) {
                        // Se debe preguntar al usuario si desea eliminar la venta y si lo confirma llama a eliminarVenta()
                        Swal.fire({
                            title: "Eliminar Venta",
                            text: "¿Está seguro de eliminar la venta?",
                            icon: "warning",
                            showCancelButton: true,
                            cancelButtonText: "Cancelar",
                            confirmButtonText: "Eliminar",
                            confirmButtonColor: "#3085d6",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                eliminarVenta(id);
                            }
                        });
                    };

                });



            } else {
                Swal.fire("Error", "No se pudo encontrar el contenido de los detalles.", "error");
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            Swal.fire("Error", "Hubo un problema al cargar los detalles.", "error");
        });
}


function eliminarVenta(id) {
    $.ajax({
            url: '../../configurar/deleteVentaAjax.php',
            type: 'POST',
            dataType: 'html',
            data: {
                id: id
            },
        })
        .done(function(resultado1) {
            console.log(resultado1)
            const respuesta = JSON.parse(resultado1)
            if (respuesta.status == true) {
                // oculta todo swal activo
                //  Swal.close();
                Swal.fire("success", "Eliminado correctamte.", "success");
                cargarInfo();
            }
        })
}