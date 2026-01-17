<?php

$plan = '';
if (isset($_GET['plan'])) {
    $plan = $_GET['plan'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>iSeller - Registro</title>
  <meta name="description" content="Regístrate en iSeller">
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

  <!-- Modernizr JS for IE8 support of HTML5 elements and media queries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
  <style>
      .step-section {
          display: none;
      }
      .step-section.active {
          display: block;
          animation: fadeIn 0.5s;
      }
      @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
      }
  </style>

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
              Regístrate y aprovecha todas las herramientas del sistema <b class="text-success">gratis durante los primeros 3 meses</b>.
            </p>
          </div>
          <div class="row">
            <div class="col-md-8 offset-md-2 contact-form-holder mt-4">
              <form id="formRegistro" method="post">
                
                <!-- Step 1: Datos del Negocio -->
                <div id="step-1" class="step-section active">
                    <h4 class="mb-4">Paso 1: Datos del Negocio</h4>
                    <div class="row">
                        <div class="col-md-12 form-input mb-3">
                            <label for="tipo_negocio">Tipo de Negocio</label>
                            <select id="tipo_negocio" name="tipo_negocio" class="form-control" required>
                                <option value="" disabled selected>Selecciona un tipo</option>
                                <option value="minimarket">Minimarket</option>
                                <option value="farmacia">Farmacia</option>
                                <option value="restaurante">Restaurante</option>
                                <option value="ferreteria">Ferretería</option>
                                <option value="tienda_ropa">Tienda de Ropa</option>
                                <option value="tecnologia">Tecnología</option>
                                <option value="otro">Otro (Especificar)</option>
                            </select>
                        </div>
                        <div class="col-md-12 form-input mb-3" id="otro_tipo_container" style="display: none;">
                            <input type="text" class="form-control" id="otro_tipo" name="otro_tipo" placeholder="Especificar tipo de negocio">
                        </div>
                        <div class="col-md-12 form-input mb-3">
                            <label for="nombre_negocio">Nombre del Negocio</label>
                            <input type="text" class="form-control" id="nombre_negocio" name="nombre_negocio" placeholder="Ej: Mi Tienda C.A." required>
                        </div>
                        <div class="col-md-12 form-btn text-right">
                            <button type="button" class="btn btn-primary" onclick="nextStep(2)">Siguiente</button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Datos del Usuario -->
                <div id="step-2" class="step-section">
                    <h4 class="mb-4">Paso 2: Datos de Usuario</h4>
                    <div class="row">
                        <div class="col-md-12 form-input mb-3">
                            <input type="text" class="form-control" id="nombres" name="nombres" placeholder="Nombre completo" required>
                        </div>
                        <div class="col-md-12 form-input mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Correo electrónico" required>
                        </div>
                        <div class="col-md-12 form-input mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                        </div>
                        <div class="col-md-12 form-input mb-3">
                            <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" placeholder="Repetir contraseña" required>
                        </div>
                        <div class="col-md-12 form-btn text-center d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep(1)">Atrás</button>
                            <button class="btn btn-secondary btn-red" type="submit">Registrar</button>
                        </div>
                    </div>
                </div>

              </form>
              <div id="form-message-warning" class="text-danger mt-3"></div>
              <div id="form-message-success" class="text-success mt-3" style="display: none;">
                Registro exitoso. Redirigiendo...
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
            <p class="mb-0">&copy; 2026 iSeller</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
  </div>
  <!-- External JS -->
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
  <script src="web/vendor/bootstrap/popper.min.js"></script>
  <script src="web/vendor/bootstrap/bootstrap.min.js"></script>
  <script src="web/vendor/select2/select2.min.js "></script>
  <script src="web/vendor/owlcarousel/owl.carousel.min.js"></script>
  <script src="web/vendor/isotope/isotope.min.js"></script>
  <script src="web/vendor/lightcase/lightcase.js"></script>
  <script src="web/vendor/waypoints/waypoint.min.js"></script>
  <script src="web/vendor/countTo/jquery.countTo.js"></script>
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Main JS -->
  <script>
    const plan = "<?php echo $plan; ?>";
    // Manejo de Pasos
    function nextStep(step) {
        // Validaciones paso 1
        if (step === 2) {
            const tipo = document.getElementById('tipo_negocio').value;
            const nombreNegocio = document.getElementById('nombre_negocio').value.trim();
            const otroTipo = document.getElementById('otro_tipo').value.trim();
            
            if (!tipo) {
                Swal.fire('Error', 'Por favor selecciona un tipo de negocio.', 'warning');
                return;
            }
            if (tipo === 'otro' && !otroTipo) {
                Swal.fire('Error', 'Por favor especifica el tipo de negocio.', 'warning');
                return;
            }
            if (!nombreNegocio) {
                Swal.fire('Error', 'Por favor ingresa el nombre del negocio.', 'warning');
                return;
            }
        }

        document.querySelectorAll('.step-section').forEach(el => el.classList.remove('active'));
        document.getElementById(`step-${step}`).classList.add('active');
    }

    function prevStep(step) {
        document.querySelectorAll('.step-section').forEach(el => el.classList.remove('active'));
        document.getElementById(`step-${step}`).classList.add('active');
    }

    // Toggle tipo personalizado
    document.getElementById('tipo_negocio').addEventListener('change', function() {
        if (this.value === 'otro') {
            document.getElementById('otro_tipo_container').style.display = 'block';
        } else {
            document.getElementById('otro_tipo_container').style.display = 'none';
        }
    });

    // Envío del formulario
    document.getElementById('formRegistro').addEventListener('submit', function(e) {
      e.preventDefault();

      const warning = document.getElementById('form-message-warning');
      const success = document.getElementById('form-message-success');
      
      const password = document.getElementById('password').value;
      const confirmar = document.getElementById('confirmar_password').value;
      const email = document.getElementById('email').value.trim();

      warning.innerText = '';
      warning.style.display = 'none';

      // Validaciones finales
      if (password.length < 5) {
        Swal.fire('Error', 'La contraseña debe tener al menos 5 caracteres.', 'warning');
        return;
      }

      if (password !== confirmar) {
        Swal.fire('Error', 'Las contraseñas no coinciden.', 'warning');
        return;
      }

      // Preparar datos
      const formData = new FormData(this);
      formData.append('plan', plan);
      
      // Enviar datos al servidor
      fetch('configurar/register_process.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Registro exitoso',
                text: 'Inicia sesión para continuar...',
                timer: 1500,
                showConfirmButton: true
            }).then(() => {
              // then confirm redirect to login
              window.location.href = 'login.php';
            });

          } else {
            Swal.fire('Error', data.message || 'Error en el registro.', 'error');
          }
        })
        .catch(err => {
          console.error(err);
          Swal.fire('Error', 'Error al conectar con el servidor.', 'error');
        });
    });
  </script>
  
  <!-- Formulario oculto para login.js -->
  <form name="data_form" style="display: none;">
      <input type="text" id="login" name="login">
      <input type="password" id="hidden_password" name="password"> 
      <!-- Usamos hidden_password para no conflicto de ID con el form de registro -->
  </form>

</body>

</html>