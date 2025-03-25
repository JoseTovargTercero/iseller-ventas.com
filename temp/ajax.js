
/* ajax */
$.ajax({
    type: 'POST',
    url: '../../back/ajax',
    dataType: 'html',
    data: {
        var: '',
        var: '',
    },
    cache: false,
    success: function(msg) {
      
      
    }
}).fail(function(jqXHR, textStatus, errorThrown) {
    toast('r', 'Ocurrió un error, inténtelo nuevamente ' + errorThrown)
});
/* ajax */

/** Locatio */
window.location.href = 'produc/go_muro';



    /* OBTENER SECTORES */
    function obt_sectores(value) {
        $.get("../../back/ajax_selects/go_sectores.php", "plan=" + value, function(data) {
          $('#plan_sector').append(data)
        });
      }
      /* END OF: OBTENER SECTORES */