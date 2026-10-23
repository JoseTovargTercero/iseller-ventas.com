// MUESTRA UN LOADER GLOBAL CUANDO SE REALICE UNA PETICON FETCH O AJAX
let solicitudesActivas = 0;
let loaderVisible = false;

function mostrarLoader() {
  if (loaderVisible) return;
  loaderVisible = true;
  Swal.fire({
    title: "Cargando...",
    allowOutsideClick: false,
    allowEscapeKey: false,
    backdrop: true,
    didOpen: () => {
      // Si la petición ya terminó mientras Swal se abría, se cierra al abrir.
      if (!loaderVisible) {
        Swal.close();
        return;
      }
      Swal.showLoading();
    },
    willClose: () => {
      loaderVisible = false;
    },
  });
}

function ocultarLoader() {
  if (!loaderVisible) return;
  // Se marca cerrado antes de Swal.close(): si el modal todavía se está
  // abriendo, didOpen lo cierra y no queda "Cargando..." pegado.
  loaderVisible = false;
  Swal.close();
}

// FETCH global
const originalFetch = window.fetch;
window.fetch = async (...args) => {
  solicitudesActivas++;
  if (solicitudesActivas === 1) mostrarLoader();

  try {
    const response = await originalFetch(...args);
    return response;
  } finally {
    solicitudesActivas--;
    if (solicitudesActivas === 0) ocultarLoader();
  }
};

// Fetch que NO muestra el loader: para polling y autorefrescos en segundo plano.
window.fetchSilencioso = (...args) => originalFetch(...args);

// jQuery AJAX global
$(document).ajaxStart(function () {
  solicitudesActivas++;
  if (solicitudesActivas === 1) mostrarLoader();
});

$(document).ajaxStop(function () {
  solicitudesActivas--;
  if (solicitudesActivas === 0) ocultarLoader();
});
