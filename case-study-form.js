document.querySelectorAll(".case-study-form").forEach(form => {
  form.addEventListener("submit", async event => {
    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    const status = form.querySelector(".case-study-form-status");
    if (!(button instanceof HTMLButtonElement) || !(status instanceof HTMLElement)) {
      console.error("Case study form is missing its submit button or status message.");
      return;
    }

    button.disabled = true;
    status.textContent = "Sending the case study PDF...";

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { Accept: "application/json" }
      });
      const result = await response.json();

      if (!response.ok || result.ok !== true) {
        throw new Error(result.message || "The case study email could not be sent.");
      }

      status.textContent = result.message;
      form.reset();
    } catch (error) {
      status.textContent = error instanceof Error
        ? error.message
        : "The case study email could not be sent. Please try again.";
    } finally {
      button.disabled = false;
    }
  });
});
