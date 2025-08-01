document
  .querySelector("form[name='data_form']")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const login = document.getElementById("login").value.trim();
    const password = document.getElementById("password").value.trim();

    if (!login || !password) {
      Swal.fire({
        icon: "warning",
        title: "Campos incompletos",
        text: "Por favor, completa todos los campos.",
      });
      return;
    }

    const formData = new FormData(this);

    fetch("login/guardar.php", {
      method: "POST",
      body: formData,
    })
      .then((resp) => resp.text()) // Obtener la respuesta como texto primero
      .then((text) => {
        console.log("Respuesta cruda del backend:", text); // Para debug
        let data;
        try {
          data = JSON.parse(text);
        } catch (error) {
          Swal.fire({
            icon: "error",
            title: "Error de formato",
            text: "La respuesta del servidor no es un JSON válido.",
          });
          console.error("Error al parsear JSON:", error);
          return;
        }

        if (data.status === true) {
          window.location.href = "publico/production/ventas.php";
        } else {
          Swal.fire({
            icon: "error",
            title: "Error de autenticación",
            text: data.msg || "Credenciales incorrectas.",
          });
        }
      })
      .catch((err) => {
        Swal.fire({
          icon: "error",
          title: "Error de conexión",
          text: "No se pudo conectar con el servidor.",
        });
        console.error("Error en la petición fetch:", err);
      });
  });
