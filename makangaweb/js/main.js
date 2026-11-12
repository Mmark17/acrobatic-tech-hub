// MakangaWeb Studio - front-end behaviour
document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Mobile navigation ---------- */
    var toggle = document.getElementById('navToggle');
    var links = document.getElementById('navLinks');
    if (toggle && links) {
        toggle.addEventListener('click', function () {
            var open = links.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* ---------- Password visibility controls ---------- */
    document.querySelectorAll('.password-toggle').forEach(function (button) {
        var input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) { return; }

        button.addEventListener('click', function () {
            var visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Hide' : 'Show';
            button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        });
    });

    /* ---------- Auto-hide success messages ---------- */
    document.querySelectorAll('.alert-success').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 6000);
    });

    /* ---------- Confirm before destructive actions ---------- */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    /* ---------- Helpers for client-side validation ---------- */
    function showError(input, message) {
        clearError(input);
        var span = document.createElement('span');
        span.className = 'field-error';
        span.textContent = message;
        input.parentNode.appendChild(span);
        input.setAttribute('aria-invalid', 'true');
    }
    function clearError(input) {
        var old = input.parentNode.querySelector('.field-error');
        if (old) { old.remove(); }
        input.removeAttribute('aria-invalid');
    }
    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var phoneRe = /^[0-9+\s-]{9,15}$/;

    /* ---------- Registration form ---------- */
    var reg = document.getElementById('registerForm');
    if (reg) {
        var pw = document.getElementById('password');
        var meter = document.getElementById('strengthBar');

        pw.addEventListener('input', function () {
            var v = pw.value, score = 0;
            if (v.length >= 8) { score++; }
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) { score++; }
            if (/[0-9]/.test(v)) { score++; }
            if (/[^A-Za-z0-9]/.test(v)) { score++; }
            var colours = ['#c0392b', '#c0392b', '#e6a117', '#7fb83b', '#1f8a5b'];
            meter.style.width = (score * 25) + '%';
            meter.style.background = colours[score];
        });

        reg.addEventListener('submit', function (e) {
            var ok = true;
            var name = reg.elements['full_name'];
            var email = reg.elements['email'];
            var phone = reg.elements['phone'];
            var confirm = reg.elements['confirm_password'];
            [name, email, phone, pw, confirm].forEach(clearError);

            if (name.value.trim().length < 3) { showError(name, 'Enter your full name.'); ok = false; }
            if (!emailRe.test(email.value.trim())) { showError(email, 'Enter a valid email address.'); ok = false; }
            if (!phoneRe.test(phone.value.trim())) { showError(phone, 'Enter a valid phone number.'); ok = false; }
            if (pw.value.length < 8 || !/[A-Za-z]/.test(pw.value) || !/[0-9]/.test(pw.value)) {
                showError(pw, 'Use at least 8 characters with letters and numbers.'); ok = false;
            }
            if (confirm.value !== pw.value) { showError(confirm, 'Passwords do not match.'); ok = false; }
            if (!ok) { e.preventDefault(); }
        });
    }

    /* ---------- Login form ---------- */
    var login = document.getElementById('loginForm');
    if (login) {
        function clearLoginFields() {
            login.elements['email'].value = '';
            login.elements['password'].value = '';
        }

        clearLoginFields();
        window.addEventListener('pageshow', clearLoginFields);
        window.addEventListener('pagehide', clearLoginFields);

        login.addEventListener('submit', function (e) {
            var email = login.elements['email'];
            var pass = login.elements['password'];
            [email, pass].forEach(clearError);
            var ok = true;
            if (!emailRe.test(email.value.trim())) { showError(email, 'Enter a valid email address.'); ok = false; }
            if (pass.value === '') { showError(pass, 'Enter your password.'); ok = false; }
            if (!ok) { e.preventDefault(); }
        });
    }

    /* ---------- Contact form ---------- */
    var contact = document.getElementById('contactForm');
    if (contact) {
        contact.addEventListener('submit', function (e) {
            var name = contact.elements['name'];
            var email = contact.elements['email'];
            var msg = contact.elements['message'];
            [name, email, msg].forEach(clearError);
            var ok = true;
            if (name.value.trim().length < 2) { showError(name, 'Please enter your name.'); ok = false; }
            if (!emailRe.test(email.value.trim())) { showError(email, 'Enter a valid email address.'); ok = false; }
            if (msg.value.trim().length < 10) { showError(msg, 'Write a message of at least 10 characters.'); ok = false; }
            if (!ok) { e.preventDefault(); }
        });
    }
});

/* Chat launcher: opens the panel; sending carries the message to the contact form */
(function () {
    var launch = document.getElementById('chatLaunch'), panel = document.getElementById('chatPanel');
    if (!launch || !panel) return;
    function toggle(open) {
        panel.hidden = !open;
        launch.setAttribute('aria-expanded', open ? 'true' : 'false');
        launch.classList.toggle('open', open);
        if (open) document.getElementById('chatInput').focus();
    }
    launch.addEventListener('click', function () { toggle(panel.hidden); });
    document.getElementById('chatClose').addEventListener('click', function () { toggle(false); });
})();

/* Business demo cart and request form */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('demoRequestForm');
    if (!form) { return; }

    var items = JSON.parse(form.dataset.items);
    var action = form.dataset.demoAction;
    var cart = {};
    var list = document.getElementById('demoCartItems');
    var total = document.getElementById('demoTotal');
    var result = document.getElementById('demoResult');
    var cartFields = document.getElementById('demoCartFields');
    var startDate = document.getElementById('demoDate');
    var checkoutDate = document.getElementById('demoCheckout');

    function nightsBetween(start, end) {
        var startParts = start.split('-').map(Number);
        var endParts = end.split('-').map(Number);
        var startUtc = Date.UTC(startParts[0], startParts[1] - 1, startParts[2]);
        var endUtc = Date.UTC(endParts[0], endParts[1] - 1, endParts[2]);
        return Math.round((endUtc - startUtc) / 86400000);
    }

    function getNights() {
        if (action !== 'booking' || !startDate.value || !checkoutDate.value) { return 1; }
        return Math.max(1, nightsBetween(startDate.value, checkoutDate.value));
    }

    function formatMoney(amount) {
        return 'UGX ' + new Intl.NumberFormat('en-UG').format(amount);
    }

    function renderCart() {
        list.textContent = '';
        cartFields.textContent = '';
        var count = 0;
        var subtotal = 0;

        Object.keys(cart).forEach(function (index) {
            var quantity = cart[index];
            var item = items[index];
            count += quantity;
            subtotal += item.price * quantity;

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'items[' + index + ']';
            input.value = quantity;
            cartFields.appendChild(input);

            var row = document.createElement('li');
            var description = document.createElement('span');
            description.textContent = item.name + ' × ' + quantity;

            var controls = document.createElement('span');
            controls.className = 'business-demo-cart-controls';
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline';
            remove.textContent = '−';
            remove.setAttribute('aria-label', 'Remove one ' + item.name);
            remove.addEventListener('click', function () {
                if (cart[index] > 1) { cart[index]--; } else { delete cart[index]; }
                renderCart();
            });
            var add = document.createElement('button');
            add.type = 'button';
            add.className = 'btn btn-sm btn-outline';
            add.textContent = '+';
            add.setAttribute('aria-label', 'Add one ' + item.name);
            add.addEventListener('click', function () {
                if (cart[index] >= 50) {
                    result.textContent = 'The demo allows up to 50 of each item per request.';
                    result.hidden = false;
                    return;
                }
                cart[index]++;
                renderCart();
            });
            controls.appendChild(remove);
            controls.appendChild(add);
            row.appendChild(description);
            row.appendChild(controls);
            list.appendChild(row);
        });

        if (count === 0) {
            var empty = document.createElement('li');
            empty.className = 'business-demo-empty';
            empty.textContent = 'Your selection is empty. Add an item to get started.';
            list.appendChild(empty);
        }

        total.textContent = formatMoney(subtotal * getNights());
    }

    document.querySelectorAll('[data-demo-add]').forEach(function (button) {
        button.addEventListener('click', function () {
            var index = button.dataset.index;
            if ((cart[index] || 0) >= 50) {
                result.textContent = 'The demo allows up to 50 of each item per request.';
                result.hidden = false;
                return;
            }
            cart[index] = (cart[index] || 0) + 1;
            renderCart();
            result.hidden = true;
        });
    });

    if (startDate && checkoutDate) {
        startDate.addEventListener('change', function () {
            if (!startDate.value) { return; }
            var startParts = startDate.value.split('-').map(Number);
            var nextDate = new Date(startParts[0], startParts[1] - 1, startParts[2] + 1);
            var minCheckout = nextDate.getFullYear() + '-' +
                String(nextDate.getMonth() + 1).padStart(2, '0') + '-' +
                String(nextDate.getDate()).padStart(2, '0');
            checkoutDate.min = minCheckout;
            checkoutDate.setCustomValidity('');
            if (checkoutDate.value && checkoutDate.value < minCheckout) {
                checkoutDate.value = '';
            }
            renderCart();
        });
        checkoutDate.addEventListener('change', function () {
            checkoutDate.setCustomValidity('');
            renderCart();
        });
    }

    form.addEventListener('submit', function (event) {
        result.hidden = true;
        if (Object.keys(cart).length === 0) {
            result.textContent = 'Add at least one item before sending your request.';
            result.hidden = false;
            event.preventDefault();
            return;
        }
        if (checkoutDate) { checkoutDate.setCustomValidity(''); }
        if (!form.reportValidity()) {
            event.preventDefault();
            return;
        }

        if (action === 'booking' && checkoutDate.value <= startDate.value) {
            checkoutDate.setCustomValidity('Choose a check-out date after check-in.');
            checkoutDate.reportValidity();
            event.preventDefault();
        }
    });

    renderCart();
});
