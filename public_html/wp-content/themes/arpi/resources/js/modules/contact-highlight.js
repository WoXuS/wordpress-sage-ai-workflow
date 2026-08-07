// MVP has no contact page: the header "Contact" link scrolls to the footer
// (handled by anchor-scroll). Once there, nudge the phone/email links so the
// user sees where the contact details live.
export default function initContactHighlight() {
  const nudge = () => {
    document.querySelectorAll('[data-contact-highlight]').forEach((el) => {
      el.classList.remove('is-nudging');
      void el.offsetWidth; // reflow so the animation replays on repeat clicks
      el.classList.add('is-nudging');
    });
  };

  document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-contact-footer]')) return;
    // Let the smooth-scroll settle first, then draw the eye.
    setTimeout(nudge, 550);
  });

  document.addEventListener('animationend', (event) => {
    event.target?.matches?.('[data-contact-highlight]') &&
      event.target.classList.remove('is-nudging');
  });
}
