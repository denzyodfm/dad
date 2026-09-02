document.querySelectorAll('[data-dialog]').forEach((button) => button.addEventListener('click', () => {
  const dialog = document.getElementById(button.dataset.dialog);
  if (dialog) dialog.showModal();
}));

const isOutside = (dialog, event) => {
  const box = dialog.getBoundingClientRect();
  return event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom;
};

document.querySelectorAll('dialog').forEach((dialog) => {
  dialog.querySelector('[data-close]')?.addEventListener('click', () => dialog.close());

  // Close only when a press both starts and ends on the backdrop. Testing the
  // click alone closed the dialog whenever a control inside it was activated by
  // keyboard, because those clicks report coordinates of 0,0.
  let pressedBackdrop = false;
  dialog.addEventListener('mousedown', (event) => {
    pressedBackdrop = event.target === dialog && isOutside(dialog, event);
  });
  dialog.addEventListener('click', (event) => {
    if (pressedBackdrop && event.target === dialog && isOutside(dialog, event)) dialog.close();
    pressedBackdrop = false;
  });
});
