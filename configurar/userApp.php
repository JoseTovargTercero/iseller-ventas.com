<?php
require_once('configurar/configuracion.php');

			
			$user = $_POST['user'];
			$passU = $_POST['pass'];
			$pass = md5($passU);


			$q = "SELECT * FROM usuarios WHERE usuario='$user' AND contrasena='$pass'";
			$s = $conexion->query($q);
				if ($s->num_rows > 0) {
					while ($f = $s->fetch_assoc()) {
					echo json_encode($f,JSON_UNESCAPED_UNICODE);
				}
			}
		
	

			//$s->close();
			$conexion->close();
