@props(['attachment'])

<div class="file-chip">
    <div class="file-ext">{{ pathinfo($attachment->original_name, PATHINFO_EXTENSION) }}</div>
    <div class="meta">
        <b>{{ $attachment->original_name }}</b>
        <span>{{ \Illuminate\Support\Number::fileSize($attachment->size, precision: 1) }}</span>
    </div>
    <a class="btn btn-sm" href="{{ route('attachments.download', $attachment) }}">{{ __('Yuklab olish') }}</a>
</div>
