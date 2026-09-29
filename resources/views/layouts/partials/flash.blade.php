{{-- Custom MarketLink flash alerts (ml-alert) — not Bootstrap alerts.
     Rendered as toasts in a fixed stack (top-right); each carries a
     countdown bar, pause-on-hover and a themed close button. --}}
@php
    $flashItems = collect()
        ->when(session('success'), fn ($c, $msg) => $c->push(['type' => 'success', 'title' => 'All good!', 'msg' => $msg]))
        ->when(session('error'), fn ($c, $msg) => $c->push(['type' => 'error', 'title' => 'Something went wrong', 'msg' => $msg]));
@endphp

@if ($flashItems->isNotEmpty())
    <div class="ml-alert-stack" aria-live="polite">
        @foreach ($flashItems as $flash)
            <div class="ml-alert {{ $flash['type'] }}" role="status" data-ml-alert data-ttl="4500">
                <div class="ml-alert-icon">
                    <i class="bi {{ $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                </div>
                <div class="ml-alert-body">
                    <div class="ml-alert-title">{{ $flash['title'] }}</div>
                    <div class="ml-alert-msg">{{ $flash['msg'] }}</div>
                </div>
                <button type="button" class="ml-alert-close" aria-label="Dismiss">
                    <i class="bi bi-x-lg"></i>
                </button>
                <span class="ml-alert-progress" aria-hidden="true"></span>
            </div>
        @endforeach
    </div>
@endif

@if (isset($errors) && $errors->any() && ! request()->routeIs('login', 'register', 'password.request', 'password.reset', 'checkout.index', 'contact'))
    {{-- Validation errors are rendered once inside each form via <x-form-errors />.
         This catch-all only covers routes whose blade has no form summary.
         Errors stay until dismissed (no countdown) — users need time to read. --}}
    <div class="ml-alert-stack" aria-live="assertive">
        <div class="ml-alert error" role="alert" data-ml-alert>
            <div class="ml-alert-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="ml-alert-body">
                <div class="ml-alert-title">Please fix the following</div>
                <ul class="ml-alert-msg mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="ml-alert-close" aria-label="Dismiss">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
@endif
