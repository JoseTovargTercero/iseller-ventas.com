<?php
// redirecciona a login.php
header("Location: login.php");
exit;
?>

<!DOCTYPE html>
<!--
	Moon by GetTemplates.co
	URL: https://gettemplates.co
-->
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Iseller App</title>
    <meta name="description" content="Core HTML Project">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel='icon' href='publico/production/images/favicon.ico' type='image/ico' />

    <!-- External CSS -->
    <link rel="stylesheet" href="web/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="web/vendor/select2/select2.min.css">
    <link rel="stylesheet" href="web/vendor/owlcarousel/owl.carousel.min.css">
    <link rel="stylesheet" href="web/vendor/lightcase/lightcase.css">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400|Work+Sans:300,400,700" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="web/css/style.min.css">
    <link rel="stylesheet" href="https://cdn.linearicons.com/free/1.0.0/icon-font.min.css">
    <link href="https://file.myfontastic.com/7vRKgqrN3iFEnLHuqYhYuL/icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Modernizr JS for IE8 support of HTML5 elements and media queries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</head>

<body data-spy="scroll" data-target="#navbar-nav-header" class="static-layout ">
    <div class="boxed-page animate__animated animate__fadeIn">
        <nav id="gtco-header-navbar" class="navbar navbar-expand-lg py-4">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="/">
                    <img src="web/img/logo.png" alt="Logo iseller" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-nav-header"
                    aria-controls="navbar-nav-header" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="lnr lnr-menu"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbar-nav-header">
                    <ulH class="navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#funcionalidades">Funcionalidades</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#tasas">Tasas de cambio</a>
                        </li>
                        <li class="nav-item" style="display: none;">
                            <a class="nav-link" href="#capturas">Capturas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#pricing">Planes</a>
                        </li>
                        <li class="nav-item" style="display: none;">
                            <a class="nav-link" href="registro.html">Contacto</a>
                        </li>
                        <li class="nav-item ml-3">
                            <a class="btn btn-sm btn-outline-success" href="login.php">Iniciar sesión</a>
                        </li>
                    </ulH>
                </div>
            </div>

        </nav>
        <div class="jumbotron d-flex align-items-center" style="background-image: url(img/hero-2.png)">
            <div class="container text-center mt-5">
                <h1 class="display-2 mb-4">Control total de tu inventario en tiempo real!</h1>
                <p>
                    Gestiona múltiples sucursales, <b>tasas de cambio personalizadas</b>, usuarios ilimitados <br> y
                    obtén seguimiento detallado de ventas y ganancias, todo desde un solo panel.
                </p>
                <a href="registro.php" class="btn btn-success mt-3">Comenzar ahora, gratis!</a>
            </div>
        </div>
        <section id="funcionalidades" class="bg-white">
            <div class="container">
                <div class="section-content">
                    <div class="title-wrap">
                        <h2 class="section-title">La forma más simple y potente <br>
                            de gestionar tu <b>inventario y ventas</b></h2>
                        <p class="section-sub-title">Optimiza tus operaciones con control multisucursal, tasas de cambio
                            personalizadas y <br> reportes de ganancias en tiempo real. Todo desde una sola plataforma.
                        </p>
                    </div>
                    <div class="row text-center">


                        <div class="col-md-4 col-sm-6">
                            <img class="rounded-circle" src="web/icons/animat-checkmark.gif" alt="Gestión Multidivisa"
                                width="140" height="140">
                            <h5 class="mb-4">Gestión Multisucursal</h5>
                            <p>Administra tu inventario, productos y listas de precios en múltiples sucursales. </p>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <img class="rounded-circle" src="web/icons/animat-customize.gif" alt="Configuración de tasas"
                                width="140" height="140">
                            <h5 class="mb-4">Tasas de Cambio Personalizables</h5>
                            <p>Configura tus tasas de cambio según tus necesidades y asigna productos específicos a cada
                                una. Así, podrás adaptar tu estrategia de precios ante los cambios del mercado.</p>
                        </div>



                        <!-- /.col-md-4 col-sm-6  -->
                        <div class="col-md-4 col-sm-6 ">
                            <img class="rounded-circle" src="web/icons/animat-responsive.gif"
                                alt="Generic placeholder image" width="140" height="140">
                            <h5 class="mb-4">Ventas y Ganancias desde cualquier dispositivo</h5>
                            <p>Visualiza datos de ventas al instante y obtén reportes de rentabilidad por producto,
                                categoría o sucursal. </p> b
                        </div>
                        <!-- /.col-md-4 col-sm-6  -->
                    </div>
                    <!-- /.row -->
                </div>
            </div>
        </section> <!-- Counter Section -->
        <section id="gtco-counter" class="overlay bg-fixed">
            <div class="container">
                <div class="section-content">
                    <div class="row">
                        <!-- Counter Item -->
                        <div class="col-md-3 col-sm-6 counter-item">
                            <i class="lnr lnr-users"></i>
                            <span class="number" data-from="0" data-to="34" data-refresh-interval="100">14</span>
                            <h4>Sucursales activas</h4>
                        </div>
                        <!-- End of Counter Item -->
                        <!-- Counter Item -->
                        <div class="col-md-3 col-sm-6 counter-item">
                            <i class="lnr lnr-briefcase"></i>
                            <span class="number" data-from="0" data-to="23418" data-refresh-interval="100">23418</span>
                            <h4>Ventas este mes</h4>
                        </div>
                        <!-- End of Counter Item -->
                        <!-- Counter Item -->
                        <div class="col-md-3 col-sm-6 counter-item">
                            <i class="lnr lnr-heart"></i>
                            <span class="number" data-from="0" data-to="38" data-refresh-interval="100">44168</span>
                            <h4>Usuarios registrados</h4>
                        </div>
                        <!-- End of Counter Item -->
                        <!-- Counter Item -->
                        <div class="col-md-3 col-sm-6 counter-item">
                            <i class="lnr lnr-rocket"></i>
                            <span class="number" data-from="0" data-to="29" data-refresh-interval="100">29</span>
                            <h4>Transacciones</h4>
                        </div>
                        <!-- End of Counter Item -->
                    </div>
                </div>
            </div>
        </section>







        <!-- End of Counter Section --> <!-- Features Section-->
        <section class="bg-white">
            <div class="container">
                <div class="section-content">
                    <!-- Section Title -->
                    <div class="title-wrap">
                        <h2 class="section-title">
                            Todas las herramientas que necesitas <br>
                            para controlar tu negocio como nunca
                        </h2>
                        <p class="section-sub-title">Gestiona tu inventario, controla precios en múltiples divisas y
                            accede a reportes en tiempo real. <br> Todo lo que buscas, en una sola plataforma
                            inteligente.</p>
                    </div>
                    <!-- End of Section Title -->
                    <div class="row">
                        <!-- Features Holder-->
                        <div class="col-md-12 features-holder">
                            <div class="row">
                                <!-- Features Item -->
                                <div class="col-md-4 col-sm-6 feature-item item mb-3 mb-3 text-center">
                                    <div class="my-4">
                                        <i class="lnr lnr-cog fs-40"></i>
                                    </div>
                                    <h4>Inventario</h4>
                                    <p>Rastrea stock de productos desde múltiples almacenes.</p>
                                </div>
                                <!-- End of Feature Item -->
                                <!-- Features Item -->
                                <div class="col-md-4 col-sm-6 feature-item item mb-3 text-center">
                                    <div class="my-4">
                                        <i class="lnr lnr-frame-contract fs-40"></i>
                                    </div>
                                    <h4>Precios y Divisas</h4>
                                    <p>Define diferentes tasas por sucursal, sin complicaciones.</p>
                                </div>
                                <!-- End of Feature Item -->
                                <!-- Features Item -->
                                <div class="col-md-4 col-sm-6 feature-item item mb-3 text-center">
                                    <div class="my-4">
                                        <i class="lnr lnr-bubble fs-40"></i>
                                    </div>
                                    <h4>Reportes Avanzados</h4>
                                    <p>Analiza ganancias, tendencias de ventas y rendimiento por tienda.</p>
                                </div>
                                <!-- End of Feature Item -->
                                <!-- Features Item -->
                                <div class="col-md-4 col-sm-6 feature-item item mb-3 text-center">
                                    <div class="my-4">
                                        <i class="lnr lnr-magic-wand fs-40"></i>
                                    </div>
                                    <h4>Integración POS</h4>
                                    <p>Terminal de punto de venta optimizada, rápida y fácil de usar.</p>
                                </div>
                                <!-- End of Feature Item -->
                                <!-- Features Item -->
                                <div class="col-md-4 col-sm-6 feature-item item mb-3 text-center">
                                    <div class="my-4">
                                        <i class="lnr lnr-clock fs-40"></i>
                                    </div>
                                    <h4>Roles y permisos</h4>
                                    <p>Administra el acceso de cada usuario según su rol.</p>
                                </div>
                                <!-- End of Feature Item -->
                                <!-- Features Item -->
                                <div class="col-md-4 col-sm-6 feature-item item mb-3 text-center">
                                    <div class="my-4">
                                        <i class="lnr lnr-thumbs-up fs-40"></i>
                                    </div>
                                    <h4>Historial de transacciones</h4>
                                    <p>Consulta ventas, devoluciones y movimientos por empleado o caja.</p>
                                </div>
                                <!-- End of Feature Item -->
                            </div>
                        </div>
                        <!-- End of Features Holder-->
                    </div>
                </div>
            </div>
        </section>
        <!-- End of Features Section-->
        <section id="tasas" class="featurettes bg-white" style="display: none;">

            <div class="container">
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <!-- Section Title -->
                            <div class="title-wrap">
                                <h2 class="section-title">
                                    <b>Una plataforma</b> de ventas e inventario,<br> diseñada para crecer con tu
                                    negocio
                                </h2>
                                <p class="section-sub-title">
                                    Gestiona productos, sucursales y ventas desde un solo lugar. Optimiza
                                    procesos,<br>controla tu stock y accede a reportes en tiempo real sin
                                    complicaciones.
                                </p>
                            </div>
                            <!-- End of Section Title -->

                            <div class="featurettes-wrap text-left mb-4">
                                <div class="row featurettes-item">
                                    <div class="col-md-4 offset-md-2 col-sm-6">
                                        <div class="my-5">
                                            <span class="lnr lnr-database fs-40 color-primary"></span>
                                        </div>
                                        <h4 class="mb-4">Inventario centralizado y actualizado</h4>
                                        <p>Visualiza el stock en todas tus sucursales en tiempo real. Evita quiebres de
                                            stock y controla entradas y salidas con precisión.</p>
                                    </div>
                                    <div class="col-md-4 offset-md-right-2 col-sm-6">
                                        <img class="my-5" src="web/img/app-profile-mockup.png" alt="Inventario Mockup">
                                    </div>
                                </div>
                            </div>

                            <div class="featurettes-wrap text-left">
                                <div class="row featurettes-item">
                                    <div class="col-md-4 offset-md-2 col-sm-6">
                                        <img class="my-4" src="web/img/app-chat-mockup.png" alt="Ventas Mockup">
                                    </div>
                                    <div class="col-md-4 offset-md-right-2 col-sm-6 mb-5">
                                        <div class="my-4">
                                            <span class="lnr lnr-chart-bars fs-40 color-primary"></span>
                                        </div>
                                        <h4 class="mb-4">Análisis de ventas y ganancias</h4>
                                        <p>Obtén informes detallados por producto, categoría, empleado o sucursal. Toma
                                            decisiones basadas en datos.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="featurettes-wrap text-left">
                                <div class="row featurettes-item">
                                    <div class="col-md-4 offset-md-2 col-sm-6 offset-sm-0">
                                        <h4 class="mb-4">¿Listo para llevar el control total de tu negocio?</h4>
                                        <p>Comienza a usar nuestro sistema hoy y transforma la forma en la que gestionas
                                            ventas, inventario y clientes.</p>
                                    </div>
                                    <div class="col-md-4 offset-md-right-2 col-sm-6 text-center">
                                        <a href="#0"><img class="btn-img my-4" src="web/img/appstore-btn.png"
                                                alt="App Store"></a>
                                        <a href="#0"><img class="btn-img" src="web/img/playstore-btn.png"
                                                alt="Play Store"></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!--/ .row -->
                </div>
            </div><!--/ .container -->

        </section>
        <!-- End of Blog Section --> <!-- Portfolio Section -->
        <section id="capturas" class="bg-white" style="display: none;">
            <div class="container">
                <div class="section-content">
                    <!-- Section Title -->
                    <div class="title-wrap">
                        <h2 class="section-title">Our <b>Awesome</b> Works</h2>
                        <p class="section-sub-title">Praesent commodo cursus magna, vel scelerisque nisl consectetur et.
                            <br> pharetra augue. Donec id elit non mi.
                        </p>
                    </div>
                    <!-- End of Section Title -->
                    <div class="row">
                        <!-- Portfolio Holder -->
                        <div class="col-md-12 portfolio-holder">
                            <!-- Btn Filter -->
                            <div class="filter-button-group btn-filter d-flex justify-content-center">
                                <a tabindex="0" class="is-checked" data-filter="*">Show All</a>
                                <a tabindex="0" data-filter=".minimalism">Minimalism</a>
                                <a tabindex="0" data-filter=".vintage">Vintage</a>
                                <a tabindex="0" data-filter=".creative">Creative</a>
                            </div>
                            <!-- End of Btn Filter -->
                            <!-- Portfolio Content -->
                            <div class="grid-portfolio">
                                <div class="grid-sizer"></div>
                                <div class="gutter-sizer"></div>
                                <!-- Portfolio Item -->
                                <div class="grid-item minimalism">
                                    <div class="grid-item-wrapper">
                                        <img src="web/img/photo-1.jpg" alt="portfolio-img" class="portfolio-item">
                                        <div class="grid-info">
                                            <div class="grid-link d-flex justify-content-center">
                                                <a class="img-pop" data-rel="lightcase" href="web/img/photo-1.jpg"
                                                    title="Photo-1">
                                                    <span class="lnr lnr-move"></span>
                                                </a>
                                                <a class="ext-link" href="https://unsplash.com/" target="_blank">
                                                    <span class="lnr lnr-link"></span>
                                                </a>
                                            </div>
                                            <div class="grid-title">
                                                <h4>Camera</h4>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- End of Portfolio Item -->
                                <!-- Portfolio Item -->
                                <div class="grid-item vintage">
                                    <div class="grid-item-wrapper">
                                        <img src="web/img/photo-6.jpg" alt="portfolio-img" class="portfolio-item">
                                        <div class="grid-info">
                                            <div class="grid-link d-flex justify-content-center">
                                                <a class="img-pop" data-rel="lightcase" href="web/img/photo-6.jpg"
                                                    title="Ship">
                                                    <span class="lnr lnr-move"></span>
                                                </a>
                                                <a class="ext-link" href="https://unsplash.com/" target="_blank">
                                                    <span class="lnr lnr-link"></span>
                                                </a>
                                            </div>
                                            <div class="grid-title">
                                                <h4>Flower</h4>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- End of Portfolio Item -->
                                <!-- Portfolio Item -->
                                <div class="grid-item creative grid-item-height">
                                    <div class="grid-item-wrapper">
                                        <img src="web/img/photo-2.jpg" alt="portfolio-img" class="portfolio-item">
                                        <div class="grid-info">
                                            <div class="grid-link d-flex justify-content-center">
                                                <a class="img-pop" data-rel="lightcase" href="web/img/photo-2.jpg"
                                                    title="Tracy Portrait">
                                                    <span class="lnr lnr-move"></span>
                                                </a>
                                                <a class="ext-link" href="https://unsplash.com/" target="_blank">
                                                    <span class="lnr lnr-link"></span>
                                                </a>
                                            </div>
                                            <div class="grid-title">
                                                <h4>Breakfast</h4>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- End of Portfolio Item -->
                                <!-- Portfolio Item -->
                                <div class="grid-item creative">
                                    <div class="grid-item-wrapper">
                                        <img src="web/img/photo-7.jpg" alt="portfolio-img" class="portfolio-item">
                                        <div class="grid-info">
                                            <div class="grid-link d-flex justify-content-center">
                                                <a class="img-pop" data-rel="lightcase" href="web/img/photo-7.jpg"
                                                    title="Guitar">
                                                    <span class="lnr lnr-move"></span>
                                                </a>
                                                <a class="ext-link" href="https://unsplash.com/" target="_blank">
                                                    <span class="lnr lnr-link"></span>
                                                </a>
                                            </div>
                                            <div class="grid-title">
                                                <h4>Chair</h4>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- End of Portfolio Item -->
                                <!-- Portfolio Item -->
                                <!-- <div class="grid-item minimalism">
                            <div class="grid-item-wrapper">
                                <img src="img/photo-3.jpg" alt="portfolio-img" class="portfolio-item">
                                <div class="grid-info">
                                    <div class="grid-link d-flex justify-content-center">
                                        <a class="img-pop" data-rel="lightcase" href="img/photo-3.jpg" title="Clock">
                                            <span class="lnr lnr-move"></span>
                                        </a>
                                        <a class="ext-link" href="https://unsplash.com/" target="_blank">
                                            <span class="lnr lnr-link"></span>
                                        </a>
                                    </div>
                                    <div class="grid-title">
                                        <h4>Clock</h4>
                                    </div>
                                </div>

                            </div>
                        </div> -->
                                <!-- End of Portfolio Item -->
                                <!-- Portfolio Item -->
                                <div class="grid-item vintage">
                                    <div class="grid-item-wrapper">
                                        <img src="web/img/photo-4.jpg" alt="portfolio-img" class="portfolio-item">
                                        <div class="grid-info">
                                            <div class="grid-link d-flex justify-content-center">
                                                <a class="img-pop" data-rel="lightcase" href="web/img/photo-4.jpg"
                                                    title="Bookself">
                                                    <span class="lnr lnr-move"></span>
                                                </a>
                                                <a class="ext-link" href="https://unsplash.com/" target="_blank">
                                                    <span class="lnr lnr-link"></span>
                                                </a>
                                            </div>
                                            <div class="grid-title">
                                                <h4>Hidden Book</h4>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- End of Portfolio Item -->
                                <!-- Portfolio Item -->
                                <div class="grid-item creative">
                                    <div class="grid-item-wrapper">
                                        <img src="web/img/photo-9.jpg" alt="portfolio-img" class="portfolio-item">
                                        <div class="grid-info">
                                            <div class="grid-link d-flex justify-content-center">
                                                <a class="img-pop" data-rel="lightcase" href="web/img/photo-9.jpg"
                                                    title="Guitar">
                                                    <span class="lnr lnr-move"></span>
                                                </a>
                                                <a class="ext-link" href="https://unsplash.com/" target="_blank">
                                                    <span class="lnr lnr-link"></span>
                                                </a>
                                            </div>
                                            <div class="grid-title">
                                                <h4>Red</h4>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- End of Portfolio Item -->
                            </div>
                            <!-- End of Portfolio Content -->
                        </div>
                        <!-- End of Portfolio Holder -->
                    </div>
                </div>
            </div>
        </section>
        <!-- End of Portfolio Section -->
        <section id="pricing" class="bg-grey">
            <div class="container">
                <div class="section-content">
                    <!-- Section Title -->
                    <div class="title-wrap">
                        <h2 class="section-title">Elige</h2>
                        <p class="section-sub-title">Disfruta del primer año completamente GRATIS. Sin restricciones, sin compromisos.</p>
                    </div>
                    <!-- End of Section Title -->
                    <div class="card-deck mb-3 text-center">

                        <div class="row m-auto">

                            <div class="col-lg-12 row">
                                <!-- Plan Mensual -->
                                <div class="price-box card mb-4 box-shadow col-lg-6">
                                    <div class="card-header p-4">
                                        <h6 class="mb-0 text-muted font-weight-bold">PLAN MENSUAL</h6>
                                        <h3 class="display-4 p-2 pb-0 mb-0 font-weight-bold text-success">Gratis</h3>
                                        <h6 class="text-success mt-0 pt-0" style=" margin-top: -8px !important;"> por un año</h6>

                                        <p class="mb-0">$9 / por mes</p>
                                    </div>
                                    <div class="card-body p-4">
                                        <ul class="price-box-list list-unstyled mt-3 mb-4">
                                            <li>Acceso multi-sucursal</li>
                                            <li>Manejo de múltiples tasas de cambio</li>
                                            <li>Usuarios ilimitados</li>
                                            <li>Seguimiento de ventas y ganancias</li>
                                            <li>Soporte por correo</li>
                                        </ul>
                                        <br>
                                        <a href="#" class="btn btn-block btn-outline-success btn-primary mt-4">Comenzar</a>
                                    </div>
                                </div>

                                <!-- Plan Mensual -->
                                <div class="price-box card mb-4 box-shadow col-lg-6">
                                    <div class="card-header p-4">
                                        <h6 class="mb-0 text-muted font-weight-bold">PLAN ANUAL</h6>
                                        <h3 class="display-4 p-2 pb-0 mb-0 font-weight-bold text-success">Gratis</h3>
                                        <h6 class="text-success mt-0 pt-0" style=" margin-top: -8px !important;"> por un año</h6>

                                        <p class="mb-0">$90 / por año</p>
                                    </div>
                                    <div class="card-body p-4">
                                        <ul class="price-box-list list-unstyled mt-3 mb-4">
                                            <li>Acceso multi-sucursal</li>
                                            <li>Manejo de múltiples tasas de cambio</li>
                                            <li>Usuarios ilimitados</li>
                                            <li>Seguimiento de ventas y ganancias</li>
                                            <li>Soporte por correo</li>
                                        </ul>
                                        <br>
                                        <a href="#" class="btn btn-block btn-outline-success btn-primary mt-4">Comenzar</a>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>


        <!-- End of Client Section -->
        <footer class="mastfoot mb-3 bg-white py-4 border-top">
            <div class="inner container">
                <div class="row">
                    <div class="col-md-6 d-flex align-items-center justify-content-md-start justify-content-center">
                        <p class="mb-0">&copy; 2019 Moon. All Right Reserved. Design by <a
                                href="https://gettemplates.co" target="_blank">GetTemplates.co</a>.</p>
                    </div>

                    <div class="col-md-6">
                        <nav class="nav nav-mastfoot justify-content-md-end justify-content-center">
                            <a class="nav-link" href="#">
                                <i class="icon-facebook"></i>
                            </a>
                            <a class="nav-link" href="#">
                                <i class="icon-twitter"></i>
                            </a>
                            <a class="nav-link" href="#">
                                <i class="icon-instagram"></i>
                            </a>
                            <a class="nav-link" href="#">
                                <i class="icon-linkedin"></i>
                            </a>
                            <a class="nav-link" href="#">
                                <i class="icon-youtube"></i>
                            </a>
                            <a class="nav-link" href="#">
                                <i class="icon-pinterest"></i>
                            </a>
                        </nav>
                    </div>

                </div>
            </div>
        </footer>
    </div>

    </div>
    <!-- External JS -->
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
    <script src="web/vendor/bootstrap/popper.min.js"></script>
    <script src="web/vendor/bootstrap/bootstrap.min.js"></script>
    <script src="web/vendor/select2/select2.min.js "></script>
    <script src="web/vendor/owlcarousel/owl.carousel.min.js"></script>
    <script src="web/vendor/isotope/isotope.min.js"></script>
    <script src="web/vendor/lightcase/lightcase.js"></script>
    <script src="web/vendor/waypoints/waypoint.min.js"></script>
    <script src="web/vendor/countTo/jquery.countTo.js"></script>

    <!-- Main JS -->
    <script src="js/app.min.js "></script>
    <script src="//localhost:35729/livereload.js"></script>
</body>

</html>