@props(['submission'])

@if ($submission->filesExpired())
    <div class="card-sub">Fayl saqlash muddati tugagani uchun o‘chirilgan</div>
@endif
