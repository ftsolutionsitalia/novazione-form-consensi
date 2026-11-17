/* Novazione – interazioni e invio del modulo. Funziona anche senza JS (fallback POST). */
(function () {
    'use strict';
    document.documentElement.classList.add('js');

    // Header con ombra allo scroll
    var header = document.querySelector('.header');
    if (header) {
        var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 40); };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // Animazioni d'ingresso
    var revealEls = document.querySelectorAll('.service-card, .contact-info, .contact-form-container');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { en.target.classList.add('visible'); io.unobserve(en.target); }
            });
        }, { threshold: 0.12 });
        revealEls.forEach(function (el) { el.classList.add('reveal'); io.observe(el); });
    }

    var form = document.getElementById('contactForm');
    if (!form) { return; }

    var alertBox = document.getElementById('form-alert');
    var button = form.querySelector('.submit-button');
    var label = button.querySelector('.btn-label');
    var defaultLabel = label.textContent;

    // Contatore caratteri del messaggio
    var msg = document.getElementById('messaggio');
    var counter = document.getElementById('messaggio-count');
    if (msg && counter) {
        var upd = function () {
            var n = msg.value.length;
            counter.textContent = n > 1500 ? n + ' / 2000' : '';
        };
        msg.addEventListener('input', upd);
        upd();
    }

    function normalizePhone(v) {
        var p = v.replace(/[\s\-.\/()]/g, '');
        if (p.indexOf('00') === 0) { p = '+' + p.slice(2); }
        if (p && p.charAt(0) !== '+') { p = '+39' + p; }
        return p;
    }

    var namePattern = /^[\p{L}\p{M}][\p{L}\p{M}' .\-]{1,59}$/u;
    var rules = {
        nome: function (v) { return namePattern.test(v.trim()) ? '' : 'Inserisci un nome valido (almeno 2 lettere).'; },
        cognome: function (v) { return namePattern.test(v.trim()) ? '' : 'Inserisci un cognome valido (almeno 2 lettere).'; },
        email: function (v) { return /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(v.trim()) ? '' : 'Inserisci un indirizzo email valido.'; },
        telefono: function (v) { return /^\+[1-9]\d{7,14}$/.test(normalizePhone(v)) ? '' : 'Inserisci un numero di cellulare valido (es. 333 123 4567).'; },
        privacy: function (_, el) { return el.checked ? '' : 'Per inviare la richiesta è necessario accettare l\'informativa privacy.'; }
    };

    function setError(name, message) {
        var field = form.elements[name];
        var err = document.getElementById('err-' + name);
        if (!field) { return; }
        if (message) { field.setAttribute('aria-invalid', 'true'); } else { field.removeAttribute('aria-invalid'); }
        if (err) { err.textContent = message || ''; }
    }

    function validateField(name) {
        var field = form.elements[name];
        if (!field || !rules[name]) { return true; }
        var m = rules[name](field.value || '', field);
        setError(name, m);
        return !m;
    }

    Object.keys(rules).forEach(function (name) {
        var field = form.elements[name];
        if (!field) { return; }
        var ev = field.type === 'checkbox' ? 'change' : 'blur';
        field.addEventListener(ev, function () { validateField(name); });
        field.addEventListener('input', function () {
            if (field.getAttribute('aria-invalid') === 'true') { validateField(name); }
        });
    });

    function showAlert(text) {
        alertBox.querySelector('span').textContent = text;
        alertBox.hidden = !text;
    }

    function setLoading(on) {
        button.disabled = on;
        button.classList.toggle('loading', on);
        label.textContent = on ? 'Invio in corso…' : defaultLabel;
    }

    form.addEventListener('submit', function (e) {
        var ok = true;
        Object.keys(rules).forEach(function (n) { if (!validateField(n)) { ok = false; } });
        if (!ok) {
            e.preventDefault();
            showAlert('Controlla i campi evidenziati.');
            var first = form.querySelector('[aria-invalid="true"]');
            if (first) { first.focus(); }
            return;
        }
        if (!window.fetch || !window.FormData) { setLoading(true); return; } // invio classico

        e.preventDefault();
        showAlert('');
        setLoading(true);

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().catch(function () { return { ok: false, message: 'Risposta non valida dal server.' }; });
        }).then(function (data) {
            if (data.ok) {
                window.location.href = data.redirect || 'success.php';
                return;
            }
            setLoading(false);
            if (data.errors) {
                Object.keys(data.errors).forEach(function (n) { setError(n, data.errors[n]); });
                var first = form.querySelector('[aria-invalid="true"]');
                if (first) { first.focus(); }
            }
            showAlert(data.message || 'Si è verificato un errore. Riprova.');
        }).catch(function () {
            setLoading(false);
            showAlert('Connessione non riuscita. Verifica la rete e riprova.');
        });
    });
})();
