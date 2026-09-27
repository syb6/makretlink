/* MarketLink front-end helpers (plain JS, no build step)
   1) Theme (dark mode) — applied before paint
   2) Core UI behaviours — flash, confirms, navs, back-to-top
   3) Motion layer — scroll reveals, progress, ripples, count-up       */
(function () {
    'use strict';

    /* =========================================================
       1) Theme (dark mode) toggle
       ========================================================= */
    var THEME_KEY = 'ml-theme';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        var icon = document.getElementById('themeIcon');
        if (icon) {
            // Moon shown in light mode (click -> dark), sun in dark mode
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    applyTheme(document.documentElement.getAttribute('data-bs-theme') || 'light');

    /* =========================================================
       2) + 3) DOM-ready behaviours
       ========================================================= */
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('DOMContentLoaded', function () {

        /* ---------- Theme toggle ---------- */
        var themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                localStorage.setItem(THEME_KEY, next);
                applyTheme(next);
            });
        }

        /* ---------- Auto-hide flash alerts ---------- */
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
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    e.preventDefault();
                }
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

        document.documentElement.classList.add('js-anim');

        /* ---------- Scroll-reveal via IntersectionObserver ---------- */
        var revealSelector = [
            '.stat-card', '.product-card', '.market-card', '.card-ml',
            '.dashboard-card', '.feature-card', '.step-card', '.category-card',
            '.order-item', '.metric-box', '.auth-card'
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
            if (img.loading || img.fetchPriority === 'high') return;
            var r = img.getBoundingClientRect();
            if (r.top > window.innerHeight && r.height >= 40) {
                img.loading = 'lazy';
            }
        });

        /* ---------- Page-leave skeleton (products & markets grids) ---------- */
        // Those grids paint slowly on first load, so show a skeleton page
        // as soon as the user follows a link to them.
        var GRIDS = ['/products', '/markets'];
        var overlay = null;

        var buildSkeleton = function (kind) {
            var el = document.createElement('div');
            el.className = 'skeleton-overlay';
            el.setAttribute('aria-hidden', 'true');
            var label = kind === '/markets' ? 'Finding local markets' : 'Gathering fresh products';
            var html = '<div class="skeleton-inner">' +
                '<div class="skeleton-head"><span class="skeleton-dot"></span><span class="skeleton-dot" style="animation-delay:.15s"></span><span class="skeleton-dot" style="animation-delay:.3s"></span>' +
                '<span style="margin-left:6px">' + label + '\u2026</span></div>' +
                '<div class="skeleton-grid">';
            for (var i = 0; i < 8; i++) {
                html += '<div class="skeleton-card" style="--i:' + i + '">' +
                    '<div class="skeleton-block skeleton-img"></div>' +
                    '<div class="skeleton-line w60"></div>' +
                    '<div class="skeleton-line"></div>' +
                    '<div class="skeleton-line w40"></div>' +
                    '</div>';
            }
            html += '</div></div>';
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
                    if (!url || !window.confirm('Remove this picture?')) return;
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
                            window.alert('Could not remove the picture. Please try again.');
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
    });
})();
