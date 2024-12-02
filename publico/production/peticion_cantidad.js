$(obtener_registros_cantidad());

function obtener_registros_cantidad(rep_cantidad)
{
	$.ajax({
		url : 'consulta_precio.php',
		type : 'POST',
		dataType : 'html',
		data : { rep_cantidad: rep_cantidad },
		})

	.done(function(resultado){
		$("#tabla_resultado_precio").html(resultado);
	})
}

$(document).on('keyup', '#unidad', function()
{
	var valorcantidad=$(this).val();
	if (valorcantidad!="")
	{
		obtener_registros_cantidad(valorcantidad);
	}
	else
		{
			obtener_registros_cantidad();
		}
});
