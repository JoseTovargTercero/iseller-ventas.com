class TablaLoader {
  constructor(endpoint) {
    this.endpoint = endpoint;
  }

  async cargar(tabla, config) {
    try {
      const response = await fetch(this.endpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          table: tabla,
          config: config,
        }),
      });

      const text = await response.text();
      // console.log("Respuesta como texto:", text); // Debug

      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("Error al parsear JSON:", e);
        Swal.fire({
          icon: "error",
          title: "Respuesta no válida",
          text: "El servidor no devolvió un JSON válido.",
        });
        return null;
      }

      if (data.success) {
        return data.success;
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: data.message || "No se pudieron cargar los datos.",
        });
        return null;
      }
    } catch (err) {
      console.error("Error de conexión:", err);
      Swal.fire({
        icon: "error",
        title: "Error de conexión",
        text: "No se pudo obtener los datos del servidor.",
      });
      return null;
    }
  }
}
