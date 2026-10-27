document.querySelectorAll("[data-download-blog]").forEach(button => {
  button.addEventListener("click", () => window.print());
});
