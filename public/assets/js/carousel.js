document.addEventListener('DOMContentLoaded', function () {
  // Support multiple carousels if needed: find all .carousel containers
  document.querySelectorAll('.carousel').forEach(car => {
    const track = car.querySelector('.carousel-track');
    if (!track) return;

    // query prev/next buttons if present in this carousel
    const prev = car.querySelector('.carousel-btn.prev');
    const next = car.querySelector('.carousel-btn.next');

    let isDown = false;
    let startX;
    let scrollLeft;
    const AUTOPLAY_DELAY = 3500;
    let autoplayId = null;

    // Mouse drag
    track.addEventListener('mousedown', (e) => {
      isDown = true;
      track.classList.add('dragging');
      startX = e.pageX - track.offsetLeft;
      scrollLeft = track.scrollLeft;
    });
    track.addEventListener('mouseleave', () => { isDown = false; track.classList.remove('dragging'); });
    track.addEventListener('mouseup', () => { isDown = false; track.classList.remove('dragging'); });
    track.addEventListener('mousemove', (e) => {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - track.offsetLeft;
      const walk = (x - startX) * 1.2; // scroll-fast
      track.scrollLeft = scrollLeft - walk;
    });

    // Touch drag
    track.addEventListener('touchstart', (e) => {
      startX = e.touches[0].pageX - track.offsetLeft;
      scrollLeft = track.scrollLeft;
    });
    track.addEventListener('touchmove', (e) => {
      const x = e.touches[0].pageX - track.offsetLeft;
      const walk = (x - startX) * 1.2;
      track.scrollLeft = scrollLeft - walk;
    });

    function slide(direction = 1) {
      const item = track.querySelector('.carousel-item');
      if (!item) return;
      const gap = parseInt(getComputedStyle(track).gap || 16, 10) || 16;
      const width = item.offsetWidth + gap;
      // apply animation class to items
      const items = Array.from(track.querySelectorAll('.carousel-item'));
      items.forEach(i => i.classList.remove('enter-from-left','enter-from-right','enter-active'));
      // determine visible start index
      const startScroll = track.scrollLeft;
      const target = startScroll + (direction * width);
      // add pre-animation class
      if (direction > 0) {
        items.forEach(i => i.classList.add('enter-from-right'));
      } else {
        items.forEach(i => i.classList.add('enter-from-left'));
      }
      // perform scroll
      track.scrollBy({ left: direction * width, behavior: 'smooth' });
      // after a short delay, activate enter animation
      setTimeout(() => {
        items.forEach(i => { i.classList.remove('enter-from-left','enter-from-right'); i.classList.add('enter-active'); });
      }, 120);
      resetAutoplay();
    }

    if (prev) prev.addEventListener('click', () => slide(-1));
    if (next) next.addEventListener('click', () => slide(1));

    function startAutoplay() {
      stopAutoplay();
      autoplayId = setInterval(() => slide(1), AUTOPLAY_DELAY);
    }
    function stopAutoplay() { if (autoplayId) { clearInterval(autoplayId); autoplayId = null; } }
    function resetAutoplay() { stopAutoplay(); startAutoplay(); }

    // Pause on hover/focus
    track.addEventListener('mouseenter', stopAutoplay);
    track.addEventListener('mouseleave', startAutoplay);

    // Start
    startAutoplay();
  });
});
