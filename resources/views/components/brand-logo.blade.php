@props(['variant' => 'white'])

{{-- The logo is inlined (a 7 KB WebP) so it is part of the page itself: the development server sends no cache
     headers, and a separately loaded image popped in a moment after every page change. --}}
<img src="data:image/webp;base64,{{ base64_encode(file_get_contents(public_path('images/logo-brainup-'.$variant.'.webp'))) }}"
    alt="BrainUp Academy" width="180" height="64" decoding="sync"
    {{ $attributes->class(['erp-brand-logo']) }}>
