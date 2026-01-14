<!DOCTYPE html>
<!--
	Moon by GetTemplates.co
	URL: https://gettemplates.co
-->
<html lang="es">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Inicio de sesion</title>
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
  <link rel="stylesheet" href="publico/build/css/loader.css">
  <link rel="stylesheet" href="https://cdn.linearicons.com/free/1.0.0/icon-font.min.css">
  <link href="https://file.myfontastic.com/7vRKgqrN3iFEnLHuqYhYuL/icons.css" rel="stylesheet">

  <!-- Modernizr JS for IE8 support of HTML5 elements and media queries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</head>

<!--
<div class="loader-container hide" id="loader">
  <svg class="spinner" width="65px" height="65px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg">
    <circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle>
  </svg>
</div>
-->


<style>
  body {
    margin: 0;
    padding: 0;
    background-color: black;
    overflow: hidden;
  }

  .snowflake {
    position: absolute;
    width: 4px;
    height: 4px;
    background-color: white;
  }
</style>

<script>
  function createSnowflake() {
    const snowflake = Object.assign(
      document.createElement('div'),
      {
        className: 'snowflake',
        style: `
        left: ${Math.random() * innerWidth}px;
        top: -5px;
        opacity: ${Math.random()};
        transform: scale(${Math.random() * 1.5 + 0.5});`
      }
    )

    document.body.appendChild(snowflake);

    let posY = -5;
    let speed = Math.random() * 2 + 1;
    let wobble = 0;

    function fall() {
      posY += speed;
      wobble += 0.02;
      snowflake.style.top = posY + 'px';
      snowflake.style.left =
        parseFloat(snowflake.style.left) +
        Math.sin(wobble) * 2 + 'px';

      posY < innerHeight
        ? requestAnimationFrame(fall)
        : snowflake.remove();
    }

    fall();
  }

  function generateSnow() {
    setInterval(createSnowflake, 100);
  }

  //generateSnow();
</script>


<body data-spy="scroll" data-target="#navbar-nav-header" class="static-layout">
  <div class="boxed-page animate__animated animate__fadeIn" style="max-width: fit-content;margin-left: auto;margin-right: auto;">
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
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="btn btn-sm btn-outline-success" href="registro.php">Registrate</a>


            </li>
          </ul>
        </div>
      </div>

    </nav>

    <section id="registro-form" class="bg-white ">
      <div class="container d-flex ">
        <div class="section-content m-auto" style="max-width: 450px; ">
          <div class="title-wrap mt-3">
            <h2 class="section-title">Inicio de sesión </h2>
            <p class="section-sub-title">
              Por favor ingresa tu correo electrónico y contraseña para ingresar a tu cuenta <a href="registro.php">aquí</a>.
            </p>
          </div>
          <div class="row">
            <div class="col-md-8 offset-md-2 contact-form-holder mt-4">
              <form name="data_form">
                <div class="row">
                  <div class="col-md-12 form-input mb-3">
                    <input type="text" class="form-control" id="login" name="login" placeholder="Correo eléctronico" required>
                  </div>
                  <div class="col-md-12 form-input mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                  </div>

                  <div class="col-md-12 form-btn text-center">
                    <button class="btn btn-block btn-success" type="submit">Verificar</button>
                  </div>
                  <p class="text-center w-100 mt-4">¿No tienes una cuenta? <a href="registro.php">Registrate</a> </p>
                </div>
              </form>
              <div id="form-message-warning" class="text-danger mt-3"></div>
              <div id="form-message-success" class="text-success mt-3" style="display: none;">
                Registro exitoso. Revisa tu correo.
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- End of Contact Form Section -->
    <footer class="mastfoot mb-3 bg-white py-4 border-top">
      <div class="inner container">
        <p class="mb-0">&copy; 2025 iSeller.</p>
      </div>
    </footer>



  </div>

  </div>
  <!-- External JS -->
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
  <script src="publico/build/js/login.js"></script>
  <script src="web/vendor/bootstrap/popper.min.js"></script>
  <script src="web/vendor/bootstrap/bootstrap.min.js"></script>
  <script src="web/vendor/select2/select2.min.js "></script>
  <script src="web/vendor/owlcarousel/owl.carousel.min.js"></script>
  <script src="web/vendor/isotope/isotope.min.js"></script>
  <script src="web/vendor/lightcase/lightcase.js"></script>
  <script src="web/vendor/waypoints/waypoint.min.js"></script>
  <script src="web/vendor/countTo/jquery.countTo.js"></script>
  <script src="publico/build/js/global-loader.js"></script>

  <!-- Main JS -->
  <script src="web/js/app.min.js "></script>

</body>

</html>