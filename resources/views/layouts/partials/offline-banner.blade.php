{{-- Global offline/online notice: shown when the browser loses internet.
     JS (app.js) toggles it via the `show` class; no server round-trip. --}}
<div id="offlineBanner" class="offline-banner" role="status" aria-live="polite">
    <i class="bi bi-wifi-off" aria-hidden="true"></i>
    <span data-offline-msg>You appear to be offline — actions won't go through until you reconnect.</span>
    <span data-online-msg class="d-none"><i class="bi bi-check-circle me-1"></i>Back online — you're good to go.</span>
</div>
