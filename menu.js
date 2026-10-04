(() => {
    const $ = (s, r = document) => r.querySelector(s);
    const toggle = $('.menu-toggle'), close = $('.close-menu');
    const overlay = $('.overlay'), shell = $('.site-shell');
    const transition = $('.page-transition');
    if (transition) {
        const done = () => transition.remove();
        transition.addEventListener('animationend', (e) => {
            if (e.target === transition) done();
        });
        setTimeout(done, 3000);
    }

    const logoutDialog = $('.logout-dialog');
    const openLogout = $('[data-open-logout]');
    const cancelLogout = $('[data-cancel-logout]');

    if (logoutDialog && openLogout && cancelLogout) {
        openLogout.addEventListener('click', () => logoutDialog.showModal());
        cancelLogout.addEventListener('click', () => logoutDialog.close());
        logoutDialog.addEventListener('click', (event) => {
            if (event.target === logoutDialog) logoutDialog.close();
        });

        // Animasi keluar: pintu fusuma menutup, lalu logout diproses
        const logoutForm = logoutDialog.querySelector('form');
        let leaving = false;
        if (logoutForm) {
            logoutForm.addEventListener('submit', (event) => {
                event.preventDefault();
                if (leaving) return;
                leaving = true;
                logoutDialog.close();

                const send = () => HTMLFormElement.prototype.submit.call(logoutForm);
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    send();
                    return;
                }

                const exit = document.createElement('div');
                exit.className = 'logout-exit';
                exit.setAttribute('aria-hidden', 'true');
                exit.innerHTML =
                    '<span class="ex-sun"></span>' +
                    '<span class="ex-ink"></span>' +
                    '<div class="ex-center">' +
                    '<div class="ex-disc"><span class="ex-kanji">\u7D42</span></div>' +
                    '<span class="ex-text">SAMPAI JUMPA</span>' +
                    '<span class="ex-sub">\u3055\u3088\u3046\u306A\u3089</span>' +
                    '</div>';
                document.body.classList.add('is-leaving');
                document.body.appendChild(exit);
                setTimeout(send, 2500);
            });
        }
    }

    if (toggle && close && overlay && shell) {
        const set = (open) => {
            shell.classList.toggle('menu-open', open);
            toggle.setAttribute('aria-expanded', open);
            overlay.setAttribute('aria-hidden', !open);
        };
        const shut = () => set(false);
        toggle.onclick = () => set(true);
        close.onclick = overlay.onclick = shut;
        document.onkeydown = (e) => {
            if (e.key === 'Escape') shut();
        };
    }

    // Kelopak sakura jatuh pada halaman login (dashboard & pengaturan)
    if ($('.login-card') && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const layer = document.createElement('div');
        layer.className = 'sakura';
        layer.setAttribute('aria-hidden', 'true');
        for (let i = 0; i < 26; i++) {
            const p = document.createElement('span');
            p.className = 'petal';
            p.style.left = (Math.random() * 100) + 'vw';
            p.style.setProperty('--size', (8 + Math.random() * 10).toFixed(1) + 'px');
            p.style.setProperty('--dur', (11 + Math.random() * 10).toFixed(1) + 's');
            p.style.setProperty('--delay', (Math.random() * -20).toFixed(1) + 's');
            p.style.setProperty('--sway', (Math.random() * 120 - 40).toFixed(0) + 'px');
            layer.appendChild(p);
        }
        document.body.appendChild(layer);
    }

    // Efek meriah halaman login: lampion, kunang-kunang, cincin berputar, kanji melayang
    if ($('.login-card') && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const rnd = (a, b) => a + Math.random() * (b - a);
        const fx = document.createElement('div');
        fx.className = 'login-fx';
        fx.setAttribute('aria-hidden', 'true');

        // Cincin berputar dengan titik cahaya yang mengorbit
        [[560, 38, 1], [780, 56, -1], [1020, 80, 1]].forEach(([size, dur, dir]) => {
            const ring = document.createElement('span');
            ring.className = 'fx-ring';
            ring.style.setProperty('--s', size + 'px');
            ring.style.setProperty('--spin', (dur * dir) + 's');
            fx.appendChild(ring);
        });

        // Kanji keberuntungan melayang naik
        ['和', '福', '桜', '夢', '光'].forEach((ch, i) => {
            const k = document.createElement('span');
            k.className = 'fx-kanji';
            k.textContent = ch;
            k.style.left = (8 + i * 19 + rnd(-4, 4)) + 'vw';
            k.style.setProperty('--size', rnd(44, 92).toFixed(0) + 'px');
            k.style.setProperty('--dur', rnd(22, 34).toFixed(1) + 's');
            k.style.setProperty('--delay', (-rnd(0, 30)).toFixed(1) + 's');
            fx.appendChild(k);
        });

        // Lampion kertas naik perlahan
        for (let i = 0; i < 7; i++) {
            const l = document.createElement('span');
            l.className = 'fx-lantern';
            l.style.left = (4 + i * 14 + rnd(-3, 3)) + 'vw';
            l.style.setProperty('--w', rnd(24, 44).toFixed(0) + 'px');
            l.style.setProperty('--dur', rnd(20, 32).toFixed(1) + 's');
            l.style.setProperty('--delay', (-rnd(0, 30)).toFixed(1) + 's');
            l.style.setProperty('--sway', rnd(-50, 50).toFixed(0) + 'px');
            fx.appendChild(l);
        }

        // Kunang-kunang
        for (let i = 0; i < 22; i++) {
            const f = document.createElement('span');
            f.className = 'fx-firefly';
            f.style.left = rnd(2, 98).toFixed(1) + 'vw';
            f.style.top = rnd(8, 92).toFixed(1) + 'vh';
            f.style.setProperty('--dx', rnd(-90, 90).toFixed(0) + 'px');
            f.style.setProperty('--dy', rnd(-90, 90).toFixed(0) + 'px');
            f.style.setProperty('--dur', rnd(6, 13).toFixed(1) + 's');
            f.style.setProperty('--tw', rnd(1.6, 3.4).toFixed(1) + 's');
            f.style.setProperty('--delay', (-rnd(0, 10)).toFixed(1) + 's');
            fx.appendChild(f);
        }

        document.body.appendChild(fx);

        // Efek parallax mengikuti gerakan kursor
        const card = $('.login-card');
        window.addEventListener('pointermove', (e) => {
            const nx = e.clientX / window.innerWidth - 0.5;
            const ny = e.clientY / window.innerHeight - 0.5;
            fx.style.setProperty('--mx', (nx * -26).toFixed(1) + 'px');
            fx.style.setProperty('--my', (ny * -26).toFixed(1) + 'px');
            if (card) {
                card.style.setProperty('--px', (nx * 10).toFixed(1) + 'px');
                card.style.setProperty('--py', (ny * 10).toFixed(1) + 'px');
            }
        }, { passive: true });
    }
})();
