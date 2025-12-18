/**
 * Novazione Landing Page
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for navigation links
    initSmoothScroll();

    // Form validation
    initFormValidation();

    // Header scroll effect
    initHeaderScroll();

    // Animation on scroll
    initScrollAnimations();
});

/**
 * Smooth scroll for anchor links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Form validation and submission
 */
function initFormValidation() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const inputs = form.querySelectorAll('input, textarea');

    // Real-time validation
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });

        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Validate all fields
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        // Check privacy checkbox
        const privacyCheckbox = document.getElementById('privacy');
        if (!privacyCheckbox.checked) {
            isValid = false;
            showError(privacyCheckbox, 'Devi accettare l\'informativa sulla privacy');
        }

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = form.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            // Show loading state
            const submitBtn = form.querySelector('.submit-button');
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<span>Invio in corso...</span>';
        }
    });
}

/**
 * Validate a single field
 */
function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';

    // Remove previous error
    clearError(field);

    // Skip non-required empty fields
    if (!field.required && value === '') {
        return true;
    }

    // Required field validation
    if (field.required && value === '') {
        isValid = false;
        errorMessage = 'Questo campo è obbligatorio';
    }

    // Email validation
    if (isValid && field.type === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Inserisci un indirizzo email valido';
        }
    }

    // Phone validation
    if (isValid && field.type === 'tel') {
        const phoneRegex = /^[\+]?[0-9\s]{10,15}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            isValid = false;
            errorMessage = 'Inserisci un numero di telefono valido';
        }
    }

    // Name validation (min 2 chars)
    if (isValid && (field.name === 'nome' || field.name === 'cognome')) {
        if (value.length < 2) {
            isValid = false;
            errorMessage = 'Deve contenere almeno 2 caratteri';
        }
    }

    if (!isValid) {
        showError(field, errorMessage);
    }

    return isValid;
}

/**
 * Show error message for a field
 */
function showError(field, message) {
    field.classList.add('error');

    // Create error message element
    let errorEl = field.parentNode.querySelector('.error-message');
    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'error-message';
        field.parentNode.appendChild(errorEl);
    }
    errorEl.textContent = message;

    // Add error styles
    field.style.borderColor = '#ef4444';
}

/**
 * Clear error from a field
 */
function clearError(field) {
    field.classList.remove('error');
    field.style.borderColor = '';

    const errorEl = field.parentNode.querySelector('.error-message');
    if (errorEl) {
        errorEl.remove();
    }
}

/**
 * Header scroll effect
 */
function initHeaderScroll() {
    const header = document.querySelector('.header');
    if (!header) return;

    let lastScroll = 0;

    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 100) {
            header.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1)';
        } else {
            header.style.boxShadow = 'none';
        }

        lastScroll = currentScroll;
    });
}

/**
 * Animation on scroll
 */
function initScrollAnimations() {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements
    document.querySelectorAll('.service-card, .contact-form-container, .contact-info').forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
}

// Add error message styles dynamically
const errorStyles = document.createElement('style');
errorStyles.textContent = `
    .error-message {
        display: block;
        color: #ef4444;
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }

    .form-group input.error,
    .form-group textarea.error {
        border-color: #ef4444 !important;
        background-color: #fef2f2 !important;
    }

    .form-group input.error:focus,
    .form-group textarea.error:focus {
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2) !important;
    }
`;
document.head.appendChild(errorStyles);
