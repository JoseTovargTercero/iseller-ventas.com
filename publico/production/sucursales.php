<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1) {

  $topnav = topnav();

  if ($_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
  }
?>

  <!DOCTYPE html>
  <html lang="es">

  <head>
    <title>Sucursales</title>
    <?php require_once('includes/headers.php'); ?>
    <style>
      .right_col {
        background: var(--dash-bg);
        min-height: 100vh;
        padding: 24px 28px !important;
      }

      .dash-header {
        margin-bottom: 28px;
      }

      .dash-header h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--dash-text);
        margin: 0;
        letter-spacing: -0.3px;
      }

      .dash-header p {
        color: var(--dash-text-muted);
        margin: 2px 0 0;
        font-size: 13px;
      }

      .btn-dash-action {
        background: linear-gradient(135deg, #2dd4a0, #25b88a);
        border: none;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 18px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: all 0.2s ease;
        height: min-content;
        box-shadow: 0 3px 12px rgba(45, 212, 160, 0.25);
        cursor: pointer;
      }

      .btn-dash-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(45, 212, 160, 0.35);
        color: #fff;
      }

      .btn-dash-action ion-icon {
        font-size: 16px;
      }



      /* ── Tarjetas de sucursal ─────────────────────────────────────── */
      .suc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 12px;
        padding: 18px 20px 20px;
      }

      .suc-card {
        position: relative;
        padding: 16px;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
        background: rgba(255, 255, 255, .02);
        transition: all .2s ease;
      }

      .suc-card:hover {
        border-color: rgba(45, 212, 160, .25);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .22);
      }

      .suc-card-top {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .suc-logo {
        position: relative;
        width: 46px;
        height: 46px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(45, 212, 160, .1);
        overflow: hidden;
      }

      .suc-logo ion-icon {
        font-size: 22px;
        color: var(--dash-mint);
      }

      /* El logo tapa el icono; si no existe, onerror lo quita y queda el icono */
      .suc-logo img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .suc-head {
        flex: 1;
        min-width: 0;
      }

      .suc-head h6 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--dash-text);
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .suc-tipo {
        display: inline-block;
        margin-top: 4px;
        padding: 2px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        color: var(--dash-mint);
        background: rgba(45, 212, 160, .1);
      }

      .suc-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid rgba(46, 53, 62, .6);
      }

      .suc-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1px;
        padding: 7px 4px;
        border-radius: 10px;
        background: rgba(255, 255, 255, .02);
        text-align: center;
      }

      .suc-stat ion-icon {
        font-size: 15px;
        color: var(--dash-text-muted);
      }

      .suc-stat b {
        font-size: 15px;
        font-weight: 700;
        color: var(--dash-text);
        font-variant-numeric: tabular-nums;
      }

      .suc-stat span {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--dash-text-muted);
      }

      .suc-empty {
        grid-column: 1 / -1;
        padding: 46px 20px;
        text-align: center;
        color: var(--dash-text-muted);
      }

      .suc-empty ion-icon {
        font-size: 38px;
        opacity: .35;
      }

      .suc-empty h6 {
        margin: 10px 0 4px;
        font-size: 15px;
        font-weight: 600;
        color: var(--dash-text);
      }

      .suc-empty p {
        margin: 0;
        font-size: 12.5px;
      }

      .btn-edit-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--dash-border);
        background: transparent;
        color: var(--dash-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s ease;
        text-decoration: none;
      }

      .btn-edit-icon:hover {
        border-color: var(--dash-mint);
        color: var(--dash-mint);
        text-decoration: none;
      }

      .btn-edit-icon ion-icon {
        font-size: 16px;
      }

      #section_edit {
        max-width: 560px;
        margin: 0 auto;
      }

      #section_edit .form-label,
      .modal-sucursal .form-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--dash-text-muted);
        margin-bottom: 6px;
        display: block;
      }

      #section_edit .form-control,
      .modal-sucursal .form-control {
        background: var(--dash-bg);
        border: 1px solid var(--dash-border);
        color: var(--dash-text);
        border-radius: 8px;
        padding: 9px 14px;
        font-size: 13px;
        width: 100%;
        transition: border-color .2s ease;
      }

      #section_edit .form-control:focus,
      .modal-sucursal .form-control:focus {
        border-color: var(--dash-mint);
        outline: none;
        box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
      }

      .file-input-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .file-input-wrap input[type="file"] {
        display: none;
      }

      .file-input-btn {
        background: rgba(255, 255, 255, .06);
        border: 1px solid var(--dash-border);
        color: var(--dash-text-muted);
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all .2s ease;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }

      .file-input-btn:hover {
        border-color: var(--dash-mint);
        color: var(--dash-mint);
        background: rgba(45, 212, 160, .06);
      }

      .file-input-btn ion-icon {
        font-size: 16px;
      }

      .file-input-name {
        font-size: 12px;
        color: var(--dash-text-muted);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 200px;
      }

      .btn-dash-secondary {
        background: rgba(255, 255, 255, .06);
        border: 1px solid var(--dash-border);
        color: var(--dash-text-muted);
        border-radius: 8px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 500;
        transition: all .2s ease;
        cursor: pointer;
      }

      .btn-dash-secondary:hover {
        border-color: var(--dash-mint);
        color: var(--dash-mint);
      }

      .btn-dash-primary {
        background: linear-gradient(135deg, #2dd4a0, #25b88a);
        border: none;
        color: #fff;
        border-radius: 8px;
        padding: 9px 24px;
        font-size: 13px;
        font-weight: 600;
        transition: all .2s ease;
        box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
        cursor: pointer;
      }

      .btn-dash-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
        color: #fff;
      }

      .modal-sucursal .modal-content {
        background: var(--dash-card);
        border: 1px solid var(--dash-border);
        border-radius: 14px;
      }

      .modal-sucursal .modal-header {
        border-bottom: 1px solid var(--dash-border);
        padding: 16px 22px;
      }

      .modal-sucursal .modal-title {
        color: var(--dash-text);
        font-weight: 600;
        font-size: 16px;
      }

      .modal-sucursal .close {
        color: var(--dash-text-muted);
        font-size: 24px;
        opacity: .7;
        transition: opacity .2s;
        background: none;
        border: none;
      }

      .modal-sucursal .close:hover {
        opacity: 1;
      }

      .modal-sucursal .modal-body {
        padding: 20px 22px;
      }

      .modal-sucursal .modal-footer {
        border-top: 1px solid var(--dash-border);
        padding: 14px 22px;
      }

      .modal-sucursal select.form-control {
        appearance: auto;
      }

      .hide {
        display: none !important;
      }

      /* ── Móvil (WebView de la app) ────────────────────────────────── */
      @media (max-width: 768px) {
        .right_col {
          padding: 14px 12px !important;
        }

        .dash-panel .panel-header {
          padding: 14px 16px 12px;
        }

        /* El botón de agregar pasa a su propia fila, a lo ancho */
        .btn-dash-action {
          width: 100%;
          justify-content: center;
          padding: 11px 18px;
        }

        /* Una sucursal por fila */
        .suc-grid {
          grid-template-columns: 1fr;
          padding: 14px;
          gap: 10px;
        }

        .suc-card {
          padding: 14px;
        }

        .btn-edit-icon {
          width: 36px;
          height: 36px;
        }

        /* Los botones del formulario de edición, mitad y mitad */
        #section_edit .d-flex {
          gap: 10px;
        }

        #section_edit .btn-dash-primary,
        #section_edit .btn-dash-secondary {
          flex: 1;
          justify-content: center;
        }
      }
    </style>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php echo $menu ?>
        <?php echo $topnav ?>

        <div class="right_col">


          <section id="section_edit" class="hide">
            <div class="dash-panel">
              <div class="panel-header">
                <h6><ion-icon name="create-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Editar sucursal</h6>

              </div>
              <div class="panel-body" style="padding:20px 22px;">
                <form id="form-data" enctype="multipart/form-data">
                  <div class="mb-3">
                    <label class="form-label" for="edit_nombre">Nombre de sucursal</label>
                    <input type="text" id="edit_nombre" name="edit_nombre" required class="form-control">
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="edit_stock_critico">Cantidad mínima para stock crítico</label>
                    <input type="text" id="edit_stock_critico" name="edit_stock_critico" required class="form-control">
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="foto2">Logo de la sucursal (JPG o PNG)</label>
                    <div class="file-input-wrap">
                      <input type="file" id="foto2" name="foto2" accept=".jpg,.jpeg,.png">
                      <label for="foto2" class="file-input-btn"><ion-icon name="cloud-upload-outline"></ion-icon> Seleccionar</label>
                      <span class="file-input-name" id="foto2-name">Ningún archivo seleccionado</span>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between" style="margin-top:24px;">
                    <button type="button" id="btn-cancelar" class="btn-dash-secondary">Cancelar</button>
                    <button type="submit" class="btn-dash-primary">Actualizar</button>
                  </div>
                </form>
              </div>
            </div>
          </section>

          <div id="section_tabla">
            <div class="dash-panel">
              <div class="panel-header d-flex justify-content-between">
                <div>
                  <h6><ion-icon name="business-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Sucursales registradas</h6>
                  <small id="sucursal-count" style="color:var(--dash-text-muted);font-size:11px;">Cargando…</small>
                </div>
                <button type="button" class="btn-dash-action" id="btn-add-sucursal">
                  <ion-icon name="add-outline"></ion-icon> Agregar
                </button>
              </div>
              <div class="panel-body p-0">
                <div class="suc-grid" id="sucursales-grid"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade modal-sucursal" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Registro de Sucursales</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
              </div>
              <div class="modal-body">
                <form id="sucursal-form" novalidate>
                  <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre de sucursal</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Sucursal Centro" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="tipo">Tipo de sucursal</label>
                    <input list="tipos-comercio" id="tipo" name="tipo" class="form-control" placeholder="Ej: Panadería" required>
                    <datalist id="tipos-comercio">
                      <option value="Panadería">
                      <option value="Carnicería">
                      <option value="Heladería">
                      <option value="Frigorífico">
                      <option value="Supermercado">
                      <option value="Verdulería">
                      <option value="Pescadería">
                      <option value="Tienda de Ropa">
                      <option value="Joyería">
                      <option value="Farmacia">
                      <option value="Ferretería">
                      <option value="Papelería">
                      <option value="Librería">
                      <option value="Tienda de Electrónica">
                      <option value="Tienda de Mascotas">
                      <option value="Floristería">
                      <option value="Barbería">
                      <option value="Peluquería">
                      <option value="Restaurante">
                      <option value="Cafetería">
                      <option value="Tienda de Deportes">
                      <option value="Juguetería">
                      <option value="Boutique">
                      <option value="Auto Lavado">
                      <option value="Gimnasio">
                      <option value="Tienda de Muebles">
                      <option value="Centro de Estética">
                      <option value="Tienda de Telefonía">
                      <option value="Otro">
                    </datalist>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="stock_critico">Cantidad mínima para stock crítico</label>
                    <input type="number" id="stock_critico" name="stock_critico" class="form-control" min="0" placeholder="Ej: 10" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="foto">Logo de la sucursal (JPG o PNG)</label>
                    <div class="file-input-wrap">
                      <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png">
                      <label for="foto" class="file-input-btn"><ion-icon name="cloud-upload-outline"></ion-icon> Seleccionar</label>
                      <span class="file-input-name" id="foto-name">Ningún archivo seleccionado</span>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="productos_accion">Acción para productos registrados</label>
                    <select id="productos_accion" name="productos_accion" class="form-control" required>
                      <option value="">Seleccione una opción</option>
                      <option value="copiar">Copiar todos los productos</option>
                      <option value="no_copiar">No copiar ninguno</option>
                    </select>
                  </div>
                  <div class="d-grid" style="margin-top:24px;">
                    <button type="submit" class="btn-dash-primary" style="width:100%;justify-content:center;">Guardar</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <script src="../vendors/nprogress/nprogress.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="../build/js/modal.js"></script>
    <script src="js/formHandler.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
      let sucursales = [];
      let sucursal_editar;

      // Nombre y tipo los escribe el usuario: se escapan antes de meterlos en el HTML
      const esc = s => (s === null || s === undefined ? '' : String(s)).replace(
        /[&<>"']/g, c => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        })[c]);

      const $addModal = $('#addModal');

      $(document).ready(function() {
        $('#btn-add-sucursal').on('click', function() {
          $addModal.modal('show');
        });

        $addModal.on('hidden.bs.modal', function() {
          document.getElementById('sucursal-form').reset();
          document.getElementById('foto-name').textContent = 'Ningún archivo seleccionado';
        });

        document.getElementById('foto').addEventListener('change', function() {
          document.getElementById('foto-name').textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
        });
        document.getElementById('foto2').addEventListener('change', function() {
          document.getElementById('foto2-name').textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
        });

        $('#sucursal-form').on('submit', function(e) {
          e.preventDefault();
          const form = e.target;
          const formData = new FormData(form);
          for (const [key, value] of formData.entries()) {
            if (key === 'foto') continue;
            if (!value) {
              Alerta.toast('error', 'Todos los campos son obligatorios.');
              return;
            }
          }
          fetch('../../configurar/sucursales.php', {
              method: 'POST',
              body: formData
            })
            .then(res => res.text())
            .then(text => {
              let json;
              try {
                json = JSON.parse(text);
              } catch (e) {
                console.error("Error al parsear JSON:", e);
                Alerta.mostrar('error', 'El servidor no devolvió un JSON válido.');
                return;
              }
              if (json.success) {
                $addModal.modal('hide');
                Alerta.mostrar('success', json.message);
                form.reset();
                cargar_tabla();
              } else {
                Alerta.mostrar('warning', 'Hubo un problema ' + json.message);
              }
            })
            .catch(err => {
              $addModal.modal('hide');
              console.error("Error en la solicitud:", err);
              Alerta.mostrar('error', 'No se pudo contactar con el servidor');
            });
        });
      });

      function cargar_tabla() {
        fetch('../../configurar/sucursales_lista.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              table: 'sucursales',
              config: '_sucursales'
            })
          })
          .then(res => res.text())
          .then(text => {
            try {
              const data = JSON.parse(text);
              const lista = Array.isArray(data) ? data : [];
              const $grid = $('#sucursales-grid').empty();
              $('#sucursal-count').text(lista.length === 1 ? '1 sucursal' : `${lista.length} sucursales`);

              if (lista.length > 0) {
                lista.forEach(item => {
                  sucursales[item.id] = item;
                  $grid.append(`
            <article class="suc-card">
              <div class="suc-card-top">
                <div class="suc-logo">
                  <ion-icon name="storefront-outline"></ion-icon>
                  <img src="images/sucursal_logo/${item.id}.png" alt="" onerror="this.remove()">
                </div>
                <div class="suc-head">
                  <h6 title="${esc(item.nombre)}">${esc(item.nombre)}</h6>
                  <span class="suc-tipo">${esc(item.tipo) || 'Sin tipo'}</span>
                </div>
                <a data-id="${item.id}" class="btn-edit-icon" title="Editar"><ion-icon name="create-outline"></ion-icon></a>
              </div>
              <div class="suc-stats">
                <div class="suc-stat"><ion-icon name="people-outline"></ion-icon><b>${item.usuarios}</b><span>Usuarios</span></div>
                <div class="suc-stat"><ion-icon name="cube-outline"></ion-icon><b>${item.productos}</b><span>Productos</span></div>
                <div class="suc-stat"><ion-icon name="alert-circle-outline"></ion-icon><b>${item.stockCritico}</b><span>Stock mín.</span></div>
              </div>
            </article>
          `);
                });
              } else {
                $grid.append(`
            <div class="suc-empty">
              <ion-icon name="storefront-outline"></ion-icon>
              <h6>Aún no hay sucursales</h6>
              <p>Usa el botón "Agregar" para crear tu primer punto de venta.</p>
            </div>
          `);
              }
            } catch (e) {
              console.error("Error al parsear JSON:", e, "\nTexto:", text);
            }
          })
          .catch(err => console.error("Error en la solicitud:", err));
      }
      cargar_tabla();

      document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit-icon');
        if (btn) cargarDatosForm(sucursales[btn.getAttribute('data-id')]);
      });

      function cargarDatosForm(datos) {
        sucursal_editar = datos.id;
        document.getElementById('edit_nombre').value = datos.nombre;
        document.getElementById('edit_stock_critico').value = datos.stockCritico;
        document.getElementById('section_edit').classList.remove('hide');
        document.getElementById('section_tabla').classList.add('hide');
      }

      function cancelarActualizacion() {
        document.getElementById('section_edit').classList.add('hide');
        document.getElementById('section_tabla').classList.remove('hide');
        document.getElementById('form-data').reset();
        document.getElementById('foto2-name').textContent = 'Ningún archivo seleccionado';
        document.querySelectorAll('#form-data input').forEach(f => f.disabled = false);
      }

      document.getElementById('btn-cancelar').addEventListener('click', cancelarActualizacion);

      new FormHandler({
        formId: 'form-data',
        url: '../../configurar/editar_sucursal.php',
        data_extra: [
          ['id_editar', () => sucursal_editar]
        ],
        onSuccess: (json) => {
          Alerta.toast('success', json.mensaje);
          cargar_tabla();
          cancelarActualizacion();
        },
        onError: (json) => {
          Alerta.toast('error', json.mensaje || 'Error desconocido.');
        },
        onFail: (err) => {
          console.error('Fallo:', err);
        }
      });
    </script>

  <?php
} else {
  define('PAGINA_INICIO', '../../index.php');
  header('Location: ' . PAGINA_INICIO);
}
  ?>