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
                .catch(function () { toast('Could not add to cart.', false); })
                .finally(function () { btn.disabled = false; });
        });
    });

    // Favorite product toggle on product page
    var favBtn = document.querySelector('.favorite-product-btn');
    if (favBtn) {
        favBtn.addEventListener('click', function () {
            fetch(favBtn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ product_id: favBtn.dataset.productId }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var icon = favBtn.querySelector('i');
                    if (data.state === 'added') {
                        icon.className = 'bi bi-heart-fill';
                        toast('Saved to favorites ♥', true);
                    } else {
                        icon.className = 'bi bi-heart';
                        toast('Removed from favorites.', true);
                    }
                })
                .catch(function () { toast('Action failed.', false); });
        });
    }
});
