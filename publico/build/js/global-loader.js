// MUESTRA UN LOADER GLOBAL CUANDO SE REALICE UNA PETICON FETCH O AJAX
let solicitudesActivas = 0;
let loaderVisible = false;

function mostrarLoader() {
  if (!loaderVisible) {
    loaderVisible = true;
    Swal.fire({
      title: "Cargando...",
      allowOutsideClick: false,
      allowEscapeKey: false,
      backdrop: true,
      didOpen: () => Swal.showLoading(),
      willClose: () => {
        loaderVisible = false;
      }, // reset
    });
  }
}

function ocultarLoader() {
  if (loaderVisible) {
    Swal.close(); // solo se cierra si fue el loader
  }
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

// jQuery AJAX global
$(document).ajaxStart(function () {
  solicitudesActivas++;
  if (solicitudesActivas === 1) mostrarLoader();
});

$(document).ajaxStop(function () {
  solicitudesActivas--;
  if (solicitudesActivas === 0) ocultarLoader();
});
