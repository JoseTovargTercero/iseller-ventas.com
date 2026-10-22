<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
<!-- Meta, title, CSS, favicons, etc. -->
<meta charset='utf-8'>
<meta http-equiv='X-UA-Compatible' content='IE=edge'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<link rel='icon' href='images/favicon.ico' type='image/ico' />

<!-- Bootstrap -->
<link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
<!-- Font Awesome -->
<link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
<!-- NProgress -->
<link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
<!-- iCheck -->


<!-- bootstrap-daterangepicker -->
<link href='../vendors/bootstrap-daterangepicker/daterangepicker.css' rel='stylesheet'>
<link href="js/jquerysctipttop.css" rel="stylesheet" type="text/css">
<!-- Custom Theme Style -->
<script src='js/jquery.min.js'></script>

<link href='../build/css/custom.min.dark-mode.css' rel='stylesheet'>
<link rel="stylesheet" href="../build/css/global-styles.css?v=<?php echo @filemtime(__DIR__ . '/../../build/css/global-styles.css'); ?>">

<link rel="stylesheet" href="../../iseller.es/css/animate.css">
<!-- Simple Line Icons -->
<link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">

<script src="../assets/sweetalert.min.js" defer></script>
<script src="../assets/sweetalert2.all.min.js"></script>

<link href='../vendors/boxicons/css/boxicons.min.css' rel='stylesheet'>
<link rel='stylesheet' href='../vendors/bootstrap-icons/bootstrap-icons.min.css'>

<script src="js/alerta.js" defer></script>
<script src="js/menu.js" defer></script>
<link rel="stylesheet" href="../vendors/fonts/fonts.css">
<script type="module" src="../vendors/ionicons/ionicons.esm.js"></script>
<script nomodule src="../vendors/ionicons/ionicons.js" defer></script>

<style>
    @view-transition {
        navigation: auto;
    }

    /* Grupo principal para transiciones */
    ::view-transition-group(*) {
        animation-duration: 0.6s;
        animation-timing-function: ease-in-out;
    }

    /* Animación solo para el contenedor .right_col */
    ::view-transition-old(.right_col) {
        animation: fade-slide-out-left 0.6s forwards;
    }

    ::view-transition-new(.right_col) {
        animation: fade-slide-in-right 0.6s forwards;
    }

    /* Keyframes */
    @keyframes fade-slide-out-left {
        0% {
            transform: translateX(0) scale(1);
            opacity: 1;
        }

        100% {
            transform: translateX(-30%) scale(0.95);
            opacity: 0;
        }
    }

    @keyframes fade-slide-in-right {
        0% {
            transform: translateX(30%) scale(1.05);
            opacity: 0;
        }

        100% {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }
</style>