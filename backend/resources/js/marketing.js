import "bootstrap-icons/font/bootstrap-icons.css";
import "../css/marketing.css";

const animateCount = (el) => {
    const target = Number(el.dataset.count || 0);
    const start = performance.now();
    const duration = 1200;

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        el.textContent = Math.round(target * progress).toLocaleString("en-US");
        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    };

    requestAnimationFrame(tick);
};

const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (!entry.isIntersecting) {
            return;
        }
        animateCount(entry.target);
        io.unobserve(entry.target);
    });
}, { threshold: 0.4 });

document.querySelectorAll("[data-count]").forEach((el) => io.observe(el));
