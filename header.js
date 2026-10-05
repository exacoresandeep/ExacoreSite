const headerScript = document.currentScript;
const headerBaseUrl = headerScript ? headerScript.src : window.location.href;
const headerStylesheet = document.createElement("link");
headerStylesheet.rel = "stylesheet";
headerStylesheet.href = new URL("./header.css", headerBaseUrl).href;

const headerStylesLoaded = new Promise((resolve, reject) => {
  headerStylesheet.addEventListener("load", resolve, { once: true });
  headerStylesheet.addEventListener("error", () => {
    reject(new Error(`Could not load header.css from ${headerStylesheet.href}`));
  }, { once: true });
  document.head.appendChild(headerStylesheet);
});

fetch(new URL("./header.html", headerBaseUrl))
  .then(response => {
    if (!response.ok) {
      throw new Error(`Could not load header.html. HTTP status: ${response.status}`);
    }
    return response.text();
  })
  .then(async html => {
    await headerStylesLoaded;

    const template = document.createElement("template");
    template.innerHTML = html;
    const oldHeaders = [...document.querySelectorAll("header")];

    if (oldHeaders.length) {
      oldHeaders[0].replaceWith(template.content);
      oldHeaders.slice(1).forEach(header => header.remove());
    } else {
      document.body.prepend(template.content);
    }
    document.querySelectorAll(".mobile-menu, .mobile-nav").forEach(menu => menu.remove());

    const currentPage = window.location.pathname.split("/").pop() || "index.html";
    document.querySelector(".shared-site-header").dataset.page = currentPage;
    document.querySelectorAll(".shared-desktop-nav a").forEach(link => {
      const linkPage = new URL(link.href).pathname.split("/").pop();
      if (linkPage === currentPage) link.setAttribute("aria-current", "page");
    });

    const toggle = document.querySelector(".shared-menu-toggle");
    const sideMenu = document.querySelector(".shared-menu-overlay");
    const closeButtons = sideMenu.querySelectorAll(".shared-menu-close, .shared-menu-backdrop");
    const setMenuOpen = isOpen => {
      sideMenu.classList.toggle("is-open", isOpen);
      sideMenu.setAttribute("aria-hidden", String(!isOpen));
      toggle.setAttribute("aria-expanded", String(isOpen));
      toggle.setAttribute("aria-label", isOpen ? "Close side menu" : "Open side menu");
      document.body.classList.toggle("shared-menu-open", isOpen);
    };

    toggle.addEventListener("click", () => {
      setMenuOpen(!sideMenu.classList.contains("is-open"));
    });

    closeButtons.forEach(button => button.addEventListener("click", () => setMenuOpen(false)));
    sideMenu.querySelectorAll("a").forEach(link => link.addEventListener("click", () => setMenuOpen(false)));
    document.addEventListener("keydown", event => {
      if (event.key === "Escape" && sideMenu.classList.contains("is-open")) setMenuOpen(false);
    });

    const updateHeaderState = () => {
      const sharedHeader = document.querySelector(".shared-site-header");
      if (sharedHeader) sharedHeader.classList.toggle("is-scrolled", window.scrollY > 10);
    };

    updateHeaderState();
    window.addEventListener("scroll", updateHeaderState, { passive: true });
  })
  .catch(error => {
    console.error("Shared header loading error:", error);
  });
