// Landing page animations — GSAP + Intersection Observer
import gsap from 'gsap';

// ── Navbar scroll effect ──
const navbar = document.getElementById('navbar');
if (navbar) {
    window.addEventListener(
        'scroll',
        () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        },
        { passive: true },
    );
}

// ── Mobile menu ──
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');
if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        mobileMenu.classList.toggle('active');
        document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
    });
    window.closeMobileMenu = () => {
        hamburger.classList.remove('active');
        mobileMenu.classList.remove('active');
        document.body.style.overflow = '';
    };
}

// ── Intersection Observer for scroll reveals ──
const observerOptions = {
    threshold: 0.15,
    rootMargin: '0px 0px -50px 0px',
};

const revealObserver = new IntersectionObserver((entries) => {
    for (const entry of entries) {
        if (entry.isIntersecting) {
            const el = entry.target;
            const delay = Number.parseFloat(el.dataset.delay || '0');

            gsap.to(el, {
                opacity: 1,
                y: 0,
                x: 0,
                scale: 1,
                duration: 0.8,
                delay: delay,
                ease: 'power3.out',
            });

            revealObserver.unobserve(el);
        }
    }
}, observerOptions);

// Observe all reveal elements with staggered delays
for (const el of document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale')) {
    const parent = el.parentElement;
    if (parent) {
        const siblings = parent.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
        const siblingIndex = Array.from(siblings).indexOf(el);
        el.dataset.delay = (siblingIndex * 0.1).toFixed(1);
    }
    revealObserver.observe(el);
}

// ── Hero animations (immediate, not scroll-triggered) ──
const heroContent = document.querySelector('.hero-content');
const heroVisual = document.querySelector('.hero-visual');

if (heroContent) {
    gsap.from('.hero-content', {
        opacity: 0,
        y: 60,
        duration: 1,
        delay: 0.3,
        ease: 'power3.out',
    });
}
if (heroVisual) {
    gsap.from('.hero-visual', {
        opacity: 0,
        y: 40,
        scale: 0.95,
        duration: 1,
        delay: 0.6,
        ease: 'power3.out',
    });
}

const floatCard = document.querySelector('.hero-float-card');
const floatCard2 = document.querySelector('.hero-float-card-2');

if (floatCard) {
    gsap.from('.hero-float-card', {
        opacity: 0,
        y: -20,
        x: 20,
        duration: 0.8,
        delay: 1.2,
        ease: 'back.out(1.7)',
    });
    gsap.to('.hero-float-card', {
        y: -8,
        duration: 2.5,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: 2,
    });
}
if (floatCard2) {
    gsap.from('.hero-float-card-2', {
        opacity: 0,
        y: 20,
        x: -20,
        duration: 0.8,
        delay: 1.5,
        ease: 'back.out(1.7)',
    });
    gsap.to('.hero-float-card-2', {
        y: 8,
        duration: 3,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: 2.5,
    });
}

// ── Counter animation ──
const counterObserver = new IntersectionObserver(
    (entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = Number.parseInt(el.dataset.target, 10);
                const obj = { val: 0 };
                gsap.to(obj, {
                    val: target,
                    duration: 2,
                    ease: 'power2.out',
                    onUpdate: () => {
                        el.textContent = Math.round(obj.val).toLocaleString();
                    },
                });
                counterObserver.unobserve(el);
            }
        }
    },
    { threshold: 0.5 },
);

for (const el of document.querySelectorAll('.counter')) {
    counterObserver.observe(el);
}
