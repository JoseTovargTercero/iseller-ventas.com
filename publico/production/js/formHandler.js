class FormHandler {
  constructor({ formId, url, onSuccess, onError, onFail, data_extra = [] }) {
    this.form = document.getElementById(formId);
    this.url = url;
    this.onSuccess = onSuccess || function () {};
    this.onError = onError || function () {};
    this.onFail = onFail || function () {};
    this.data_extra = data_extra;

    if (!this.form) {
      console.error(`No se encontró un formulario con el ID: ${formId}`);
      return;
    }

    this.form.addEventListener("submit", this.handleSubmit.bind(this));
  }

  handleSubmit(e) {
    e.preventDefault();

    const formData = new FormData(this.form);

    // Añadir datos extra personalizados
    for (const [key, value] of this.data_extra) {
      const val = typeof value === "function" ? value() : value;
      formData.append(key, val);
    }

    for (const [key, value] of formData.entries()) {
      if (!value) {
        Alerta.toast("error", "Todos los campos son obligatorios.");
        return;
      }
    }

    fetch(this.url, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.text())
      .then((text) => {
        console.log("Respuesta cruda del servidor:", text);

        let json;
        try {
          json = JSON.parse(text);
        } catch (e) {
          console.error("Error al parsear JSON:", e);
          Alerta.mostrar("error", "El servidor no devolvió un JSON válido.");
          this.onFail(e, this.form);
          return;
        }

        if (json.success) {
          this.onSuccess(json, this.form);
          this.form.reset();
        } else {
          this.onError(json, this.form);
        }
      })
      .catch((err) => {
        console.error("Error en la solicitud:", err);
        Alerta.toast("error", "No se pudo contactar con el servidor");
        this.onFail(err, this.form);
      });
  }
}
