document.addEventListener("DOMContentLoaded", function () {
  // Alternar al hacer clic
  document.querySelectorAll(".navdrop-toggle").forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      e.stopPropagation();
      const parent = this.closest(".navdrop");

      // Cerrar otros
      document.querySelectorAll(".navdrop").forEach((drop) => {
        if (drop !== parent) drop.classList.remove("open");
      });

      // Alternar el actual
      parent.classList.toggle("open");
    });
  });

  // Cerrar al hacer clic fuera
  document.addEventListener("click", function () {
    document.querySelectorAll(".navdrop").forEach((drop) => {
      drop.classList.remove("open");
    });
  });

  // Cerrar al salir del submenú con el mouse
  document.querySelectorAll(".navdrop-menu").forEach((menu) => {
    menu.addEventListener("mouseleave", function () {
      const parent = this.closest(".navdrop");
      parent.classList.remove("open");
    });
  });

  // 🔁 NUEVO: Cerrar si el mouse sale de todo el nav lateral
  const navbar = document.getElementById("navbar");
  if (navbar) {
    navbar.addEventListener("mouseleave", function () {
      document.querySelectorAll(".navdrop").forEach((drop) => {
        drop.classList.remove("open");
      });
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("toggle-navbar");
  if (!toggleBtn) return;
  const navbar = document.getElementById("navbar");
  const menuContainer = document.getElementById("menu_button");

  // Crear contenedor para posición original
  const originalContainer = document.createElement("div");
  originalContainer.id = "menu_button_original";
  toggleBtn.parentNode.insertBefore(originalContainer, toggleBtn);
  originalContainer.appendChild(toggleBtn);

  const icon = toggleBtn.querySelector("ion-icon");

  // Función para abrir menú y mover botón
  function openMenu() {
    navbar.classList.add("open");
    menuContainer.appendChild(toggleBtn);
    icon.setAttribute("name", "close-outline");
  }

  // Función para cerrar menú y devolver botón
  function closeMenu() {
    navbar.classList.remove("open");
    originalContainer.appendChild(toggleBtn);
    icon.setAttribute("name", "menu-outline");
  }

  // Toggle al hacer clic
  toggleBtn.addEventListener("click", function () {
    const isOpen = navbar.classList.contains("open");
    if (isOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  // Cerrar al hacer clic fuera
  document.addEventListener("click", function (e) {
    const isClickOutside =
      !navbar.contains(e.target) && !toggleBtn.contains(e.target);
    if (
      window.innerWidth < 992 &&
      navbar.classList.contains("open") &&
      isClickOutside
    ) {
      closeMenu();
    }
  });
});

// Resaltar item activo en el menú según la página actual
document.addEventListener("DOMContentLoaded", function () {
  const paginaActual = window.location.pathname.split("/").pop();
  if (!paginaActual) return;

  const links = document.querySelectorAll('#navbar a[href]');
  let encontrado = false;

  links.forEach(function (link) {
    const href = link.getAttribute('href');
    if (href === paginaActual) {
      link.classList.add('active');
      const navdrop = link.closest('.navdrop');
      if (navdrop) {
        const toggle = navdrop.querySelector('.navdrop-toggle');
        if (toggle) toggle.classList.add('active');
      }
      encontrado = true;
    }
  });
});
