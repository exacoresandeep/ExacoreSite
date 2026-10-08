const header = document.querySelector('.site-header');
const progress = document.querySelector('.scroll-progress');
const cursor = document.querySelector('.cursor-glow');

window.addEventListener('scroll', () => {
  const y = window.scrollY;
  header.classList.toggle('scrolled', y > 50);
  const max = document.documentElement.scrollHeight - window.innerHeight;
  progress.style.width = `${(y / max) * 100}%`;
});

window.addEventListener('pointermove', e => {
  cursor.style.left = e.clientX + 'px';
  cursor.style.top = e.clientY + 'px';
  cursor.style.opacity = '1';
});

const menuBtn = document.querySelector('.menu-toggle');
const mobileMenu = document.querySelector('.mobile-menu');

menuBtn.addEventListener('click', () => {
  const open = mobileMenu.classList.toggle('open');
  document.body.classList.toggle('menu-open', open);
  menuBtn.setAttribute('aria-expanded', open);
});

document.querySelectorAll('.mobile-menu a').forEach(link => {
  link.addEventListener('click', () => {
    mobileMenu.classList.remove('open');
    document.body.classList.remove('menu-open');
    menuBtn.setAttribute('aria-expanded', 'false');
  });
});

// Reveal on scroll
const revealObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// Accordion
document.querySelectorAll('.accordion-head').forEach(button => {
  button.addEventListener('click', () => {
    const item = button.parentElement;
    const isOpen = item.classList.contains('active');

    document.querySelectorAll('.accordion-item').forEach(i => {
      i.classList.remove('active');
    });

    if (!isOpen) {
      item.classList.add('active');
    }
  });
});

// Counter animation
const counters = document.querySelectorAll('[data-count]');
const counterObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const el = entry.target;
    const target = Number(el.dataset.count);
    const duration = 1500;
    const start = performance.now();

    function tick(now) {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.floor(target * eased);
      if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
    counterObserver.unobserve(el);
  });
}, {threshold:.5});
counters.forEach(c => counterObserver.observe(c));

// Video modal
const modal = document.querySelector('#videoModal');
const companyVideo = modal.querySelector('.company-video');
document.querySelector('.play-btn').addEventListener('click', () => {
  modal.classList.add('open');
  modal.setAttribute('aria-hidden','false');
  companyVideo.play().catch(error => {
    console.error('Unable to play the Exacore company video:', error);
  });
});
document.querySelector('.modal-close').addEventListener('click', closeModal);
modal.addEventListener('click', e => { if(e.target === modal) closeModal(); });
function closeModal(){
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden','true');
  companyVideo.pause();
  companyVideo.currentTime = 0;
}
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });

// Contact form demo
document.querySelector('#contactForm').addEventListener('submit', e => {
  e.preventDefault();
  const message = e.currentTarget.querySelector('.form-message');
  message.textContent = 'Thank you. Your enquiry has been captured — connect this form to your backend/email API.';
  e.currentTarget.reset();
});

// Quick contact close
document.querySelector('.quick-contact button').addEventListener('click', e => {
  e.currentTarget.parentElement.style.transform = 'translateY(130px)';
  e.currentTarget.parentElement.style.opacity = '0';
});

// Current year
const year = document.querySelector('#year');
if (year) year.textContent = new Date().getFullYear();

// Subtle hero parallax
const heroMedia = document.querySelector('.hero-media');
window.addEventListener('scroll', () => {
  if (window.scrollY < window.innerHeight) {
    heroMedia.style.transform = `scale(1.05) translateY(${window.scrollY * .08}px)`;
  }
});
/* NAVIGATION HOVER PANELS */

const navHoverPanel =
  document.getElementById("navHoverPanel");
const aboutPanel =
  document.getElementById("aboutPanel");
const contactPanel =
 document.getElementById("contactPanel");
const navLinks =
  document.querySelectorAll(".desktop-nav a");
let hideTimer;

/* SHOW PANEL */
function showNavPanel(type) {

  clearTimeout(hideTimer);
  navHoverPanel.classList.add("show");
  aboutPanel.classList.remove("active");
  contactPanel.classList.remove("active");
  if (type === "about") {
    aboutPanel.classList.add("active");
  }
  if (type === "contact") {
    contactPanel.classList.add("active");
  }
}

/* HIDE PANEL */
function hideNavPanel() {
  hideTimer = setTimeout(() => {
    navHoverPanel.classList.remove("show");
  }, 150);

}

if (navHoverPanel && aboutPanel && contactPanel) {
  /* NAV LINKS */
  navLinks.forEach(link => {
    const text = link.textContent.trim().toLowerCase();
    if (text === "about us") {
      link.addEventListener("mouseenter", () => {
        showNavPanel("about");
      });
    }

    if (text === "contact us") {
      link.addEventListener("mouseenter", () => {
        showNavPanel("contact");
      });
    }

    link.addEventListener("mouseleave", hideNavPanel);
  });

  /* KEEP OPEN WHEN MOUSE IS ON PANEL */
  navHoverPanel.addEventListener("mouseenter", () => {
    clearTimeout(hideTimer);
  });

  navHoverPanel.addEventListener("mouseleave", hideNavPanel);
}