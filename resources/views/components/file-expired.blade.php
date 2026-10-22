@props(['submission'])

@if ($submission->filesExpired())
    <div class="card-sub">{{ __('Fayl saqlash muddati tugagani uchun o‘chirilgan') }}</div>
@endif
