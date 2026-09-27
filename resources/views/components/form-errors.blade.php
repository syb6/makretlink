@php
    // Friendly validation errors.
    // Accepts: $errors (bag, auto), optional $friendly = [
    //   'field' => ['why' => ..., 'fix' => ...], ...  ] overrides.
    $friendly = $friendly ?? [];

    $copy = [
        'name' => ['why' => 'the name field needs your attention', 'fix' => 'Use letters and spaces only, up to 120 characters — e.g. “Ayesha Khan”.'],
        'business_name' => ['why' => 'the stall name needs your attention', 'fix' => 'Give your stall a clear name, up to 150 characters — e.g. “Green Valley Farms”.'],
        'contact_person' => ['why' => 'the contact person is missing or too long', 'fix' => 'Tell us who customers should ask for, up to 120 characters.'],
        'email' => ['why' => 'the email address is missing or not valid', 'fix' => 'Use a real format like name@example.com — this is how you log in.'],
        'phone' => ['why' => 'the phone number needs your attention', 'fix' => 'Enter digits (and + or - if needed), up to 30 characters — e.g. +92 300 1234567.'],
        'address' => ['why' => 'the address is missing or too long', 'fix' => 'Write where you are located (max 1000 characters) so people can find you.'],
        'password' => ['why' => 'the password does not meet the rules', 'fix' => 'Use at least 8 characters. Both password boxes must match exactly.'],
        'password_confirmation' => ['why' => 'the two password boxes do not match', 'fix' => 'Type the same password in both boxes — watch for typos and caps lock.'],
        'price' => ['why' => 'the price is missing or not a valid number', 'fix' => 'Enter a positive amount in Rs, like 120.00 — no letters or symbols.'],
        'unit' => ['why' => 'the selling unit is missing', 'fix' => 'Tell us how you sell it: kg, dozen, bunch, pc… (max 30 characters).'],
        'quantity' => ['why' => 'the quantity needs your attention', 'fix' => 'Enter how much stock you have, as a positive number.'],
        'category_id' => ['why' => 'the selected category does not exist', 'fix' => 'Pick a category from the list, or leave it as Uncategorized.'],
        'image' => ['why' => 'the picture could not be accepted', 'fix' => 'Use a JPG, PNG or WebP image under 2 MB. Try a smaller or different photo.'],
        'profile_image' => ['why' => 'the profile picture could not be accepted', 'fix' => 'Use a JPG, PNG or WebP image under 2 MB. Try a smaller or different photo.'],
        'profile_photo' => ['why' => 'the profile picture could not be accepted', 'fix' => 'Use a JPG, PNG or WebP image under 2 MB. Try a smaller or different photo.'],
        'message' => ['why' => 'your message needs your attention', 'fix' => 'Write your question (a few words up to ~1000 characters).'],
        'role' => ['why' => 'the account type is missing', 'fix' => 'Choose whether you are joining as a Customer or a Farmer.'],
    ];

    $allErrors = $errors->all();
@endphp
@if ($allErrors)
    <div class="form-error-box" role="alert" tabindex="-1">
        <div class="form-error-head">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>{{ count($allErrors) }} thing{{ count($allErrors) > 1 ? 's' : '' }} need{{ count($allErrors) > 1 ? '' : 's' }} fixing before you continue</strong>
                <div class="small">Here is what happened and how to fix each one:</div>
            </div>
        </div>
        <ul class="form-error-list">
            @foreach ($allErrors as $i => $msg)
                @php
                    $key = collect($errors->keys())->get($i);
                    $tip = $friendly[$key]['fix'] ?? ($copy[$key]['fix'] ?? null);
                @endphp
                <li>
                    <span class="form-error-msg">{{ $msg }}</span>
                    @if ($tip)
                        <span class="form-error-fix"><i class="bi bi-lightbulb me-1"></i>{{ $tip }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
