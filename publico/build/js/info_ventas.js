["fechaSolic", "sucursal_selector", "usuario"].forEach((id) => {
    document.getElementById(id).addEventListener("change", cargarInfo);
});

const ORDENES_GRUPO = new URLSearchParams(window.location.search).get("ordenes") || "";

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
                ordenes: ORDENES_GRUPO,
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
                    case 'Fraccionado':
                        // Pago dividido entre medios; se muestra el total en USD
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
                    row.cobrado || '-',
                    row.total_price_cop || '-',
                    row.total_price_bs || '-',
                    row.cliente,
                    `<div style="display:flex;gap:6px;justify-content:center;">
                      <button class="btn-detalles" onclick='verDetalles("${row.id}")' title="${row.productosTexto}">
                        <ion-icon name="eye-outline"></ion-icon>
                      </button>
                      <button class="btn-detalles" onclick='imprimirTicketVenta("${row.id}", "default")' title="Imprimir ticket 58 mm">
                        <ion-icon name="print-outline"></ion-icon>
                      </button>
                      <button class="btn-eliminar" onclick='confirmarEliminar("${row.id}")' title="Eliminar venta">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>`,
                ]);
            });
            table.draw();

            const grupoCount = document.getElementById("grupo-count");
            if (grupoCount) {
                const totalGrupo = Array.isArray(data.tabla) ? data.tabla.length : 0;
                grupoCount.textContent = `(${totalGrupo} venta${totalGrupo === 1 ? "" : "s"})`;
            }
        })
        .catch((error) => {
            console.error("Error en la solicitud:", error);
            alert("Hubo un problema con la conexión al servidor.");
        });
}
cargarInfo();


function ensureVentaDetalleModal() {
    if (document.getElementById("modalVentaDetalle")) return;

    const style = document.createElement("style");
    style.id = "venta-detalle-modal-style";
    style.textContent = `
        #modalVentaDetalle .modal-content {
            background: var(--dash-card, #181b22);
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 14px;
            color: var(--dash-text, #e8edf2);
            overflow: hidden;
        }
        #modalVentaDetalle .modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--dash-border, #2e353e);
        }
        #modalVentaDetalle .modal-title {
            margin: 0;
            color: var(--dash-text, #e8edf2);
            font-size: 16px;
            font-weight: 700;
        }
        #modalVentaDetalle .venta-detalle-sub {
            display: block;
            margin-top: 2px;
            color: var(--dash-text-muted, #8892a0);
            font-size: 11px;
        }
        #modalVentaDetalle .close {
            margin: 0;
            padding: 0;
            color: var(--dash-text, #e8edf2);
            font-size: 24px;
            line-height: 1;
            opacity: .75;
            text-shadow: none;
        }
        #modalVentaDetalle .close:hover {
            color: var(--dash-mint, #2dd4a0);
            opacity: 1;
        }
        #modalVentaDetalle .modal-body {
            display: block;
            flex: 1 1 auto;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 18px 20px;
            background: var(--dash-card, #181b22);
        }
        #modalVentaDetalle .modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 14px 20px;
            border-top: 1px solid var(--dash-border, #2e353e);
        }
        .venta-detalle-footer-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .venta-detalle-select {
            background: var(--dash-bg, #1a1e24);
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 8px;
            color: var(--dash-text, #e8edf2);
            padding: 7px 10px;
            font-size: 12px;
        }
        .venta-detalle-btn-print {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            border: 0;
            border-radius: 8px;
            color: #fff;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .venta-detalle-btn-print:hover {
            color: #fff;
            transform: translateY(-1px);
        }
        .venta-detalle-btn-danger {
            background: rgba(239, 90, 111, .12);
            border: 1px solid rgba(239, 90, 111, .3);
            border-radius: 8px;
            color: #ef5a6f;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .venta-detalle-btn-danger:hover {
            background: rgba(239, 90, 111, .22);
            color: #ef5a6f;
        }
        .venta-detalle-btn-secondary {
            background: rgba(255, 255, 255, .06);
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 8px;
            color: var(--dash-text-muted, #8892a0);
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 500;
        }
        .venta-detalle-btn-secondary:hover {
            border-color: var(--dash-mint, #2dd4a0);
            color: var(--dash-mint, #2dd4a0);
        }
        .venta-detalle-loading {
            padding: 46px 10px;
            color: var(--dash-text-muted, #8892a0);
            font-size: 13px;
            text-align: center;
        }
        .venta-detalle-loading::before {
            content: "";
            display: block;
            width: 30px;
            height: 30px;
            margin: 0 auto 12px;
            border: 2px solid rgba(45, 212, 160, .2);
            border-top-color: var(--dash-mint, #2dd4a0);
            border-radius: 50%;
            animation: ventaDetalleSpin .8s linear infinite;
        }
        @keyframes ventaDetalleSpin {
            to { transform: rotate(360deg); }
        }
        .venta-detalle-content {
            overflow-x: auto;
        }
        .venta-detalle-content .orden-detalle {
            margin-bottom: 10px;
            padding: 12px 14px;
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 10px;
            background: rgba(255, 255, 255, .025);
        }
        .venta-detalle-content .orden-detalle p {
            margin: 0 0 4px;
            color: var(--dash-text, #e8edf2);
            font-size: 13px;
        }
        .venta-detalle-content .orden-detalle p:last-child {
            margin-bottom: 0;
        }
        .venta-detalle-content .dash-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .venta-detalle-content .dash-table th {
            padding: 9px 10px;
            border-bottom: 1px solid var(--dash-border, #2e353e);
            color: var(--dash-text-muted, #8892a0);
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: .4px;
            text-align: left;
            text-transform: uppercase;
        }
        .venta-detalle-content .dash-table td {
            padding: 9px 10px;
            border-bottom: 1px solid rgba(46, 53, 62, .5);
            color: var(--dash-text, #e8edf2);
        }
        .venta-detalle-content .dash-table tbody tr:last-child td {
            border-bottom: none;
        }
        .venta-detalle-content .dash-table tbody tr:hover {
            background: rgba(45, 212, 160, .03);
        }
        @media (max-width: 576px) {
            #modalVentaDetalle .modal-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }
            .venta-detalle-footer-right {
                flex-direction: column;
                align-items: stretch;
            }
            .venta-detalle-footer-right .venta-detalle-btn-print,
            .venta-detalle-footer-right .venta-detalle-btn-secondary,
            .venta-detalle-select {
                width: 100%;
            }
        }
    `;
    document.head.appendChild(style);

    const modal = document.createElement("div");
    modal.className = "modal fade";
    modal.id = "modalVentaDetalle";
    modal.tabIndex = -1;
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="ventaDetalleTitulo">Detalle de la venta</h5>
                        <small class="venta-detalle-sub" id="ventaDetalleSub">Información de la venta</small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ventaDetalleBody"></div>
                <div class="modal-footer">
                    <button type="button" class="venta-detalle-btn-danger" id="ventaDetalleEliminar">
                        <ion-icon name="trash-outline" style="vertical-align:middle;margin-right:4px;"></ion-icon>
                        Eliminar venta
                    </button>
                    <div class="venta-detalle-footer-right">
                        <select id="ventaDetalleMoneda" class="venta-detalle-select">
                            <option value="default">Moneda original</option>
                            <option value="USD">Dólares</option>
                            <option value="COP">Pesos</option>
                            <option value="BS">Bolívares</option>
                        </select>
                        <button type="button" class="venta-detalle-btn-print" id="ventaDetalleImprimir">
                            <ion-icon name="print-outline" style="vertical-align:middle;margin-right:4px;"></ion-icon>
                            Ticket 58 mm
                        </button>
                        <button type="button" class="venta-detalle-btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>`;
    document.body.appendChild(modal);

    document.getElementById("ventaDetalleImprimir").addEventListener("click", function() {
        const id = this.dataset.id;
        if (!id) return;
        imprimirTicketVenta(id, document.getElementById("ventaDetalleMoneda").value);
    });

    document.getElementById("ventaDetalleEliminar").addEventListener("click", function() {
        const id = this.dataset.id;
        if (!id) return;
        $("#modalVentaDetalle").modal("hide");
        if (typeof confirmarEliminar === "function") {
            confirmarEliminar(id);
        } else if (typeof eliminarVenta === "function" && window.confirm("¿Está seguro de eliminar esta venta?")) {
            eliminarVenta(id);
        }
    });

    $("#modalVentaDetalle").on("hidden.bs.modal", function() {
        document.getElementById("ventaDetalleBody").innerHTML = "";
    });
}

function imprimirTicketVenta(id, moneda = "default") {
    if (!id) return;
    const url = "ticket_venta_58.php?id=" + encodeURIComponent(id) +
        "&moneda=" + encodeURIComponent(moneda || "default");
    window.open(url, "_blank", "width=440,height=680,menubar=no,toolbar=no,scrollbars=yes");
}

function verDetalles(id) {
    ensureVentaDetalleModal();

    const body = document.getElementById("ventaDetalleBody");
    const titulo = document.getElementById("ventaDetalleTitulo");
    const sub = document.getElementById("ventaDetalleSub");
    const btnImprimir = document.getElementById("ventaDetalleImprimir");
    const btnEliminar = document.getElementById("ventaDetalleEliminar");

    titulo.textContent = "Detalle de la venta #" + id;
    sub.textContent = "Productos, pagos y totales de la venta";
    btnImprimir.dataset.id = id;
    btnEliminar.dataset.id = id;
    document.getElementById("ventaDetalleMoneda").value = "default";

    body.innerHTML = '<div class="venta-detalle-loading">Cargando detalle de la venta…</div>';
    $("#modalVentaDetalle").modal("show");

    fetch(`detallesVenta.php?id=${id}`)
        .then((response) => response.text())
        .then((html) => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, "text/html");
            const content = doc.querySelector(".x_content");

            if (!content) {
                throw new Error("No se pudo encontrar el contenido de los detalles.");
            }

            const title = content.querySelector(".x_title");
            if (title) title.remove();

            body.innerHTML = `<div class="venta-detalle-content">${content.innerHTML}</div>`;
        })
        .catch((error) => {
            console.error("Error:", error);
            body.innerHTML = '<div class="venta-detalle-loading">No se pudo cargar el detalle de la venta.</div>';
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