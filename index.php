<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='icon' href='publico/production/images/favicon.ico' type='image/ico' />
    <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->

    <link href='publico/vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
    <!-- Font Awesome -->
    <link href='publico/vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
    <!-- NProgress -->
    <link href='publico/build/css/custom.min.css' rel='stylesheet'>

    <!-- Icomoon Icon Fonts-->
    <link rel="stylesheet" href="iseller.es/css/icomoon.css">
    <!-- Simple Line Icons -->
    <link rel="stylesheet" href="iseller.es/css/simple-line-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Login</title>
</head>

<body style="background: #f4f4f4;">
    <div class="cuerpo">

        <header class="top-head">
            <div class="left">

                <img style="margin-right: 15px; opacity: 0.8;" s class="center " src='publico/production/images/logo1-inv-compact.png' height="50px">
            </div>


        </header>
        <section class="contenidoLogin" style="height: 100%; overflow-y: auto; color: #b1b1b1 !important; ">
            <div class="to-animate-2 i bounceIn animated">
                <div class="login" style="width: 360px;">
                    <h1 style="width: 100%; text-align: center; font-size: 22px; color: gray;">Inicio de Sesión</h1>
                    <hr>
                    <div style="display: grid; place-items: center;">
                        <img style="border: 1px solid #ffdee5; margin-bottom: 20px; border-radius:50%" alt="Imagen de Usuario" src="publico/production/images/img.png" height="120px"></img>
                    </div>
                    <div class="form-group" style="width: 80%; margin: auto">

                        <form name="data_form">

                            <label for="user">Usuario</label>
                            <input required style="color: #9c9c9c; padding-left: 30px" type='text' name='login' class="form-control" id='login'>
                            <span><i class="line icon-people iconLeft"></i></span>
                            <br>
                            <label for="pass">Contraseña</label>
                            <input required style="color: #9c9c9c; padding-left: 30px" type='password' name='password' class="form-control" id='password'>
                            <span><i class="line icon-lock iconLeft"></i></span>

                            <div style="display: grid; place-items: center; margin-top: 45px">
                                <Button type="submit" class="btn btn-success">Validar</Button>
                        </form>
                        <br>
                        <br>
                        <p style="color: #a5a9ac; text-align: center">
                            &copy; Iseller. Todos los derechos reservados
                        </p>

                    </div>

                </div>
            </div>
    </div>

    <script>
        // captura del submit de data_form

        document.querySelector("form[name='data_form']").addEventListener("submit", function(e) {
            e.preventDefault();

            const login = document.getElementById('login').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!login || !password) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor, completa todos los campos.'
                });
                return;
            }

            const formData = new FormData(this);

            fetch('login/guardar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(resp => resp.text()) // Obtener la respuesta como texto primero
                .then(text => {
                    console.log('Respuesta cruda del backend:', text); // Para debug
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de formato',
                            text: 'La respuesta del servidor no es un JSON válido.'
                        });
                        console.error('Error al parsear JSON:', error);
                        return;
                    }

                    if (data.status === true) {
                        window.location.href = 'publico/production/ventas.php';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de autenticación',
                            text: data.msg || 'Credenciales incorrectas.'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor.'
                    });
                    console.error('Error en la petición fetch:', err);
                });
        });
    </script>

    </script>
    </div> <!-- end of col -->
    </div>
</body>

</html>

<style>
    :root {
        --RedColor: #f82249;
    }

    .foot {
        position: fixed;
        bottom: 0;
        width: 100%;
        text-align: center;
    }

    /*---------------- variabeles -------------*/
    body {
        margin: 0;
    }

    .bg-red {
        background: #ff5e7b !important;
        border: 1px solid #ff5e7b !important;
        color: #fff;
    }

    .cuerpo {
        min-height: 100vh;
        display: grid;
        grid-template-rows: 0.1fr auto;

    }

    .cardPsuv {
        display: grid;
        grid-template-rows: auto 1fr;
    }

    .psuvLettering {
        display: grid;
        font-size: 80px;
        font-family: fantasy;
        place-items: center;
    }

    .noShadow {
        box-shadow: none !important;
        padding: 10px;
        color: #ff2655;
        border-top: none !important;
        background: #f4f4f4 !important;


        width: 100%;
        bottom: 0;
        justify-content: center;
        display: flex;
        z-index: 11;
    }

    .iconLeft {
        margin: -30px 0 0 10px;
        position: absolute;
        float: left;
        padding-top: 3px;
        color: #9c9c9c;
    }

    .left {
        float: left;
        margin-left: 10px;
    }

    .ulHome {
        height: 270px;
        overflow: hidden;
    }

    .status {
        margin-right: 15px;
        font-size: 18px;
    }

    .ulHome:hover {
        overflow: auto;
    }

    .ulHome::-webkit-scrollbar {
        background-color: lightgray;
        width: 6px;
        height: 6px;
    }

    .red {
        color: #ff5e7b;
    }

    .form-group {
        padding: 10px;
    }

    li {
        margin-bottom: 7px;
    }

    .ulHome::-webkit-scrollbar-thumb {
        background: var(--RedColor);
        height: 10px;
    }

    .login {
        background-color: white !important;
        box-shadow: 20px 20px 30px #d5d5d5 !important;
        height: 100%;
        padding: 15px;
        background-color: white;
        border-radius: 5px;
    }

    .contenido {

        height: 100%;
        padding: 15px 15px 70px 15px;
        font-family: sans-serif;
        color: #565656;
    }

    .contenidoLogin {
        display: grid;
        place-items: center;
        height: 100%;
        width: 100%;
        padding: 15px 15px 70px 15px;
        font-family: sans-serif;
        color: #565656;
    }

    .right {
        float: right;
        padding-top: 3px;
    }

    .top-head {
        padding: 10px 10px 7px 10px;

    }

    .nameUser {
        font-weight: lighter;
        margin-left: 5px;
        vertical-align: top;
        color: #afafaf;

    }

    .nav-inferior {
        position: fixed;
        width: 100%;
        bottom: 0;
        background-color: white;
        box-shadow: 0 2px 5px rgb(0 0 0);
        justify-content: center;
        border-top: 2px solid #abababd9;
        display: flex;
        z-index: 11;
    }

    .contenido {
        background-color: #f9f9f9;
    }

    .doors {
        text-align: center;
        padding: 13px 0 13px 0;
        margin: 0 10px 0 10px;
        cursor: pointer;
    }

    .door-active {
        border-top: 2px solid var(--RedColor);
    }

    .active {
        color: var(--RedColor) !important;
    }

    .icono {
        margin: 0 20px 0 20px;
        font-size: 1.3rem;
        color: dimgray;
    }

    .icono:hover {
        color: var(--RedColor);
    }

    .icono-salir {
        margin: 0 10px 0 10px;
        font-size: 22px;
        cursor: pointer;
        color: #d0d0d0;
    }

    .icono-salir:hover {
        color: var(--RedColor);
    }

    .card-box {
        overflow-y: hidden;
        max-height: 80vh;
        padding: 15px;
        background-color: white;
        box-shadow: 30px 30px 60px rgb(0 0 0 / 4%);
        border-radius: 5px;
    }

    .card-box:hover {
        overflow-y: auto;
    }

    .card-box::-webkit-scrollbar {
        background-color: lightgray;
        width: 6px;
        height: 6px;
    }

    .card-box::-webkit-scrollbar-thumb {
        background: var(--RedColor);
        height: 10px;
    }

    .cant-2do-Row {
        margin-top: 20px;
        overflow: hidden !important;
        height: 310px;
    }

    .cant-card {
        height: 130px;
        overflow: hidden !important;
        display: grid;
        place-items: center;
    }

    .iconHome {
        font-size: 100px;
        margin-right: -10px;
    }

    .cant {
        margin-bottom: -15px;
        font-size: 48px;
        margin-left: 10px;
    }

    .iconC {
        margin-bottom: -10px;
        float: right;
        font-size: 78px;
        margin-right: 15px;
    }

    .desc {
        margin-top: -10px;
        margin-left: 10px;

        #fh5co-header.navbar-fixed-top {
            position: fixed !important;
            background: rgba(255, 255, 255, 0.9);
            -webkit-box-shadow: 0 0 9px 0 rgba(0, 0, 0, 0.1);
            -moz-box-shadow: 0 0 9px 0 rgba(0, 0, 0, 0.1);
            -ms-box-shadow: 0 0 9px 0 rgba(0, 0, 0, 0.1);
            box-shadow: 0 0 9px 0 rgba(0, 0, 0, 0.1);
            margin-top: 0px;
            top: 0;
            z-index: 99;
        }

        #fh5co-header .navbar-brand {
            float: left;
            display: block;
            font-size: 30px;
            font-weight: 700;
            padding-left: 0;
            color: #fff;
        }

        .navbar-brand {
            background-color: white !important;
            color: #52d3aa !important;

        }

        .navbar-header {
            background-color: white !important
        }

        @media (min-width: 768px) .navbar-right {
            float: right !important;
            margin-right: -15px;
        }

        #fh5co-header #navbar li a {
            font-family: "Source Sans Pro", Arial, sans-serif;
            color: rgba(255, 255, 255, 0.5);
            position: relative;
            font-size: 19px;
            font-weight: 300;
        }

        .right {
            float: right !important;
            margin-top: 10px !important;
        }


        .enlaces {
            margin-left: 15px;
            font-family: "Source Sans Pro", Arial, sans-serif;
            color: #b1b1b1;
            position: relative;
            font-size: 19px;
            font-weight: 300;
        }

        .enlaces:hover {
            color: #52d3aa;
            text-decoration: none !important;

        }


        .container {
            width: 75%
        }



        .top-c {
            margin-top: 50px;
        }

        .center {
            text-align: center;
        }

        .btn.btn-primary {

            padding-left: 15px;
            padding-right: 15px;
            background: #52d3aa;
            color: #fff;
            border: none !important;
            border: 2px solid transparent !important;
        }

        .form-control {
            display: block;
            width: 100%;
            height: 42px;
            padding: 10px 20px;
            font-size: 14px;
            line-height: 1.42857;
            color: #555555;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
            border-radius: 4px;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
            border-bottom-left-radius: 4px;
            -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
            -webkit-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
            -o-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
            transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
        }

        .form-control {
            box-shadow: none;
            background: transparent;
            border: 2px solid rgba(0, 0, 0, 0.1);
            height: 48px;
            font-size: 18px;
            font-weight: 400;
        }

        .form-control:active,
        .form-control:focus {
            outline: none;
            box-shadow: none;
            border-color: #52d3aa;
        }



        .login_wrapper {
            right: 0px;
            margin-top: 5%;
            max-width: 90%;
            position: relative;
        }


        .nombre {
            font-weight: 800
        }

        .left-col {
            color: #b3b3b3
        }

        .text-left {
            color: #818181 !important;
            font-size: 22px;
            font-family: "Source Sans Pro", Arial, sans-serif;
            font-weight: 400;
            margin: 0 0 30px 0;
        }

        .text-left2 {
            font-size: 25px;
            margin-left: 10px;
            font-family: "Source Sans Pro", Arial, sans-serif;
            font-weight: 400;
            margin: 0 0 30px 0;
        }

        .text-left3 {
            color: #52d3aa;
            font-size: 22px;
            margin-left: 10px;
            font-family: "Source Sans Pro", Arial, sans-serif;
            font-weight: 400;
            margin: 0 0 30px 0;
        }

        .colre {
            color: #52d3aa !important;
        }

        @media screen and (max-width: 768px) .fh5co-nav-toggle {
            display: none !important;
        }
</style>

</html>