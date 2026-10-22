@props(['active', 'label'])

<span class="status-toggle" role="img" aria-label="{{ $label }}">
    <span @class(['switch', 'static', 'on' => $active]) aria-hidden="true"></span>
    <span @class(['status-label', 'on' => $active])>{{ $label }}</span>
</span>
