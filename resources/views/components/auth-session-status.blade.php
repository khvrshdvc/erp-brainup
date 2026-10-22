@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-ok']) }} role="status">
        {{ $status }}
    </div>
@endif
