@php
    // One compact summarized alert: WHAT is wrong, WHY, HOW to fix.
    // Accepts: $errors (bag, auto), optional $friendly = [
    //   'field' => ['why' => ..., 'fix' => ...], ...  ] overrides.
    $friendly = $friendly ?? [];

    // Format hints only make sense for genuine FORMAT failures. Auth failures
    // are keyed on 'email' too (bad credentials, deactivated account) — the
    // address itself is fine, so those messages must not get a hint attached.
    $authMessages = [
        'Invalid email or password.',
        'Your account has been deactivated. Please contact the administrator.',
    ];

    $copy = [
        'name' => ['fix' => 'Use letters and spaces, up to 120 characters — e.g. “Ayesha Khan”.'],
        'business_name' => ['fix' => 'Give your stall a clear name, up to 150 characters — e.g. “Green Valley Farms”.'],
        'contact_person' => ['fix' => 'Tell us who customers should ask for, up to 120 characters.'],
        'email' => ['fix' => 'Use a real format like name@example.com — this is how you log in.'],
        'phone' => ['fix' => 'Digits (and + or -) only, up to 30 characters — e.g. +92 300 1234567.'],
        'address' => ['fix' => 'Write where you are located (max 1000 characters) so people can find you.'],
        'password' => ['fix' => 'Use at least 8 characters; both password boxes must match exactly.'],
        'password_confirmation' => ['fix' => 'Type the same password in both boxes — watch for caps lock.'],
        'price' => ['fix' => 'Enter a positive amount in Rs, like 120.00 — no letters or symbols.'],
        'unit' => ['fix' => 'How you sell it: kg, dozen, bunch, pc… (max 30 characters).'],
        'quantity' => ['fix' => 'Enter how much stock you have, as a positive number.'],
        'category_id' => ['fix' => 'Pick a category from the list.'],
        'image' => ['fix' => 'Use a JPG, PNG or WebP image under 2 MB.'],
        'profile_image' => ['fix' => 'Use a JPG, PNG or WebP image under 2 MB.'],
        'profile_photo' => ['fix' => 'Use a JPG, PNG or WebP image under 2 MB.'],
        'message' => ['fix' => 'Write your question (a few words up to ~1000 characters).'],
        'role' => ['fix' => 'Choose whether you are joining as a Customer or a Farmer.'],
        'slots' => ['fix' => 'Pick a pickup slot for every stall in your order.'],
        'pickup_date' => ['fix' => 'Choose one of the available pickup windows.'],
        'title' => ['fix' => 'A short headline, up to 150 characters.'],
        'subject' => ['fix' => 'A short subject, up to 150 characters.'],
        'reply' => ['fix' => 'Write a short reply (a few words up to ~500 characters).'],
    ];

    $allErrors = $errors->all();
@endphp
@if ($allErrors)
    <div class="form-error-box" role="alert" tabindex="-1">
        <div class="form-error-head">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>{{ count($allErrors) }} thing{{ count($allErrors) > 1 ? 's' : '' }} need{{ count($allErrors) > 1 ? '' : 's' }} fixing before you continue</strong>
            </div>
        </div>
        <ul class="form-error-list">
            @foreach ($allErrors as $i => $msg)
                @php
                    $key = collect($errors->keys())->get($i);
                    $fix = in_array($msg, $authMessages, true)
                        ? null
                        : ($friendly[$key]['fix'] ?? ($copy[$key]['fix'] ?? null));
                @endphp
                <li>
                    <span class="form-error-msg">{{ $msg }}</span>
                    @if ($fix)
                        — <span class="form-error-fix">{{ $fix }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
