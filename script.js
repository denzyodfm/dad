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

const track = document.querySelector('[data-carousel-track]');
if (track) {
  const previous = document.querySelector('[data-carousel-prev]');
  const next = document.querySelector('[data-carousel-next]');
  const current = document.getElementById('carousel-current');
  const cards = [...track.querySelectorAll('.project')];
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let autoplayTimer = null;
  let isPaused = false;
  const step = () => cards[0]?.getBoundingClientRect().width + parseFloat(getComputedStyle(track).gap || 0);
  const update = () => {
    const index = Math.max(0, Math.min(cards.length - 1, Math.round(track.scrollLeft / Math.max(step(), 1))));
    const visibleNumber = track.children[index]?.querySelector('.card-top span')?.textContent;
    if (current) current.textContent = String(parseInt(visibleNumber || String(index + 1), 10));
    if (previous) previous.disabled = track.scrollLeft <= 2;
    if (next) next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
  };
  previous?.addEventListener('click', () => track.scrollBy({left: -step(), behavior: reducedMotion ? 'auto' : 'smooth'}));
  next?.addEventListener('click', () => track.scrollBy({left: step(), behavior: reducedMotion ? 'auto' : 'smooth'}));
  track.addEventListener('scroll', update, {passive: true});
  track.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') previous?.click();
    if (event.key === 'ArrowRight') next?.click();
  });
  new ResizeObserver(update).observe(track);
  update();

  const advance = () => {
    if (isPaused || document.hidden) return;
    const distance = step();
    track.scrollBy({left: distance, behavior: 'smooth'});
    window.setTimeout(() => {
      const firstCard = track.firstElementChild;
      if (!firstCard) return;
      track.append(firstCard);
      track.scrollTo({left: Math.max(0, track.scrollLeft - distance), behavior: 'auto'});
      update();
    }, 650);
  };
  const pause = () => { isPaused = true; };
  const resume = () => { isPaused = false; };

  if (!reducedMotion && cards.length > 1) {
    autoplayTimer = window.setInterval(advance, 4200);
    track.addEventListener('mouseenter', pause);
    track.addEventListener('mouseleave', resume);
    track.addEventListener('focusin', pause);
    track.addEventListener('focusout', (event) => {
      if (!track.contains(event.relatedTarget)) resume();
    });
    track.addEventListener('pointerdown', pause);
    track.addEventListener('pointerup', resume);
    document.addEventListener('visibilitychange', update);
  }
}
