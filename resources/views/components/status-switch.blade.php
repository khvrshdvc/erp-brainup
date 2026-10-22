@props(['action', 'active', 'label'])

<form method="POST" action="{{ $action }}" class="status-toggle">
    @csrf
    @method('PATCH')
    <button type="submit" @class(['switch', 'on' => $active]) role="switch" aria-checked="{{ $active ? 'true' : 'false' }}"
        title="{{ $active ? 'Faolsizlantirish' : 'Faollashtirish' }}"></button>
    <span @class(['status-label', 'on' => $active])>{{ $label }}</span>
</form>
