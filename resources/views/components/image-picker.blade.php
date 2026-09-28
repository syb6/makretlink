@php
    $id = $id ?? $name;
    $label = $label ?? 'Picture';
    $hint = $hint ?? 'JPG, PNG or WebP · max 2 MB · square works best';
    $placeholder = $placeholder ?? 'bi-image';
    $existing = $existing ?? null;
    // $type (product | category | market) resolves the stored image path
    // through ImageLibrary — the same resolver the models use on the UI.
    $type = $type ?? null;
    $src = $existing
        ? ($type
            ? \App\Services\ImageLibrary::url($type, $existing)
            : (str_starts_with($existing, 'http') || str_starts_with($existing, asset('')) ? $existing : asset('storage/' . $existing)))
        : null;
@endphp
<div class="image-picker" data-image-picker>
    <input type="file" name="{{ $name }}" id="{{ $id }}" class="image-picker-input"
           accept="image/png,image/jpeg,image/webp" aria-label="{{ $label }}">

    <button type="button" class="image-picker-tile {{ $src ? 'has-image' : '' }}"
            aria-label="{{ $src ? 'Change ' : 'Choose ' }}{{ $label }}"
            data-picker-tile>
        <span class="image-picker-preview">
            @if ($src)
                <img src="{{ $src }}" alt="" loading="lazy">
            @endif
        </span>
        <span class="image-picker-overlay">
            <i class="bi {{ $placeholder }} picker-empty-icon"></i>
            <i class="bi bi-camera-fill picker-change-icon"></i>
            <span class="picker-text">{{ $src ? 'Change picture' : 'Choose picture' }}</span>
        </span>
    </button>

    <div class="image-picker-meta">
        <span class="picker-hint"><i class="bi bi-info-circle me-1"></i>{{ $hint }}</span>
        @if ($src && !empty($removeUrl))
            <button type="button" class="picker-remove" data-picker-remove data-url="{{ $removeUrl }}">
                <i class="bi bi-trash3 me-1"></i>Remove
            </button>
        @endif
    </div>
</div>
