$(obtener_registros_codigo3());

function obtener_registros_codigo3(rep_codigo3)
{
	$.ajax({
		url : 'consulta_datoscliente.php',
		type : 'POST',
		dataType : 'html',
		data : { rep_codigo3: rep_codigo3 },
		})

	.done(function(resultado_codigo3){
		$("#tabla_resultado_datos").html(resultado_codigo3);
	})
}

$(document).on('keyup', '#cedula', function()
{
	var valorcompraTipo3=$(this).val();
	if (valorcompraTipo3!="")
	{
		obtener_registros_codigo3(valorcompraTipo3);
	}
	else
		{
			obtener_registros_codigo3();
		}
});
