$(obtener_registros2());

function obtener_registros2(rep2)
{
	$.ajax({
		url : 'consulta_codigo_producto.php',
		type : 'POST',
		dataType : 'html',
		data : { rep2: rep2 },
		})

	.done(function(resultado2){
		$("#tabla_resultado_codigo_producto").html(resultado2);
	})
}

$(document).on('keyup', '#codigo', function()
{
	var valorBusqueda2=$(this).val();
	if (valorBusqueda2!="")
	{
		obtener_registros2(valorBusqueda2);
	}
	else
		{
			obtener_registros2();
		}
});
