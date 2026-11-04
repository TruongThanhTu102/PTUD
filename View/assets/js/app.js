"use strict";
// Form actions work without JavaScript. This adds only a delete confirmation.
document.addEventListener("submit", function (event) {
  const form = event.target.closest("form[data-confirm]");
  if (form && !window.confirm(form.getAttribute("data-confirm"))) {
    event.preventDefault();
  }
});
