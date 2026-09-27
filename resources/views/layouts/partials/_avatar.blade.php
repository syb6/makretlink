@php
    // Accepts: $user (required), $size (px, default 32), $class (extra classes)
    $size = $size ?? 32;
    $class = $class ?? '';
    $url = $user->avatarUrl();
@endphp
@if ($url)
    <img src="{{ $url }}" alt="{{ $user->name }}" width="{{ $size }}" height="{{ $size }}"
         class="avatar-img {{ $class }}" loading="lazy"
         onerror="this.outerHTML='<span class=&quot;avatar-fallback {{ $class }}&quot; style=&quot;width:{{ $size }}px;height:{{ $size }}px;font-size:{{ max(11, round($size * 0.38)) }}px&quot;>{{ $user->initials() }}</span>'">
@else
    <span class="avatar-fallback {{ $class }}" style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ max(11, round($size * 0.38)) }}px">{{ $user->initials() }}</span>
@endif
