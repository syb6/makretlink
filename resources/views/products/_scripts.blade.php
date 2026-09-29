{{-- Products page scripts: filter auto-submit, sort preservation, debounce --}}
<script>
    (function () {
        'use strict';

        /* ---------- Sort select: submit + keep spinner-free ---------- */
        var sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', function () {
                sortSelect.form.submit();
            });
        }

        /* ---------- Filter selects: auto-submit on change ---------- */
        document.querySelectorAll('#productFilters select[data-autosubmit]').forEach(function (sel) {
            sel.addEventListener('change', function () {
                sel.form.submit();
            });
        });

        /* ---------- Search: debounced auto-submit (desktop) ---------- */
        // Only when a pointer is fine (desktop) — on touch keyboards the
        // debounce would fire mid-typing; there the Go button remains.
        var qInput = document.getElementById('filterQ');
        var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        if (qInput && finePointer) {
            var timer = null;
            qInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { qInput.form.submit(); }, 650);
            });
        }

        /* ---------- Min/max sanity: no inverted price range ---------- */
        var minEl = document.getElementById('filterMin');
        var maxEl = document.getElementById('filterMax');
        if (minEl && maxEl) {
            var syncClamp = function (source, target) {
                var s = parseFloat(source.value);
                var t = parseFloat(target.value);
                if (!isNaN(s) && !isNaN(t) && s > t) {
                    target.value = source.value;
                }
            };
            minEl.addEventListener('change', function () { syncClamp(minEl, maxEl); });
            maxEl.addEventListener('change', function () { syncClamp(maxEl, minEl); });
        }
    })();
</script>
