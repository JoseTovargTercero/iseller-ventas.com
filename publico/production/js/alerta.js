// alerta.js
class Alerta {
  static mostrar(tipo, mensaje, redireccion = null) {
    const types_title = {
      success: "Éxito!",
      error: "Ups!",
      warning: "Atención",
      info: "Atención",
      question: "Atención",
    };

    Swal.fire({
      icon: tipo, // 'success', 'error', 'warning', 'info', 'question'
      title: types_title[tipo],
      html: mensaje,
      showConfirmButton: false,
      timer: 6500,
      willClose: () => {
        if (redireccion) {
          window.location.href = redireccion;
        }
      },
    });
  }

  static toast(tipo, mensaje, posicion = "bottom-end", tiempo = 3000) {
    Swal.fire({
      toast: true,
      position: posicion,
      icon: tipo,
      title: mensaje,
      showConfirmButton: false,
      timer: tiempo,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener("mouseenter", Swal.stopTimer);
        toast.addEventListener("mouseleave", Swal.resumeTimer);
      },
    });
  }
}
