const footerScript = document.currentScript;
const footerBaseUrl = footerScript ? footerScript.src : window.location.href;
const footerStylesheet = document.createElement("link");
footerStylesheet.rel = "stylesheet";
footerStylesheet.href = new URL("./footer.css", footerBaseUrl).href;

const footerStylesLoaded = new Promise((resolve, reject) => {
  footerStylesheet.addEventListener("load", resolve, { once: true });
  footerStylesheet.addEventListener("error", () => {
    reject(new Error(`Could not load footer.css from ${footerStylesheet.href}`));
  }, { once: true });
  document.head.appendChild(footerStylesheet);
});

fetch(new URL("./footer.html", footerBaseUrl))
  .then(response => {
    if (!response.ok) {
      throw new Error(`Could not load footer.html. HTTP status: ${response.status}`);
    }
    return response.text();
  })
  .then(async html => {
    await footerStylesLoaded;
    const template = document.createElement("template");
    template.innerHTML = html;

    document.querySelectorAll("footer").forEach(footer => footer.remove());

    const target = document.getElementById("footer");
    if (target) {
      target.replaceWith(template.content);
    } else {
      document.body.append(template.content);
    }

    const year = document.querySelector("#year");
    if (year) year.textContent = new Date().getFullYear();
  })
  .catch(error => {
    console.error("Shared footer loading error:", error);
  });
