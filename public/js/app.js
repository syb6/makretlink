/* MarketLink front-end helpers (plain JS, no build step)
   1) Theme (dark mode) — applied before paint
   2) Core UI behaviours — flash, confirms, navs, back-to-top
   3) Motion layer — scroll reveals, progress, ripples, count-up       */
(function () {
    'use strict';

    /* =========================================================
       0) Themed dialogs — mlConfirm() / mlAlert()
       Promise-based replacements for window.confirm/alert so every
       destructive action and error uses the MarketLink theme with
       animations, instead of raw browser chrome.
       ========================================================= */
    var dialogCounter = 0;

    function buildDialog(opts) {
        var id = 'ml-dialog-' + (++dialogCounter);
        var safeMsg = String(opts.message).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });

        var backdrop = document.createElement('div');
        backdrop.className = 'ml-dialog-backdrop';
        backdrop.id = id;
        backdrop.setAttribute('role', 'presentation');
        backdrop.innerHTML =
            '<div class="ml-dialog" role="' + (opts.mode === 'alert' ? 'alertdialog' : 'dialog') + '" aria-modal="true" aria-label="' + (opts.title ? safeMsg : 'Dialog') + '">' +
                '<div class="ml-dialog-icon ml-dialog-icon-' + (opts.danger ? 'danger' : 'primary') + '">' +
                    '<i class="bi ' + (opts.danger ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill') + '"></i>' +
                '</div>' +
                (opts.title ? '<h5 class="ml-dialog-title"></h5>' : '') +
                '<p class="ml-dialog-message">' + safeMsg + '</p>' +
                '<div class="ml-dialog-actions">' +
                    (opts.mode === 'confirm' ? '<button type="button" class="btn btn-outline-ml btn-sm" data-dialog-cancel>Cancel</button>' : '') +
                    '<button type="button" class="btn ' + (opts.danger ? 'btn-danger' : 'btn-ml') + ' btn-sm" data-dialog-ok>' + (opts.okLabel || 'OK') + '</button>' +
                '</div>' +
            '</div>';

        if (opts.title) {
            backdrop.querySelector('.ml-dialog-title').textContent = opts.title;
        }

        document.body.appendChild(backdrop);
        // Double rAF so the transition runs reliably after insertion.
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { backdrop.classList.add('show'); });
        });

        return backdrop;
 }

    function closeDialog(backdrop, value, resolve) {
        backdrop.classList.remove('show');
        setTimeout(function () { backdrop.remove(); }, 220);
        resolve(value);
    }

    /**
     * Themed confirm. Usage: mlConfirm('Delete this product?').then(function (ok) { ... })
     * Esc/backdrop click resolve false; Enter / OK button resolves true.
     */
    function mlConfirm(message, options) {
        options = options || {};
        return new Promise(function (resolve) {
            if (reduced) { resolve(window.confirm(message)); return; }

            var backdrop = buildDialog({
                message: message,
                title: options.title || 'Are you sure?',
                danger: options.danger !== false,
                okLabel: options.okLabel || 'Yes, continue',
                mode: 'confirm',
            });

            var done = false;
            var settle = function (value) {
                if (done) return;
                done = true;
                document.removeEventListener('keydown', onKey);
                closeDialog(backdrop, value, resolve);
            };

            var onKey = function (e) {
                if (e.key === 'Escape') settle(false);
                if (e.key === 'Enter') { e.preventDefault(); settle(true); }
            };
            document.addEventListener('keydown', onKey);

            backdrop.querySelector('[data-dialog-ok]').addEventListener('click', function () { settle(true); });
            var cancel = backdrop.querySelector('[data-dialog-cancel]');
            if (cancel) cancel.addEventListener('click', function () { settle(false); });
            backdrop.addEventListener('click', function (e) { if (e.target === backdrop) settle(false); });

            var ok = backdrop.querySelector('[data-dialog-ok]');
            if (ok) ok.focus();
        });
    }

    /** Themed alert: mlAlert('Saved!') or mlAlert('…', { danger: true }). */
    function mlAlert(message, options) {
        options = options || {};
        return new Promise(function (resolve) {
            if (reduced) { window.alert(message); resolve(); return; }

            var backdrop = buildDialog({
                message: message,
                title: options.title || (options.danger ? 'Something went wrong' : 'Notice'),
                danger: !!options.danger,
                okLabel: options.okLabel || 'Got it',
                mode: 'alert',
            });

            var done = false;
            var settle = function () {
                if (done) return;
                done = true;
                document.removeEventListener('keydown', onKey);
                closeDialog(backdrop, undefined, resolve);
            };

            var onKey = function (e) {
                if (e.key === 'Escape' || e.key === 'Enter') { e.preventDefault(); settle(); }
            };
            document.addEventListener('keydown', onKey);

            backdrop.querySelector('[data-dialog-ok]').addEventListener('click', settle);
            backdrop.addEventListener('click', function (e) { if (e.target === backdrop) settle(); });

            var ok = backdrop.querySelector('[data-dialog-ok]');
            if (ok) ok.focus();
        });
    }

    // Expose for the other scripts (marketlink.js) and inline handlers.
    window.mlConfirm = mlConfirm;
    window.mlAlert = mlAlert;

    /* =========================================================
       1) Theme (dark mode) toggle
       ========================================================= */
    var THEME_KEY = 'ml-theme';

    function applyTheme(theme) {
        var el = document.documentElement;
        el.setAttribute('data-bs-theme', theme);
        var icon = document.getElementById('themeIcon');
        if (icon) {
            // Moon shown in light mode (click -> dark), sun in dark mode
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    /**
     * Theme switch with a brief cross-fade: .theme-anim on <html> enables
     * color transitions for one moment, then is removed so regular page
     * updates never transition (per UX best practice). Skipped when the
     * user prefers reduced motion.
     */
    function applyThemeAnimated(next) {
        var el = document.documentElement;
        applyTheme(next);
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        el.classList.add('theme-anim');
        clearTimeout(applyThemeAnimated._t);
        applyThemeAnimated._t = setTimeout(function () { el.classList.remove('theme-anim'); }, 400);
    }

    /* =========================================================
       2) + 3) DOM-ready behaviours
       ========================================================= */
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('DOMContentLoaded', function () {

        /* ---------- Theme toggle (with smooth cross-fade) ---------- */
        var themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                localStorage.setItem(THEME_KEY, next);
                applyThemeAnimated(next);
            });
        }

        /* ---------- Custom ml-alert toasts ----------
           Auto-dismiss with a visible countdown; hovering an alert pauses
           its timer so slow readers never lose a message. Alerts carrying
           data-ttl="0" (validation summaries) stay until dismissed. */
        document.querySelectorAll('[data-ml-alert]').forEach(function (alertEl) {
            if (alertEl.__mlAlertBound) return;
            alertEl.__mlAlertBound = true;

            var ttl = parseInt(alertEl.dataset.ttl || '0', 10);
            var timer = null;
            var remaining = ttl;
            var startedAt = 0;

            var dismiss = function () {
                alertEl.classList.add('leaving');
                setTimeout(function () {
                    var stack = alertEl.parentElement;
                    alertEl.remove();
                    // Tidy up empty stacks so no phantom gap remains.
                    if (stack && stack.classList.contains('ml-alert-stack') && !stack.children.length) {
                        stack.remove();
                    }
                }, 320);
            };

            alertEl.querySelector('.ml-alert-close')?.addEventListener('click', dismiss);

            if (ttl > 0) {
                var start = function () {
                    startedAt = Date.now();
                    timer = setTimeout(dismiss, remaining);
                };
                var pause = function () {
                    if (!timer) return;
                    clearTimeout(timer);
                    timer = null;
                    remaining -= Date.now() - startedAt;
                };
                alertEl.addEventListener('mouseenter', pause);
                alertEl.addEventListener('mouseleave', start);
                start();
            }
        });

        /* ---------- Legacy auto-hide alerts (any remaining alert-auto-hide) ---------- */
        document.querySelectorAll('.alert-auto-hide').forEach(function (el) {
            setTimeout(function () {
                try {
                    bootstrap.Alert.getOrCreateInstance(el).close();
                } catch (e) {
                    el.classList.add('d-none');
                }
            }, 4500);
        });

        /* ---------- Confirm dialogs for destructive forms ---------- */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (form.__mlConfirmed) { form.__mlConfirmed = false; return; }
                e.preventDefault();
                mlConfirm(form.getAttribute('data-confirm'), { danger: true }).then(function (ok) {
                    if (!ok) return;
                    form.__mlConfirmed = true;
                    if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }
                });
            });
        });

        /* ---------- Themed logout confirmation ---------- */
        // Same promise-based dialog as destructive actions; not dangerous, so
        // no red chrome — just a friendly "see you soon" before signing out.
        document.querySelectorAll('form[action*="logout"]').forEach(function (form) {
            if (form.dataset.logoutBound) return; // idempotent
            form.dataset.logoutBound = '1';
            form.addEventListener('submit', function (e) {
                if (form.__mlConfirmed) { form.__mlConfirmed = false; return; }
                e.preventDefault();
                mlConfirm('Sign out of MarketLink?', {
                    title: 'See you soon!',
                    danger: false,
                    okLabel: 'Sign out',
                }).then(function (ok) {
                    if (!ok) return;
                    form.__mlConfirmed = true;
                    if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }
                });
            });
        });

        /* ---------- Mobile drawer (olive nav rail) ---------- */
        var mobileToggle = document.getElementById('mobileToggle');
        var navMenu = document.getElementById('navMenu');
        if (mobileToggle && navMenu) {
            mobileToggle.addEventListener('click', function () {
                var open = navMenu.classList.toggle('open');
                mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            navMenu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    navMenu.classList.remove('open');
                    mobileToggle.setAttribute('aria-expanded', 'false');
                });
            });
        }

        /* ---------- Admin sidebar off-canvas (below lg) ---------- */
        var adminToggle = document.getElementById('adminNavToggle');
        var adminSidebar = document.getElementById('adminSidebar');
        var adminBackdrop = document.getElementById('sidebarBackdrop');
        if (adminToggle && adminSidebar) {
            var setAdminNav = function (open) {
                adminSidebar.classList.toggle('show', open);
                if (adminBackdrop) adminBackdrop.classList.toggle('show', open);
            };
            adminToggle.addEventListener('click', function () {
                setAdminNav(!adminSidebar.classList.contains('show'));
            });
            if (adminBackdrop) {
                adminBackdrop.addEventListener('click', function () { setAdminNav(false); });
            }
        }

        /* ---------- Offline / online banner ---------- */
        var offlineBanner = document.getElementById('offlineBanner');
        if (offlineBanner) {
            var offlineTimer = null;
            var offlineMsg = offlineBanner.querySelector('[data-offline-msg]');
            var onlineMsg = offlineBanner.querySelector('[data-online-msg]');

            var showBanner = function (backOnline) {
                clearTimeout(offlineTimer);
                offlineBanner.classList.toggle('online', backOnline);
                offlineMsg.classList.toggle('d-none', backOnline);
                onlineMsg.classList.toggle('d-none', !backOnline);
                offlineBanner.classList.add('show');

                if (backOnline) {
                    // Reassurance notice — auto-hide after a moment.
                    offlineTimer = setTimeout(function () {
                        offlineBanner.classList.remove('show');
                    }, 3500);
                }
            };

            if (!navigator.onLine) showBanner(false);
            window.addEventListener('offline', function () { showBanner(false); });
            window.addEventListener('online', function () { showBanner(true); });
        }

        /* ---------- Back-to-top button ---------- */
        var backToTop = document.getElementById('backToTop');
        if (backToTop) {
            var onScrollTop = function () {
                backToTop.classList.toggle('show', window.scrollY > 300);
            };
            window.addEventListener('scroll', onScrollTop, { passive: true });
            onScrollTop();
            backToTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
            });
        }

        /* =========================================================
           3) Motion layer (skipped when reduced motion is preferred)
           ========================================================= */

        /* ---------- Scroll progress bar ---------- */
        var progress = document.createElement('div');
        progress.className = 'scroll-progress';
        document.body.appendChild(progress);

        var ticking = false;
        var updateProgress = function () {
            var h = document.documentElement;
            var max = h.scrollHeight - h.clientHeight;
            var ratio = max > 0 ? h.scrollTop / max : 0;
            progress.style.width = (ratio * 100) + '%';
            // Single scroll variable (0..1) consumed by the parallax layers in CSS.
            h.style.setProperty('--scroll', ratio.toFixed(4));
            ticking = false;
        };
        var requestProgress = function () {
            if (!ticking) { ticking = true; requestAnimationFrame(updateProgress); }
        };
        window.addEventListener('scroll', requestProgress, { passive: true });
        window.addEventListener('resize', requestProgress);
        updateProgress();

        if (reduced) return; // everything below is decorative motion

        /* ---------- Page-enter fade ---------- */
        // Soft fade-up on every fresh paint. Skipped on bfcache restores
        // (back/forward must feel instant) — pageshow clears it there.
        document.body.classList.add('page-enter');
        window.addEventListener('pageshow', function () {
            document.body.classList.remove('page-enter');
        });

        document.documentElement.classList.add('js-anim');

        /* ---------- Scroll-reveal via IntersectionObserver ---------- */
        var revealSelector = [
            '.stat-card', '.product-card', '.market-card', '.card-ml',
            '.dashboard-card', '.feature-card', '.step-card', '.category-card',
            '.order-item', '.metric-box', '.auth-card',
            /* broader coverage: content sections across public + dashboard pages */
            '.notif-item', '.breadcrumb', '.page-banner .container',
            '.market-availability', '.empty',
            /* home refresh: section intros + banners opt in explicitly */
            '.section-head', '.promo-content', '.promo-img-box', '.cta-box > h2',
            '.cta-box > p', '.cta-btns'
        ].join(', ');

        var revealEls = Array.prototype.slice.call(document.querySelectorAll(revealSelector))
            // Skip elements inside modals — they animate with the modal itself.
            .filter(function (el) { return !el.closest('.modal'); });

        revealEls.forEach(function (el) {
            el.classList.add('reveal');
            // Stagger siblings within the same parent (max 6 steps).
            var parent = el.parentElement;
            if (parent) {
                var index = Array.prototype.indexOf.call(parent.children, el);
                el.style.setProperty('--d', Math.min(Math.max(index, 0), 5) * 70 + 'ms');
            }
        });

        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        io.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });

            revealEls.forEach(function (el) { io.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add('in-view'); });
        }

        /* ---------- Hero content entrance ---------- */
        // The anim classes use fill-mode: both, which would freeze their final
        // keyframe over the parallax transform — so drop them once finished.
        var hero = document.querySelector('.hero-content');
        if (hero) {
            hero.classList.add('anim-up');
            hero.addEventListener('animationend', function () { hero.classList.remove('anim-up'); }, { once: true });
        }
        var heroImg = document.querySelector('.hero-image-wrapper');
        if (heroImg) {
            heroImg.classList.add('anim-pop');
            heroImg.style.animationDelay = '150ms';
            heroImg.addEventListener('animationend', function () {
                heroImg.classList.remove('anim-pop');
                heroImg.style.removeProperty('animation-delay');
            }, { once: true });
        }

        /* ---------- Click ripple on buttons ---------- */
        document.addEventListener('click', function (e) {
            // NOTE: .chatbot-fab is intentionally excluded — its pulse ring needs
            // overflow: visible, and it already has press/hover feedback.
            var btn = e.target.closest('.btn-ml, .btn-marketlink, .btn-outline-ml, .btn-marketlink-outline, .btn-explore-market, .back-to-top, .dropdown-item, a.page-link, .pill-nav a, .pill-nav button, .sidebar-menu a, .sidebar-menu button');
            if (!btn) return;

            var rect = btn.getBoundingClientRect();
            var d = Math.max(rect.width, rect.height);
            var ink = document.createElement('span');
            ink.className = 'ripple-ink';
            ink.style.width = ink.style.height = d + 'px';
            ink.style.left = (e.clientX - rect.left - d / 2) + 'px';
            ink.style.top = (e.clientY - rect.top - d / 2) + 'px';
            btn.classList.add('ripple-host');
            btn.appendChild(ink);
            setTimeout(function () { ink.remove(); }, 600);
        });

        /* ---------- Subtle 3D tilt on the hero basket ---------- */
        // Desktop, fine pointers only. Runs on the <img> while the float
        // animation runs on the wrapper's <picture> — the two compose.
        var tiltWrap = document.querySelector('.hero-image-wrapper');
        var tiltImg = tiltWrap ? tiltWrap.querySelector('.hero-img') : null;
        if (tiltWrap && tiltImg && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            tiltWrap.addEventListener('mousemove', function (e) {
                var r = tiltWrap.getBoundingClientRect();
                var x = (e.clientX - r.left) / r.width - 0.5;
                var y = (e.clientY - r.top) / r.height - 0.5;
                tiltImg.style.transform = 'perspective(700px) rotateY(' + (x * 7).toFixed(2) + 'deg) rotateX(' + (-y * 7).toFixed(2) + 'deg)';
            });
            tiltWrap.addEventListener('mouseleave', function () {
                tiltImg.style.transform = '';
            });
        }

        /* ---------- Stat number count-up ---------- */
        var countUp = function (el) {
            var target = parseFloat(el.dataset.target || '0');
            var decimals = (el.dataset.target.split('.')[1] || '').length;
            var dur = 900;
            var start = null;

            var step = function (ts) {
                if (start === null) start = ts;
                var p = Math.min((ts - start) / dur, 1);
                var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
                el.textContent = (target * eased).toFixed(decimals);
                if (p < 1) requestAnimationFrame(step);
                else el.textContent = el.dataset.target;
            };
            requestAnimationFrame(step);
        };

        var counterIO = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                countUp(entry.target);
                counterIO.unobserve(entry.target);
            });
        }, { threshold: 0.4 }) : null;

        document.querySelectorAll('.stat-number').forEach(function (el) {
            var raw = el.textContent.trim();
            // Only animate plain numbers (skip "$1,234.00" and "4.6 / 5")
            if (!/^\d+(\.\d+)?$/.test(raw)) return;
            el.dataset.target = raw;
            el.textContent = '0';
            if (counterIO) { counterIO.observe(el); } else { countUp(el); }
        });

        /* ---------- Site-wide image shimmer (per page layout) ---------- */
        // Sweeping placeholder on every substantial content image while it
        // loads (cards, circles, heroes, farmer photos...). Tiny images
        // (avatars/logos <= 48px) skip it; classes are removed on load or
        // error so the shimmer never sticks.
        Array.prototype.forEach.call(document.images, function (img) {
            var host = img.parentElement;
            if (!host || host.classList.contains('img-shimmer')) return;
            if (img.closest('.modal, #chatbotWindow')) return; // modal/chat handled elsewhere
            if (Math.max(img.width, img.height) > 0 && Math.max(img.width, img.height) <= 48) return;
            host.classList.add('img-shimmer');
            var clear = function () {
                host.classList.add('img-shimmer-out');
                setTimeout(function () {
                    host.classList.remove('img-shimmer', 'img-shimmer-out');
                }, 450);
            };
            if (img.complete && img.naturalWidth > 0) {
                clear(); // cached image — shimmer would just flash
            } else {
                img.addEventListener('load', clear, { once: true });
                img.addEventListener('error', clear, { once: true });
            }
        });

        /* ---------- Lazy-loading safety net ---------- */
        // Blade templates already set loading="lazy"; this catches any
        // below-fold image that slipped through (dynamic includes etc.).
        Array.prototype.forEach.call(document.images, function (img) {
            if (img.loading || img.getAttribute('fetchpriority') === 'high') return;
            var r = img.getBoundingClientRect();
            if (r.top > window.innerHeight && r.height >= 40) {
                img.loading = 'lazy';
            }
        });

        /* ---------- Page-leave skeletons, shaped per destination ---------- */
        // Products/markets/farmers grids paint slowly on first load, so show
        // a skeleton page the moment the user follows a link to them. Each
        // destination gets its own layout mirror of the real page.
        var GRIDS = ['/products', '/markets', '/farmers'];
        var overlay = null;

        // Reusable fragment builders
        var skelHead = function (label) {
            return '<div class="skeleton-head"><span class="skeleton-dot"></span><span class="skeleton-dot" style="animation-delay:.15s"></span><span class="skeleton-dot" style="animation-delay:.3s"></span>' +
                '<span style="margin-left:6px">' + label + '\u2026</span></div>';
        };
        var skelBanner = function () {
            return '<div class="sk-banner"><div class="sk-banner-eyebrow"></div><div class="sk-banner-title"></div><div class="sk-banner-sub"></div></div>';
        };
        var skelProductCard = function (i) {
            return '<div class="skeleton-card" style="--i:' + i + '">' +
                '<div class="skeleton-block skeleton-img"></div>' +
                '<div class="sk-chip"></div>' +
                '<div class="skeleton-line w60"></div>' +
                '<div class="skeleton-line w80"></div>' +
                '<div class="sk-price-row"><div class="sk-price"></div><div class="sk-add"></div></div>' +
                '</div>';
        };
        var skelMarketCard = function (i) {
            return '<div class="skeleton-card" style="--i:' + i + '">' +
                '<div class="skeleton-block skeleton-img"></div>' +
                '<div class="skeleton-line w60"></div>' +
                '<div class="skeleton-line w80"></div>' +
                '<div class="sk-chip-row"><span class="sk-chip"></span><span class="sk-chip"></span><span class="sk-chip"></span></div>' +
                '<div class="sk-foot"><div class="sk-price"></div><div class="sk-link"></div></div>' +
                '</div>';
        };
        var skelFarmerCard = function (i) {
            return '<div class="skeleton-card sk-farmer" style="--i:' + i + '">' +
                '<div class="sk-farmer-head"><div class="sk-avatar"></div><div class="sk-farmer-id"><div class="skeleton-line w80" style="margin:0 0 8px"></div><div class="skeleton-line w60" style="margin:0"></div></div></div>' +
                '<div class="skeleton-line"></div>' +
                '<div class="skeleton-line w80"></div>' +
                '<div class="sk-chip-row"><span class="sk-chip"></span><span class="sk-chip"></span></div>' +
                '<div class="sk-btn"></div>' +
                '</div>';
        };

        var buildSkeleton = function (kind) {
            var el = document.createElement('div');
            el.className = 'skeleton-overlay';
            el.setAttribute('aria-hidden', 'true');
            var html = '<div class="skeleton-inner">';

            if (kind === '/products') {
                // Products page: banner, filter card row, count row, 4-col grid.
                html += skelHead('Gathering fresh products');
                html += skelBanner();
                html += '<div class="sk-filter">';
                for (var f = 0; f < 6; f++) html += '<div class="sk-field"></div>';
                html += '<div class="sk-btn sk-btn-go"></div></div>';
                html += '<div class="sk-count-row"><div class="skeleton-line w40" style="margin:0"></div></div>';
                html += '<div class="skeleton-grid sk-grid-4">';
                for (var i = 0; i < 8; i++) html += skelProductCard(i);
            } else if (kind === '/markets') {
                // Markets page: banner, then sidebar cards + map, 2-col grid.
                html += skelHead('Finding local markets');
                html += skelBanner();
                html += '<div class="sk-markets-layout">';
                html += '<div class="sk-side">';
                for (var s = 0; s < 2; s++) {
                    html += '<div class="sk-side-card"><div class="skeleton-line w60" style="margin:0 0 10px"></div>';
                    for (var b = 0; b < 2; b++) html += '<div class="sk-field" style="margin:8px 0"></div>';
                    html += '<div class="sk-btn" style="margin-top:10px"></div></div>';
                }
                html += '<div class="sk-map"></div></div>';
                html += '<div class="sk-side-main"><div class="skeleton-grid sk-grid-2">';
                for (var m2 = 0; m2 < 4; m2++) html += skelMarketCard(m2);
                html += '</div></div></div>';
            } else {
                // Farmers page: banner, centered search row, 3-col avatar cards.
                html += skelHead('Meeting the growers');
                html += skelBanner();
                html += '<div class="sk-search"><div class="sk-field"></div><div class="sk-field"></div><div class="sk-btn"></div></div>';
                html += '<div class="skeleton-grid sk-grid-3">';
                for (var fa = 0; fa < 6; fa++) html += skelFarmerCard(fa);
            }

            html += '</div>';
            el.innerHTML = html;
            return el;
        };

        var skeletonFor = function (path) {
            return GRIDS.filter(function (g) { return path === g || path.indexOf(g + '/') === 0 || path.indexOf(g + '?') === 0; })[0];
        };

        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var link = e.target.closest('a[href]');
            if (!link) return;
            var target = skeletonFor(new URL(link.href, location.href).pathname);
            if (!target || link.target === '_blank' || link.hasAttribute('download')) return;
            overlay = buildSkeleton(target);
            document.body.appendChild(overlay);
            document.body.classList.add('page-leaving');
            setTimeout(function () {
                // Server never answered — restore the page so it stays usable.
                if (overlay && overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                    overlay = null;
                    document.body.classList.remove('page-leaving');
                }
            }, 4000);
        });

        window.addEventListener('pageshow', function (e) {
            // bfcache back-navigation: the overlay would otherwise freeze on screen
            if (e.persisted && overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
                overlay = null;
            }
            document.body.classList.remove('page-leaving');
        });

        /* ---------- Chat greeting typewriter (delightful, tiny) ---------- */
        var greet = document.querySelector('#chatbotBody .chatbot-msg.bot:first-child');
        if (greet && greet.textContent.length < 220) {
            var full = greet.textContent;
            greet.textContent = '';
            var pos = 0;
            var type = function () {
                greet.textContent = full.slice(0, pos += 3);
                if (pos < full.length) setTimeout(type, 24);
            };
            setTimeout(type, 500);
        }

        /* ---------- Image pickers (products, markets, profiles…) ---------- */
        document.querySelectorAll('[data-image-picker]').forEach(function (picker) {
            var input = picker.querySelector('.image-picker-input');
            var tile = picker.querySelector('[data-picker-tile]');
            var preview = picker.querySelector('.image-picker-preview');
            if (!input || !tile) return;

            tile.addEventListener('click', function () { input.click(); });

            // Remember the original hint text so an error message can be undone.
            (function () {
                var meta = picker.querySelector('.image-picker-meta');
                var hint = meta && meta.querySelector('.picker-hint');
                if (hint && !hint.dataset.originalHint) {
                    hint.dataset.originalHint = hint.textContent.trim();
                }
            })();

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) return;
                if (!/^image\/(png|jpe?g|webp)$/i.test(file.type)) {
                    input.value = '';
                    tile.animate(
                        [
                            { transform: 'translateX(0)' },
                            { transform: 'translateX(-6px)' },
                            { transform: 'translateX(6px)' },
                            { transform: 'translateX(0)' },
                        ],
                        { duration: 260 }
                    );
                    // Say WHY nothing happened — a silent shake reads as a
                    // broken save button, which is how profile updates got
                    // mistaken for "not working".
                    var meta = picker.querySelector('.image-picker-meta');
                    var hint = meta && meta.querySelector('.picker-hint');
                    if (hint) {
                        hint.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>That file is not a supported picture. Use JPG, PNG or WebP.';
                        hint.classList.add('picker-hint-error');
                        clearTimeout(picker.__hintTimer);
                        picker.__hintTimer = setTimeout(function () {
                            hint.innerHTML = hint.dataset.originalHint || 'JPG, PNG or WebP · max 2 MB · square works best';
                            hint.classList.remove('picker-hint-error');
                        }, 6000);
                    } else {
                        mlAlert('That file is not a supported picture. Use JPG, PNG or WebP (max 2 MB).', { danger: true, title: 'Unsupported file' });
                    }
                    return;
                }
                var url = URL.createObjectURL(file);
                var img = preview.querySelector('img');
                if (img) {
                    img.src = url;
                } else {
                    img = document.createElement('img');
                    img.src = url;
                    img.alt = '';
                    preview.appendChild(img);
                }
                tile.classList.add('has-image');
                var text = picker.querySelector('.picker-text');
                if (text) text.textContent = 'Change picture';
            });

            var removeBtn = picker.querySelector('[data-picker-remove]');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    var url = removeBtn.dataset.url;
                    if (!url) return;
                    mlConfirm('Remove this picture?', { danger: true, okLabel: 'Yes, remove it' }).then(function (ok) {
                        if (!ok) return;
                        fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    })
                        .then(function (r) {
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            preview.innerHTML = '';
                            tile.classList.remove('has-image');
                            removeBtn.remove();
                            var text = picker.querySelector('.picker-text');
                            if (text) text.textContent = 'Choose picture';
                        })
                        .catch(function () {
                            mlAlert('Could not remove the picture. Please try again.', { danger: true });
                        });
                    });
            });
            }
        });

        /* ---------- Cart badge pop when the count changes ---------- */
        var cartBadge = document.getElementById('cart-count');
        if (cartBadge && window.MutationObserver) {
            var last = cartBadge.textContent;
            new MutationObserver(function () {
                if (cartBadge.textContent !== last) {
                    last = cartBadge.textContent;
                    cartBadge.classList.remove('badge-pop');
                    void cartBadge.offsetWidth; // restart animation
                    cartBadge.classList.add('badge-pop');
                }
            }).observe(cartBadge, { childList: true });
        }

        /* ---------- Themed dropdowns for native selects ----------
           Native <select> popups are drawn by the OS (white, square, no
           dark mode, wrong font) — the one thing this theme can't style.
           On fine-pointer devices we mirror every select.form-select into
           a Bootstrap dropdown: the closed control keeps the exact
           .form-select look, the popup uses the themed dropdown-menu.
           The native select stays in the DOM (hidden) so forms, server
           validation and all `change` listeners keep working untouched.
           Touch devices keep the native picker — those are better UX. */
        var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        if (finePointer && window.bootstrap && window.bootstrap.Dropdown) {
            document.querySelectorAll('select.form-select').forEach(function (sel) {
                if (sel.dataset.selectDd || sel.multiple || sel.disabled) return;
                sel.dataset.selectDd = '1';

                var wrap = document.createElement('div');
                wrap.className = 'select-dd';
                // Mirror width-affecting hints (w-auto class + inline styles)
                if (sel.classList.contains('w-auto')) wrap.classList.add('w-auto');
                // Spacing utilities live on the select but must style the wrapper
                Array.prototype.forEach.call(sel.classList, function (c) {
                    if (/^m[tblry]?-\d+$/.test(c)) {
                        wrap.classList.add(c);
                        sel.classList.remove(c);
                    }
                });
                ['width', 'min-width', 'max-width'].forEach(function (prop) {
                    if (sel.style.getPropertyValue(prop)) {
                        wrap.style.setProperty(prop, sel.style.getPropertyValue(prop));
                        sel.style.removeProperty(prop);
                    }
                });
                sel.parentNode.insertBefore(wrap, sel);
                wrap.appendChild(sel);

                // Keep the select for forms/validation, but out of sight + tab order
                sel.classList.add('visually-hidden');
                sel.setAttribute('tabindex', '-1');
                sel.setAttribute('aria-hidden', 'true');

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'form-select select-dd-btn' + (sel.classList.contains('form-select-sm') ? ' form-select-sm' : '');
                if (sel.classList.contains('is-invalid')) btn.classList.add('is-invalid');
                if (sel.classList.contains('is-valid')) btn.classList.add('is-valid');
                btn.setAttribute('data-bs-toggle', 'dropdown');
                btn.setAttribute('aria-haspopup', 'listbox');
                btn.setAttribute('aria-expanded', 'false');
                var label = document.createElement('span');
                label.className = 'select-dd-label';
                btn.appendChild(label);

                var menu = document.createElement('ul');
                menu.className = 'dropdown-menu select-dd-menu';
                menu.setAttribute('role', 'listbox');
                wrap.appendChild(btn);
                wrap.appendChild(menu);

                function syncLabel() {
                    var opt = sel.options[sel.selectedIndex];
                    label.textContent = opt ? opt.textContent.trim() : '';
                    label.classList.toggle('placeholder', !opt || opt.value === '');
                }

                function buildMenu() {
                    menu.innerHTML = '';
                    Array.prototype.forEach.call(sel.options, function (opt) {
                        var li = document.createElement('li');
                        var a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'dropdown-item';
                        a.setAttribute('role', 'option');
                        a.dataset.value = opt.value;
                        a.textContent = opt.textContent.trim();
                        if (opt.disabled) a.disabled = true;
                        li.appendChild(a);
                        menu.appendChild(li);
                    });
                    syncActive();
                }

                function syncActive() {
                    Array.prototype.forEach.call(menu.querySelectorAll('.dropdown-item'), function (a) {
                        var on = a.dataset.value === sel.value;
                        a.classList.toggle('active', on);
                        if (on) a.setAttribute('aria-selected', 'true');
                        else a.removeAttribute('aria-selected');
                    });
                    syncLabel();
                }

                buildMenu();

                // Fixed positioning strategy: escapes overflow:hidden
                // dashboard cards and stays above the sticky header.
                // popperConfig as a FUNCTION keeps Bootstrap's default
                // modifier set (offset/positioning) intact and only turns
                // off flip + horizontal shift — replacing the array outright
                // (as an object would) was what made menus land in the
                // wrong place (bottom-left of the page bug).
                var ddInst = new window.bootstrap.Dropdown(btn, {
                    popperConfig: function (defaultConfig) {
                        defaultConfig.strategy = 'fixed';
                        defaultConfig.modifiers = defaultConfig.modifiers.map(function (m) {
                            if (m.name === 'flip') {
                                return Object.assign({}, m, { enabled: false });
                            }
                            if (m.name === 'preventOverflow') {
                                return Object.assign({}, m, { options: Object.assign({}, m.options, { mainAxis: false }) });
                            }
                            return m;
                        });
                        return defaultConfig;
                    },
                });

                menu.addEventListener('click', function (e) {
                    var a = e.target.closest('.dropdown-item');
                    if (!a || a.disabled) return;
                    if (sel.value !== a.dataset.value) {
                        sel.value = a.dataset.value;
                        // Real change event — autosubmit filters, sort forms
                        // and the stall picker keep working unmodified.
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    syncActive();
                    ddInst.hide();
                    btn.focus();
                });

                // External updates (validation classes, JS-set values)
                sel.addEventListener('change', syncActive);
                if (window.MutationObserver) {
                    new MutationObserver(function () {
                        btn.classList.toggle('is-invalid', sel.classList.contains('is-invalid'));
                        btn.classList.toggle('is-valid', sel.classList.contains('is-valid'));
                        btn.disabled = sel.disabled;
                    }).observe(sel, { attributes: true, attributeFilter: ['class', 'disabled'] });
                }

                btn.addEventListener('shown.bs.dropdown', function () {
                    // Match the field's width exactly (CSS min-width alone
                    // leaves the menu wider than the trigger sometimes).
                    menu.style.minWidth = btn.offsetWidth + 'px';
                    menu.style.width = btn.offsetWidth + 'px';
                    var act = menu.querySelector('.dropdown-item.active');
                    if (act) act.scrollIntoView({ block: 'nearest' });
                });

                if (sel.form) {
                    sel.form.addEventListener('reset', function () {
                        setTimeout(syncActive, 0);
                    });
                }
            });
        }
    });
})();
