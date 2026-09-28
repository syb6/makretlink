@php
    // Accepts: $product (required unless $categoryImage), $category, $class, $alt, $eager, $style
    // Image resolution lives on the models: Product::image_url / Category::image_url
    // already walk upload -> curated library photo -> themed placeholder.
    if (! empty($categoryImage) && ! empty($category)) {
        $imgSrc = $category->image_url;
        $imgAlt = $alt ?? $category->name;
    } else {
        $imgSrc = $product->image_url;
        $imgAlt = $alt ?? $product->name;
    }

    $imgClass = $class ?? 'product-thumb';
    $eager = $eager ?? false;
@endphp
<img src="{{ $imgSrc }}"
     class="{{ $imgClass }} card-img"
     alt="{{ $imgAlt }}"
     width="600" height="400"
     @if (!empty($style)) style="{{ $style }}" @endif
     @unless($eager) loading="lazy" @endunless
     decoding="async"
     onload="this.classList.add('is-loaded');">
