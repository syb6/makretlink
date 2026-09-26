@php
    // Accepts: $product (required), $class (optional), $alt (optional), $eager (optional)
    $imgSrc = $product->image_url;
    $imgClass = $class ?? 'product-thumb';
    $imgAlt = $alt ?? $product->name;
    $imgGeneral = asset('images/placeholders/general.svg');
    $eager = $eager ?? false;
@endphp
<img src="{{ $imgSrc }}"
     class="{{ $imgClass }}"
     alt="{{ $imgAlt }}"
     @if (!empty($style)) style="{{ $style }}" @endif
     @unless($eager) loading="lazy" @endunless
     data-fallback="{{ $imgGeneral }}"
     onerror="this.onerror=null; this.src=this.dataset.fallback;">
