(() => {
  // Announcement bar expand/collapse + optional cookie suppression.
  const announcement = document.querySelector('[data-announcement]');
  if (!announcement) {
    return;
  }

  const toggle = announcement.querySelector('.announcement-toggle');
  if (!toggle) {
    return;
  }

  const cookieEnabled = announcement.dataset.cookieEnabled === '1';
  const cookieName = announcement.dataset.cookieName || 'announcement_seen';
  const cookieDays = parseInt(announcement.dataset.cookieDays || '0', 10);

  const setCookie = () => {
    if (!cookieEnabled || cookieDays <= 0) {
      return;
    }
    const expires = new Date();
    expires.setTime(expires.getTime() + cookieDays * 24 * 60 * 60 * 1000);
    document.cookie = `${cookieName}=1; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
  };

  const setExpanded = (expanded) => {
    announcement.classList.toggle('is-expanded', expanded);
    announcement.classList.toggle('is-collapsed', !expanded);
    announcement.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    if (expanded) {
      setCookie();
    }
  };

  // Initialize from server-provided state.
  const initialExpanded = announcement.getAttribute('aria-expanded') === 'true';
  setExpanded(initialExpanded);

  toggle.addEventListener('click', (event) => {
    event.preventDefault();
    const expanded = announcement.getAttribute('aria-expanded') === 'true';
    setExpanded(!expanded);
  });
})();
