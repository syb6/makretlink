@php
    // Accepts: $product (required unless $categoryImage), $class, $alt, $eager, $style
    //          $categoryImage + $category renders a representative category tile.
    $imgGeneral = asset('images/placeholders/general.svg');

    $categoryKeyMap = [
        'vegetables'      => 'vegetables',
        'fruits'          => 'fruits',
        'dairy-eggs'      => 'dairy-eggs',
        'baked-goods'     => 'baked-goods',
        'herbs-greens'    => 'herbs-greens',
        'honey-preserves' => 'honey-preserves',
    ];

    if (! empty($categoryImage) && ! empty($category)) {
        // Admin-uploaded category photo wins; otherwise the themed placeholder.
        $imgSrc = $category->image
            ? asset('storage/'.$category->image)
            : asset('images/placeholders/'.($categoryKeyMap[$category->slug] ?? 'general').'.svg');
        $imgAlt = $alt ?? $category->name;
    } else {
        $imgSrc = $product->image_url;
        $imgAlt = $alt ?? $product->name;
    }

    $imgClass = $class ?? 'product-thumb';
    $eager = $eager ?? false;
@endphp
<img src="{{ $imgSrc }}"
     class="{{ $imgClass }}"
     alt="{{ $imgAlt }}"
     @if (!empty($style)) style="{{ $style }}" @endif
     @unless($eager) loading="lazy" @endunless
     data-fallback="{{ $imgGeneral }}"
     onerror="this.onerror=null; this.src=this.dataset.fallback;">
