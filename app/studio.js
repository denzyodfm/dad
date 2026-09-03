// Confirmation for destructive buttons. Kept in a file because the studio
// sends a Content-Security-Policy of script-src 'self', which blocks inline
// handlers.
document.querySelectorAll('[data-confirm]').forEach((button) => {
  button.addEventListener('click', (event) => {
    if (!window.confirm(button.dataset.confirm)) event.preventDefault();
  });
});
