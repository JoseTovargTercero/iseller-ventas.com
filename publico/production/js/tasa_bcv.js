// Función para obtener el tipo de cambio USD desde la API
async function getExchangeRate() {
  try {
    const response = await fetch(
      "https://magicloops.dev/api/loop/4b921d65-98a4-4a6e-827a-76552b0c53af/run"
    );
    const data = await response.json();

    if (data?.tipo_cambio_referencia?.USD) {
      const usdRate = data.tipo_cambio_referencia.USD;
      console.log("USD rate:", usdRate);

      // Llamamos a la función para enviarlo al backend
      sendToBackend(usdRate);
    } else {
      console.error("No se encontró el valor del USD en la respuesta.");
    }
  } catch (error) {
    console.error("Error al obtener la tasa de cambio:", error);
  }
}

// Función para enviar la tasa al backend usando AJAX
function sendToBackend(usdRate) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "../../configurar/bcv_almacenar.php", true); // Cambia la ruta aquí

  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      console.log("Respuesta del backend:", xhr.responseText);
    }
  };

  const params = "usd_rate=" + encodeURIComponent(usdRate);
  xhr.send(params);
}

// obtener tasas del back
async function obtener_ultimo_cambio() {
  try {
    const response = await fetch("../../configurar/bcv_almacenado.php");
    const data = await response.json();

    const tasa = data.tasa;
    const ultimo_Cambio = data.ultimo_Cambio;

    if (tasa && ultimo_Cambio) {
      verificarActualizacion(tasa, ultimo_Cambio);
    } else {
      console.error("Faltan datos en la respuesta del backend.");
    }
  } catch (error) {
    console.error("Error al obtener el último cambio:", error);
  }
}

//obtener_ultimo_cambio();

// Verificar criterios de actualizacion
function verificarActualizacion(tasa_USD, ultima_actualizacion) {
  const ahora = new Date();
  const horaLimite = new Date();
  horaLimite.setHours(16, 0, 0, 0); // 4:00 PM hoy

  const fechaActualizacion = new Date(ultima_actualizacion * 1000);

  // Normalizar fechas a solo año-mes-día
  const hoy = new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate());
  const ayer = new Date(hoy);
  ayer.setDate(hoy.getDate() - 1);
  const actualizadaEl = new Date(
    fechaActualizacion.getFullYear(),
    fechaActualizacion.getMonth(),
    fechaActualizacion.getDate()
  );

  const actualizadaDespuesDeLas4 = fechaActualizacion.getHours() >= 16;
  const ahoraEsDespuesDeLas4 = ahora.getHours() >= 16;

  let debeActualizar = false;

  if (actualizadaEl.getTime() === hoy.getTime()) {
    // Actualizada hoy
    if (!actualizadaDespuesDeLas4 && ahoraEsDespuesDeLas4) {
      // Hoy antes de las 4 PM y ya pasaron las 4 PM
      debeActualizar = true;
    }
  } else if (actualizadaEl.getTime() === ayer.getTime()) {
    // Actualizada ayer
    if (!actualizadaDespuesDeLas4) {
      debeActualizar = true;
    } else {
      if (ahoraEsDespuesDeLas4) {
        debeActualizar = true;
      }
    }
  } else if (actualizadaEl < ayer) {
    // Cualquier día anterior a ayer
    debeActualizar = true;
  }

  if (debeActualizar) {
    console.log("✅ Debe actualizar la tasa. Obteniendo nueva tasa...");
    getExchangeRate();
  } else {
    console.log("❌ No es necesario actualizar aún.");
  }
}
