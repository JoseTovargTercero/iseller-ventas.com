$(obtener_registros_codigo());

function obtener_registros_codigo(rep_codigo)
{
	$.ajax({
		url : 'consulta_codigo.php',
		type : 'POST',
		dataType : 'html',
		data : { rep_codigo: rep_codigo },
		})

	.done(function(resultado_codigo){
		$("#tabla_resultado_codigo").html(resultado_codigo);
	})
}

$(document).on('keyup', '#nombre', function()
{
	var valornombre=$(this).val();
	if (valornombre!="")
	{
		obtener_registros_codigo(valornombre);
	}
	else
		{
			obtener_registros_codigo();
		}
});
