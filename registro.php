<!DOCTYPE html>
<!--
	Moon by GetTemplates.co
	URL: https://gettemplates.co
-->
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Moon - Multipurpose Bootstrap 4 Template by GetTemplates.co</title>
  <meta name="description" content="Core HTML Project">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

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

  <!-- Modernizr JS for IE8 support of HTML5 elements and media queries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>

</head>

<body data-spy="scroll" data-target="#navbar-nav-header" class="static-layout">
  <div class="boxed-page">
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
              <a class="nav-link" href="index.php">Regresar al inicio</a>
            </li>
          </ul>
        </div>
      </div>

    </nav>

    <section id="registro-form" class="bg-white">
      <div class="container">
        <div class="section-content">
          <div class="title-wrap">
            <h2 class="section-title">¡Empieza hoy, sin pagar nada!</h2>
            <p class="section-sub-title">
              Regístrate y aprovecha todas las herramientas del sistema <b class="text-success">gratis durante el primer año</b>.
            </p>
          </div>
          <div class="row">
            <div class="col-md-8 offset-md-2 contact-form-holder mt-4">
              <form id="formRegistro" method="post">
                <div class="row">
                  <div class="col-md-12 form-input mb-3">
                    <input type="text" class="form-control" id="nombres" name="nombres" disabled placeholder="Nombre completo" required>
                  </div>
                  <div class="col-md-12 form-input mb-3">
                    <input type="email" class="form-control" id="email" name="email" disabled placeholder="Correo electrónico" required>
                  </div>
                  <div class="col-md-12 form-input mb-3">
                    <input type="password" class="form-control" id="password" name="password" disabled placeholder="Contraseña" required>
                  </div>
                  <div class="col-md-12 form-input mb-3">
                    <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" disabled placeholder="Repetir contraseña" required>
                  </div>
                  <div class="col-md-12 form-btn text-center">
                    <button class="btn btn-block btn-secondary btn-red" disabled type="submit">Registrar</button>
                  </div>
                  <p class="text-center w-100 text-danger mt-3">
                    <strong>Importante:</strong> No disponible. El registro de nuevos usuarios está deshabilitado temporalmente. Si tienes alguna duda, por favor contacta a nuestro soporte técnico.
                  </p>
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
        <div class="row">
          <div class="col-md-6 d-flex align-items-center justify-content-md-start justify-content-center">
            <p class="mb-0">&copy; 2025 iSeller</p>
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
  <script>
    document.getElementById('formRegistro').addEventListener('submit', function(e) {
      e.preventDefault();

      return
      const nombres = document.getElementById('nombres').value.trim();
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      const confirmar = document.getElementById('confirmar_password').value;
      const warning = document.getElementById('form-message-warning');
      const success = document.getElementById('form-message-success');

      warning.innerHTML = '';
      success.style.display = 'none';

      // Validaciones básicas
      if (!nombres || !email || !password || !confirmar) {
        warning.innerText = 'Todos los campos son obligatorios.';
        return;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        warning.innerText = 'Correo electrónico inválido.';
        return;
      }

      if (password.length < 8) {
        warning.innerText = 'La contraseña debe tener al menos 8 caracteres.';
        return;
      }

      if (password !== confirmar) {
        warning.innerText = 'Las contraseñas no coinciden.';
        return;
      }

      // Enviar datos al servidor
      fetch('back/registro.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            nombres,
            email,
            password
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            success.style.display = 'block';
            document.getElementById('formRegistro').reset();
          } else {
            warning.innerText = data.message || 'Error en el registro.';
          }
        })
        .catch(() => {
          warning.innerText = 'Error al conectar con el servidor.';
        });
    });
  </script>

</body>

</html>