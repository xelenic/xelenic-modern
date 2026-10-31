(() => {
  'use strict';

  // Footer year
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // Rest the sections out of view (see .is-paused in style.css), waking them
  // well ahead of arrival so they're already moving when they come into view.
  if ('IntersectionObserver' in window) {
    const sections = new IntersectionObserver((entries) => {
      entries.forEach((entry) => entry.target.classList.toggle('is-paused', !entry.isIntersecting));
    }, { rootMargin: '50% 0px' });
    document.querySelectorAll('main > section, .site-footer').forEach((section) => sections.observe(section));
  }

  // Header shrink + back-to-top visibility on scroll
  const header = document.getElementById('siteHeader');
  const backToTop = document.getElementById('backToTop');

  const onScroll = () => {
    const scrolled = window.scrollY > 24;
    header?.classList.toggle('scrolled', scrolled);
    backToTop?.classList.toggle('visible', window.scrollY > 480);
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  // Mobile nav toggle
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');

  const closeNav = () => {
    navToggle?.classList.remove('open');
    navLinks?.classList.remove('open');
    navToggle?.setAttribute('aria-expanded', 'false');
  };

  navToggle?.addEventListener('click', () => {
    const isOpen = navLinks?.classList.toggle('open');
    navToggle.classList.toggle('open', !!isOpen);
    navToggle.setAttribute('aria-expanded', String(!!isOpen));
  });

  navLinks?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeNav);
  });

  // Scroll-triggered reveal animations (GSAP ScrollTrigger, falls back to
  // IntersectionObserver if the CDN scripts fail to load).
  const revealEls = document.querySelectorAll('.reveal');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // The lite (phone) page has no scroll animation: everything is just there.
  const lite = document.documentElement.classList.contains('is-lite');

  if (lite) {
    revealEls.forEach((el) => el.classList.add('in-view'));
  } else if (window.gsap && window.ScrollTrigger && revealEls.length) {
    gsap.registerPlugin(ScrollTrigger);

    if (reduceMotion) {
      gsap.set(revealEls, { autoAlpha: 1, y: 0 });
    } else {
      gsap.set(revealEls, { autoAlpha: 0, y: 28 });

      ScrollTrigger.batch(revealEls, {
        start: 'top 88%',
        onEnter: (batch) =>
          gsap.to(batch, {
            autoAlpha: 1,
            y: 0,
            duration: 0.7,
            ease: 'power3.out',
            stagger: 0.1,
            overwrite: true,
          }),
      });

      // Orbit diagram: scale + rotate in on top of its own fade-in
      const orbitWrap = document.querySelector('.orbit-wrap');
      if (orbitWrap) {
        gsap.from(orbitWrap, {
          scale: 0.75,
          rotate: -12,
          duration: 1.1,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: orbitWrap,
            start: 'top 82%',
          },
        });
      }
    }
  } else if ('IntersectionObserver' in window && revealEls.length) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -60px 0px' }
    );
    revealEls.forEach((el) => observer.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('in-view'));
  }

  // Contact form (front-end only demo submission)
  const form = document.getElementById('contactForm');
  const formNote = document.getElementById('formNote');

  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    if (formNote) {
      formNote.textContent = "Thanks, your message has been noted. We'll get back to you shortly.";
    }
    form.reset();
  });
})();
