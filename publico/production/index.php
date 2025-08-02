<?php
require_once('includes/requires.php');



/////////////////////////// CONTADOR //////////////////////////////


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {

    $topnav = topnav();

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
    $tipo_u =  $_SESSION['nivel'];



    $query = "SELECT * FROM `sucursales` WHERE bss_id = ?";

    if ($tipo_u == 2) {
        $id_sucursal = $_SESSION['sucursal'];

        // Solo para los usuarios tipo 2
        $query .= " AND id='$id_sucursal'";
    }

    $query .= "  ORDER BY principal DESC";

    $stmt = mysqli_prepare($conexion, $query);
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sucursales = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sucursales[] = $row;
        }
    }
    $stmt->close();




    $stmt = mysqli_prepare($conexion, "SELECT id, nombre, id_sucursal FROM `usuarios` WHERE bss_id = ?");
    $stmt->bind_param('s', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    $stmt->close();




?>

    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Inicio </title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>


    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>

                <?php echo $menu ?>

                <style>
                    .h3ini {
                        font-size: 16px;
                    }

                    .count {
                        font-size: 32px !important;
                    }

                    .gastos {
                        font-size: 12px;
                        position: absolute;
                        margin-top: 35px;
                        color: #ff8989;
                        font-weight: 900;
                    }

                    .circle-container {
                        width: 80px;
                        height: 80px;
                        display: inline-block;
                        margin: 10px;
                        position: relative;
                    }

                    .progress-ring {
                        transform: rotate(-90deg);
                    }

                    .ring-bg {
                        fill: none;
                        stroke: #eee;
                        stroke-width: 6;
                    }

                    .ring {
                        fill: none;
                        stroke: #61bdaff5;
                        stroke-width: 6;
                        stroke-linecap: round;
                        stroke-dasharray: 339.292;
                        stroke-dashoffset: 339.292;
                        transition: stroke-dashoffset 0.5s ease;
                    }


                    .circle-wrapper {
                        position: relative;
                        width: 100%;
                        height: 100%;
                    }

                    .icon-wrapper {
                        position: absolute;
                        inset: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }


                    .icons {
                        top: 50%;
                        left: 50%;
                        margin: auto;
                        width: 24px;
                        height: 24px;
                        opacity: 0.7;
                    }
                </style>
                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->

                <!-- page content -->
                <div class='right_col'>




                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h3ini">Inicio</h3>
                            <p style="margin-top: -10px;">Resumen y estadísticas</p>
                        </div>
                        <?php if ($_SESSION["nivel"] == 1): ?>
                            <div style="    align-self: anchor-center;">
                                <button type="button" style="height: min-content;" class="btn btn-success btn-sm" data-toggle="modal" data-target="#exampleModalCenter">
                                    Aplicar Filtro
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>




                    <div class='row'>


                        <div class="animated flipInY col-lg-4">
                            <div class="tile-stats p-2">
                                <div class="d-flex justify-content-between">
                                    <div class="div">
                                        <p class="fs-13">Ventas del día</p>
                                        <small class="text-muted ml-10">Ventas</small>
                                        <div class="count" id="venta_dia"></div>
                                        <p>
                                    </div>
                                    <div class="div">
                                        <div class="circle-container" id="progress1">
                                            <div class="circle-wrapper">
                                                <svg class="progress-ring" width="80" height="80">
                                                    <circle class="ring-bg" cx="40" cy="40" r="34" />
                                                    <circle class="ring" cx="40" cy="40" r="34" />
                                                </svg>
                                                <div class="icon-wrapper">
                                                    <img class="icons" src="https://cdn-icons-png.flaticon.com/512/493/493389.png" alt="icono">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <i class="text-success line icon-arrow-up "></i>
                                &nbsp;<span id="ganancias_dia"></span> <small>Margen</small>
                                &nbsp;
                                <i class="text-danger line icon-arrow-down"></i>
                                &nbsp;<span id=""></span> <small>Gastos</small>
                                </p>
                            </div>
                        </div>


                        <div class="animated flipInY col-lg-4">
                            <div class="tile-stats p-2">
                                <div class="d-flex justify-content-between">
                                    <div class="div">
                                        <p class="fs-13">Ventas semana</p>
                                        <small class="text-muted ml-10">Ventas de la semana</small>
                                        <div class="count " id="venta_semana"></div>
                                    </div>
                                    <div class="div">
                                        <div class="circle-container" id="progress2">
                                            <div class="circle-wrapper">
                                                <svg class="progress-ring" width="80" height="80">
                                                    <circle class="ring-bg" cx="40" cy="40" r="34" />
                                                    <circle class="ring" cx="40" cy="40" r="34" />
                                                </svg>
                                                <div class="icon-wrapper">
                                                    <img class="icons" src="https://cdn-icons-png.flaticon.com/512/493/493389.png" alt="icono">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p>
                                    <i class="text-success line icon-arrow-up "></i>
                                    &nbsp;<span id="ganancias_semana"></span> <small>Margen</small>
                                    &nbsp;
                                    <i class="text-danger line icon-arrow-down"></i>
                                    &nbsp;<span id="gastos_semana"></span> <small>Gastos</small>
                                </p>
                            </div>
                        </div>

                        <div class="animated flipInY col-lg-4">
                            <div class="tile-stats p-2">
                                <div class="d-flex justify-content-between">
                                    <div class="div">
                                        <p class="fs-13">Ventas Mes</p>
                                        <small class="text-muted ml-10">Ventas del mes</small>
                                        <div class="count" id="venta_mes"></div>
                                    </div>
                                    <div class="div">
                                        <div class="circle-container" id="progress3">
                                            <div class="circle-wrapper">
                                                <svg class="progress-ring" width="80" height="80">
                                                    <circle class="ring-bg" cx="40" cy="40" r="34" />
                                                    <circle class="ring" cx="40" cy="40" r="34" />
                                                </svg>
                                                <div class="icon-wrapper">
                                                    <img class="icons" src="https://cdn-icons-png.flaticon.com/512/493/493389.png" alt="icono">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p>
                                    <i class="text-success line icon-arrow-up "></i>
                                    &nbsp;<span id="ganancias_mes"></span> <small>Margen</small>
                                    &nbsp;
                                    <i class="text-danger line icon-arrow-down"></i>
                                    &nbsp;<span id="gastos_mes"></span> <small>Gastos</small>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if ($_SESSION["nivel"] == 1): ?>

                        <!-- Modal -->
                        <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLongTitle">Aplicar filtro</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">

                                        <div class="mb-3">
                                            <label for="sucursal" class="form-label">Sucursal</label>
                                            <select id="sucursal" class="me-2 form-control form-control-sm">
                                                <?php if (count($sucursales) > 1): ?>
                                                    <option value="todas">-- Seleccione --</option>
                                                <?php endif; ?>

                                                <?php foreach ($sucursales as $row): ?>
                                                    <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($row['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="usuario" class="form-label">Usuario</label>
                                            <select id="usuario" class="me-2 form-control form-control-sm">
                                                <option value="todos">-- Seleccione --</option>
                                            </select>
                                        </div>


                                    </div>
                                    <div class="modal-footer text-end">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>



                    <script>
                        document.getElementById('sucursal').addEventListener('change', function() {
                            const sucursalId = this.value;
                            const usuarioSelect = document.getElementById('usuario');
                            usuarioSelect.innerHTML = '<option value="todos">-- Seleccione --</option>'; // Limpiar opciones anteriores

                            if (sucursalId === 'todas') {
                                return;
                            }

                            // Filtrar usuarios por sucursal
                            const usuariosFiltrados = <?php echo json_encode($usuarios); ?>.filter(usuario => usuario.id_sucursal == sucursalId);

                            if (usuariosFiltrados.length > 0) {
                                usuariosFiltrados.forEach(usuario => {
                                    const option = document.createElement('option');
                                    option.value = usuario.id;
                                    option.textContent = usuario.nombre;
                                    usuarioSelect.appendChild(option);
                                });
                            } else {
                                usuarioSelect.innerHTML = '<option value="">No hay usuarios disponibles</option>';
                            }
                        });
                    </script>


                    <div class='row '>



                        <div class="col-lg-6 col-sm-6 col-lx-6">
                            <div class="x_panel tile">
                                <div class="x_title">
                                    <div class="align-items-center g-2 row">
                                        <div class="col">
                                            <h6 class="mb-0">Información de la sucursal</h6>
                                        </div>

                                    </div>
                                </div>

                                <div class="p-0 card-body" style="min-height: 400px;" id="informacion_interes">
                                </div>

                            </div>
                        </div>

                        <div class="col-lg-6 col-sm-6 col-lx-6">
                            <div class="x_panel tile">
                                <div class="x_title">
                                    <div class="align-items-center g-2 row">
                                        <div class="col">
                                            <h6 class="mb-0">Ventas de la semana</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-0 card-body" id="apex_chart_1">

                                </div>

                            </div>
                        </div>

                        <div class='col-lg-12'>
                            <div class='x_panel tile '>
                                <div class='x_title' style="border-bottom: none">
                                    <h5 style="font-weight: 400;">Ventas de las ultimas semanas</h5>
                                </div>
                                <div class='x_content' style="margin-top: -20px;">
                                    <div id="chartdiv"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- /footer content -->
            </div>
        </div>

        <script src="../vendors/amcharts5/index.js"></script>
        <script src="../vendors/amcharts5/xy.js"></script>
        <script src="../vendors/amcharts5/themes/Animated.js"></script>
        <script src="../vendors/amcharts5/themes/Material.js"></script>
        <script src="../build/js/global-loader.js"></script>

        <script>
            // Progreso superior
            function setProgress(containerId, percent) {
                const container = document.getElementById(containerId);
                const circle = container.querySelector('.ring');
                const radius = circle.r.baseVal.value;
                const circumference = 2 * Math.PI * radius;

                const offset = circumference - (percent / 100) * circumference;
                circle.style.strokeDashoffset = offset;
                circle.style.strokeDasharray = circumference;
            }

            // Indicadores
            const indicadores = [{
                    id: "creditosHoy",
                    titulo: "Créditos",
                    subtitulo: "Créditos otorgados hoy.",
                    prefijo: "$"
                },
                {
                    id: "despachadosHoy",
                    titulo: "Despachos",
                    subtitulo: "Productos despachados hoy.",
                    prefijo: ""
                }, {
                    id: "ventasMesDescontado",
                    titulo: "Descontado",
                    subtitulo: "Dinero descontado del mes.",
                    prefijo: "$"
                },
                {
                    id: "almacenProductos",
                    titulo: "Almacén",
                    subtitulo: "Productos en el almacén.",
                    prefijo: ""
                },
                {
                    id: "valorStockSinGanancia",
                    titulo: "Valor del stock",
                    subtitulo: "Valor base sin ganancias",
                    prefijo: "$"
                },
                {
                    id: "gananciasEsperadas",
                    titulo: "Ganancias",
                    subtitulo: "Ganancias esperadas",
                    prefijo: "$"
                }
            ];




            var options = {
                series: [{
                    name: "Ventas",
                    data: []
                }],
                chart: {
                    height: 435,
                    type: 'line',
                    background: 'transparent', // 👈 Fondo transparente
                    zoom: {
                        enabled: false
                    }
                },
                theme: {
                    mode: 'dark' // 👈 Aplica texto y ejes oscuros
                },
                tooltip: {
                    theme: 'dark'
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'straight'
                },
                markers: {
                    size: 5
                },
                grid: {
                    row: {
                        opacity: 0.5
                    },
                },
                xaxis: {
                    categories: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
                }
            };

            var apex_chart = new ApexCharts(document.querySelector("#apex_chart_1"), options);
            apex_chart.render();


            function calcularPorcentajeAvance(meta, avance) {
                if (avance == 0) return 0;
                if (meta <= 0) return 100;

                let porcentaje = (avance / meta) * 100;
                porcentaje = Math.min(Math.floor(porcentaje), 100); // Redondea hacia abajo y limita a 100

                return porcentaje;
            }




            function cargar_tabla(sucursal = null, usuario = null) {
                fetch('../../configurar/index_back.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            sucursal: sucursal,
                            usuario: usuario
                        })
                    })
                    /*.then(response => response.text()) // Primero obtenemos el texto plano
                                        .then(text => {
                                            console.log(text)
                                        })*/
                    .then(response => response.json())
                    .then(json => {
                        if (!json || json.length === 0) return;


                        // Construir datos para el gráfico
                        let data = [];
                        for (let semana in json.ventasSemanas) {
                            const valores = json.ventasSemanas[semana];
                            const obj = {
                                semana: semana,
                                ventas: valores[0] || 0,
                                ganancias: valores[1] || 0
                            };

                            if (valores[2] && valores[2] != 0) {
                                obj.gasto = valores[2];
                            }

                            data.push(obj);
                        }


                        // Cada vez que necesites actualizar el gráfico

                        actualizarDatos(data);

                        const ventas_semana = json.ventasSemana

                        apex_chart.updateSeries([{
                            name: 'Ventas',
                            data: [
                                recortarADosDecimales(ventas_semana.Lunes),
                                recortarADosDecimales(ventas_semana.Martes),
                                recortarADosDecimales(ventas_semana.Miercoles),
                                recortarADosDecimales(ventas_semana.Jueves),
                                recortarADosDecimales(ventas_semana.Viernes),
                                recortarADosDecimales(ventas_semana.Sabado),
                                recortarADosDecimales(ventas_semana.Domingo)
                            ]

                        }]);

                        document.getElementById('venta_dia').innerText = `$${json.totalVentasDiarias}`;
                        document.getElementById('venta_semana').innerText = `$${json.totalVentasSemana}`;
                        document.getElementById('venta_mes').innerText = `$${json.totalVentasMes}`;

                        let porcentaje_avance_dia = calcularPorcentajeAvance(json.VentasDiarias_anterior, json.totalVentasDiarias)
                        let porcentaje_avance_semana = calcularPorcentajeAvance(json.VentasSemana_anterior, json.totalVentasSemana)
                        let porcentaje_avance_mes = calcularPorcentajeAvance(json.VentasMes_anterior, json.totalVentasMes)


                        // Ejemplos de uso
                        setProgress('progress1', porcentaje_avance_dia);
                        setProgress('progress2', porcentaje_avance_semana);
                        setProgress('progress3', porcentaje_avance_mes);



                        document.getElementById('ganancias_dia').innerText = `$${json.gananciasDia}`;
                        document.getElementById('ganancias_semana').innerText = `$${json.gananciasSemana}`;
                        document.getElementById('ganancias_mes').innerText = `$${json.gananciasMes}`;
                        document.getElementById('gastos_semana').innerText = `$${json.gastosSemana}`;
                        document.getElementById('gastos_mes').innerText = `$${json.gastosMes}`;

                        renderInformacionInteres(json)

                    })
                    .catch(error => {
                        console.error("Error en la solicitud:", error);
                    });
            }

            const colorPairs = [{
                    bg: 'bg-primary-subtle',
                    text: 'text-primary'
                },
                {
                    bg: 'bg-secondary-subtle',
                    text: 'text-secondary'
                },
                {
                    bg: 'bg-success-subtle',
                    text: 'text-success'
                },
                {
                    bg: 'bg-danger-subtle',
                    text: 'text-danger'
                },
                {
                    bg: 'bg-warning-subtle',
                    text: 'text-warning'
                },
                {
                    bg: 'bg-info-subtle',
                    text: 'text-info'
                }
            ];

            function getRandomColorPair() {
                return colorPairs[Math.floor(Math.random() * colorPairs.length)];
            }

            // Render dinámico solo con los campos especificados
            function renderInformacionInteres(json) {
                const contenedor = document.getElementById('informacion_interes');
                contenedor.innerHTML = ''; // Limpiar contenido anterior

                indicadores.forEach(item => {
                    if (json.hasOwnProperty(item.id)) {
                        const valor = json[item.id];
                        const {
                            bg,
                            text
                        } = getRandomColorPair();
                        const bloque = `
                            <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                            <div class="py-1">
                                <div class="d-flex align-items-center">
                                <div class="avatar avatar-xl me-3">
                                    <div class="avatar-name rounded-circle ${bg}">
                                    <span class="fs-9 ${text}">${item.titulo.charAt(0)}</span>
                                    </div>
                                </div>
                                <h6 class="d-flex mb-0 align-items-center">
                                    <span>
                                    <p class="m-0">${item.titulo}</p>
                                    <small class='text-muted'>${item.subtitulo}</small>
                                    </span>
                                </h6>
                                </div>
                            </div>
                            <div class="p-2">
                                <div class="fs-15 fw-semibold">${item.prefijo}${valor}</div>
                            </div>
                            </div>
                        `;
                        contenedor.insertAdjacentHTML('beforeend', bloque);
                    }
                });
            }

            var chart; // Variable global

            function inicializarGrafico() {
                var options = {
                    chart: {
                        type: 'bar',
                        height: 400,
                        stacked: true,
                        background: 'transparent', // 👈 Fondo transparente
                        zoom: {
                            enabled: true
                        },
                        toolbar: {
                            show: true
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '50%',
                            endingShape: 'rounded'
                        }
                    },
                    theme: {
                        mode: 'dark' // 👈 Aplica texto y ejes oscuros
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return "$" + val;
                            }
                        },
                        theme: 'dark'

                    },
                    xaxis: {
                        categories: [], // Se carga dinámicamente
                        title: {
                            text: 'Semana'
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Valores'
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'center',
                        labels: {
                            colors: '#fff'
                        }
                    },
                    series: [] // Se actualiza dinámicamente
                };

                chart = new ApexCharts(document.querySelector("#chartdiv"), options);
                chart.render();
            }

            function actualizarDatos(data) {
                // Obtener las categorías (semana)
                let categorias = data.map(d => d.semana);

                // Inicializar estructura de series
                let seriesData = {
                    Ventas: [],
                    Ganancias: [],
                    Gastos: []
                };

                data.forEach(d => {
                    seriesData["Ventas"].push(Number(d.ventas) || 0);
                    seriesData["Ganancias"].push(Number(d.ganancias) || 0);
                    seriesData["Gastos"].push(Number(d.gasto) || 0);
                });

                let series = [{
                        name: "Ventas",
                        data: seriesData["Ventas"]
                    },
                    {
                        name: "Ganancias",
                        data: seriesData["Ganancias"]
                    },
                    {
                        name: "Gastos",
                        data: seriesData["Gastos"]
                    }
                ];

                // Actualiza los datos del gráfico
                chart.updateOptions({
                    xaxis: {
                        categories: categorias
                    }
                });

                chart.updateSeries(series);
            }

            // Llamada inicial
            inicializarGrafico();


            // document ready function

            //    $(document).ready(function() {
            cargar_tabla();
            //    });


            document.getElementById('sucursal').addEventListener('change', function() {
                cargar_tabla(this.value);
            })
            document.getElementById('usuario').addEventListener('change', function() {
                const sucursal = document.getElementById('sucursal').value;
                cargar_tabla(sucursal, this.value);
            });
        </script>



        <!-- jQuery -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../build/js/custom.js"></script>

    <?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
    ?>