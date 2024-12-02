<?php
$id = $_GET['id'];
$accion = $_GET['accion'];
$qty = $_GET['qty'];
require_once("../../configurar/configuracion.php");


?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Loader #6 - Jelly Box</title>
  <link rel="stylesheet" href="styles.css">
  <script src='js/jquery.min.js'></script>

  <style>
    /* NOTE: The styles were added inline because Prefixfree needs access to your styles and they must be inlined if they are on local disk! */
    #loader {
      /* Uncomment this to make it run! */
      /*
     animation: loader 5s linear infinite; 
  */

      position: absolute;
      top: calc(50% - 20px);
      left: calc(50% - 20px);
    }

    @keyframes loader {
      0% {
        left: -100px
      }

      100% {
        left: 110%;
      }
    }

    #box {
      width: 50px;
      height: 50px;
      background: #fff;
      animation: animate .5s linear infinite;
      position: absolute;
      top: 0;
      left: 0;
      border-radius: 3px;
    }

    @keyframes animate {
      17% {
        border-bottom-right-radius: 3px;
      }

      25% {
        transform: translateY(9px) rotate(22.5deg);
      }

      50% {
        transform: translateY(18px) scale(1, .9) rotate(45deg);
        border-bottom-right-radius: 40px;
      }

      75% {
        transform: translateY(9px) rotate(67.5deg);
      }

      100% {
        transform: translateY(0) rotate(90deg);
      }
    }

    #shadow {
      width: 50px;
      height: 5px;
      background: #000;
      opacity: 0.1;
      position: absolute;
      top: 59px;
      left: 0;
      border-radius: 50%;
      animation: shadow .5s linear infinite;
    }

    @keyframes shadow {
      50% {
        transform: scale(1.2, 1);
      }
    }


    body {
      background: #6997DB;
      overflow: hidden;
    }

    h4 {
      position: absolute;
      bottom: 20px;
      left: 20px;
      margin: 0;
      font-weight: 200;
      opacity: .5;
      font-family: sans-serif;
      color: #fff;
    }

    .line {
      border-bottom: 0.1px solid black;
      border-style: dotted;

    }

    .total {
      width: 90%;
      text-align: right;
      font-size: 14px;
      font-weight: bold;
      padding-right: 10px;
    }
  </style>


</head>













<body>
  <div id="loader">
    <div id="shadow"></div>
    <div id="box"></div>
  </div>
  <h4>Loader #6</h4>


  <div class="ticket" id="ticket" style="margin-left: -10px !important;">

    <div class="containerImg">
      <img class="imgCompany" src="images/logo.png" alt="Logotipo">

    </div>

    <p class="centrado">J410285427
      <br>Mercado Rebusque Mayabiro
      <br><?php
          echo date('Y-m-d H:i a')
          ?>
      <br>
      <strong>


      </strong>
      <small>* Nota de entrega</small>
    </p>
    <br>
    <section id="resultTable"></section>


    <br>
    <br>
    <div class="line"></div>
    <p class="centrado" style="font-size: 10px;">¡GRACIAS POR SU COMPRA!
    <div class="line"></div>
    <br>
    <br>
    <br>
    <br>
    ...

  </div>

  <script>

    var id = "<?php echo $id ?>";
    var qty = "<?php echo $qty ?>";


    function consultarCantidad(params) {
    $.ajax({
        url: 'articulosTicketQty.php',
        type: 'POST',
        dataType: 'html',
        data: {
          id: params
        },
      })

      .done(function(resultado1) {


        if (qty != resultado1.trim()) {
          setTimeout(consultarCantidad(id), 500);
       //   alert('reconsultando. Esperado '+ qty + '. Result ' + resultado1.trim())
        }else{
          consultar(id)
        }

      })
  }

  consultarCantidad(id)


  function consultar(params) {
    $.ajax({
        url: 'articulosTicket.php',
        type: 'POST',
        dataType: 'html',
        data: {
          id: params
        },
      })

      .done(function(resultado1) {
        $('#resultTable').html(resultado1)
       const myTimeout = setTimeout(myStopFunction, 100);

      })


  }



  
  //http://localhost/iseller/publico/production/ticket.php?id=104&accion=vendido
  
  function myStopFunction() {
    
    if ($('#resultTable').html() != '' && $('#resultTable').html() != undefined) {
      imprimir()
      //alert('Se imprimira el ticket')
    }else{
        consultar(id)
    }

    }

    function imprimir() {
      let id = " <?php echo $id ?>";
      const printContents = document.getElementById('ticket').innerHTML;
      const originalContents = document.body.innerHTML;
      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      open('ventas.php?id=' + id, '_self').close();

    }
  </script>

</body>

</html>