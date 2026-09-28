/* Add-to-cart + favorites AJAX handlers */
document.addEventListener('DOMContentLoaded', function () {
    var csrf = document.querySelector('meta[name="csrf-token"]');

    function toast(message, ok) {
        var wrap = document.createElement('div');
        wrap.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger') + ' position-fixed shadow';
        wrap.style.cssText = 'top:80px;right:24px;z-index:2000;min-width:260px';
        wrap.textContent = message;
        document.body.appendChild(wrap);
        setTimeout(function () { wrap.remove(); }, 3000);
    }

    // Add to cart (product cards + product page)
    document.querySelectorAll('.add-to-cart-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!btn.dataset.stockId) { toast('No stock available right now.', false); return; }

            var qtyInput = document.getElementById('qtyInput');
            var qty = qtyInput ? qtyInput.value : 1;

            btn.disabled = true;
            fetch(btn.dataset.url || document.querySelector('meta[name="cart-add-url"]')?.content || window.cartAddUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ stock_id: btn.dataset.stockId, quantity: qty }),
            })
                .then(function (r) { return r.json().catch(function () { throw new Error('failed'); }); })
                .then(function (data) {
                    if (data.ok) {
                        toast('Added to cart ✓', true);
                        var badge = document.getElementById('cart-count');
                        if (badge && data.count !== undefined) {
                            badge.textContent = data.count;
                            badge.classList.remove('d-none');
                        }
                    } else {
                        toast(data.message || 'Could not add to cart.', false);
                    }
                })
                .catch(function () {
                    (window.mlAlert || function (m) { window.alert(m); })(
                        navigator.onLine
                            ? 'Could not add to cart — the server did not respond. Check your connection and try again.'
                            : 'You appear to be offline. Reconnect and try again.',
                        { danger: true }
                    );
                })
                .finally(function () { btn.disabled = false; });
        });
    });

    // Favorite product toggle on product page
    // Favorite toggles: products, farmers and markets all share one handler.
    // Each button carries data-url plus data-product-id / data-farmer-id /
    // data-market-id; the matching *_id key is sent to the server.
    document.querySelectorAll('.favorite-product-btn, .favorite-farmer-btn, .favorite-market-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var payload = {};
            ['productId', 'farmerId', 'marketId'].forEach(function (key) {
                if (btn.dataset[key] !== undefined) {
                    payload[key.replace(/[A-Z]/g, function (m) { return '_' + m.toLowerCase(); })] = btn.dataset[key];
                }
            });

            fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var icon = btn.querySelector('i');
                    if (data.state === 'added') {
                        if (icon) { icon.className = 'bi bi-heart-fill'; }
                        toast('Saved to favorites ♥', true);
                    } else {
                        if (icon) { icon.className = 'bi bi-heart'; }
                        toast('Removed from favorites.', true);
                    }
                })
                .catch(function () {
                    (window.mlAlert || function (m) { window.alert(m); })(
                        navigator.onLine
                            ? 'That action did not go through — the server did not respond. Please try again.'
                            : 'You appear to be offline. Reconnect and try again.',
                        { danger: true }
                    );
                });
        });
    });
});
