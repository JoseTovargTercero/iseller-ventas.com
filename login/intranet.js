function validacion_index(){ 
   	
	//valido el correo electronico 
   	valor_nombre = document.getElementById("p_username").value;
	if( valor_nombre.length==0 ) 
	{
	f_inicio.p_username.focus()
	alert('[ERROR] Tiene que escribir su nombre de usuario.');
	return false;
  	}
	
	//valido la contraseña
   	valor_password = document.getElementById("p_password").value;
	if( valor_password.length==0 ) 
	{
	f_inicio.p_password.focus()
	alert('[ERROR] Tiene que escribir su contraseña.');
	return false;
  	}
	
 
   	f_inicio.submit(); 
} // JavaScript Document