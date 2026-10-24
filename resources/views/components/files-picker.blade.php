@props([
    'name' => 'files',
    'id' => null,
    'attachments' => collect(),
    'extensions' => \App\Support\Upload::EXTENSIONS,
    'maxMb' => \App\Support\Upload::MAX_MB,
    'maxFiles' => \App\Support\Upload::MAX_SUBMISSION_FILES,
])

@php
    $id ??= $name;
    $types = strtoupper(implode(', ', array_diff($extensions, ['jpeg'])));
    $kept = $attachments->map(fn ($attachment) => [
        'id' => $attachment->id,
        'name' => $attachment->original_name,
        'ext' => pathinfo($attachment->original_name, PATHINFO_EXTENSION),
        'size' => \Illuminate\Support\Number::fileSize($attachment->size, precision: 1),
        'url' => route('attachments.download', $attachment),
    ])->values();
    $errorMessages = collect($errors->get($name))->merge(collect($errors->get($name.'.*'))->flatten())->unique();
@endphp

<div class="field" x-data="filesPicker({ maxMb: {{ (int) $maxMb }}, maxFiles: {{ (int) $maxFiles }}, extensions: @js($extensions), types: @js($types), kept: @js($kept) })">
    <template x-for="item in kept" :key="item.id">
        <div class="file-chip" :class="{ 'is-removed': removed.includes(item.id) }">
            <div class="file-ext" x-text="item.ext"></div>
            <div class="meta">
                <b x-text="item.name"></b>
                <span x-text="item.size"></span>
            </div>
            <a class="btn btn-sm" :href="item.url" x-show="! removed.includes(item.id)">{{ __('Yuklab olish') }}</a>
            <button type="button" class="btn btn-sm" @click="toggleKept(item.id)"
                x-text="removed.includes(item.id) ? @js(__('Qaytarish')) : @js(__('Olib tashlash'))"></button>
            <input type="hidden" name="remove[]" :value="item.id" :disabled="! removed.includes(item.id)">
        </div>
    </template>

    <template x-for="(item, index) in files" :key="item.name + item.size + item.file.lastModified">
        <div class="file-chip">
            <div class="file-ext" x-text="item.ext"></div>
            <div class="meta">
                <b x-text="item.name"></b>
                <span x-text="item.size"></span>
            </div>
            <button type="button" class="btn btn-sm" @click="removeNew(index)">{{ __('Olib tashlash') }}</button>
        </div>
    </template>

    <div @class(['file-input', 'is-invalid' => $errorMessages->isNotEmpty()])
        :class="{ drag: dragging, 'is-invalid': error }"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)">

        <input x-ref="input" id="{{ $id }}" type="file" name="{{ $name }}[]" class="sr-only" multiple
            accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}"
            @change="pick($event)" :required="total === 0">

        <svg class="file-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 11.5l-8.6 8.6a5 5 0 01-7.1-7.1l8.6-8.6a3.3 3.3 0 014.7 4.7l-8.6 8.6a1.7 1.7 0 01-2.4-2.4l7.9-7.9"/>
        </svg>

        <label for="{{ $id }}" class="file-input-text">
            <span x-show="total === 0">{{ __('Fayl tanlanmagan') }}</span>
            <span x-show="total > 0" x-cloak x-text="@js(__('Fayllar: :count / :max')).replace(':count', total).replace(':max', {{ (int) $maxFiles }})"></span>
        </label>

        <label for="{{ $id }}" class="btn btn-sm">{{ __('Fayl qo‘shish') }}</label>
    </div>

    <div class="field-hint">{{ __(':types · har biri :maxmb MB gacha · ko‘pi bilan :max ta fayl · bir nechta rasm yoki faylni tanlash yoki sudrab tashlash mumkin', ['types' => $types, 'maxmb' => $maxMb, 'max' => $maxFiles]) }}</div>
    <div class="field-error" x-show="error" x-cloak x-text="error" role="alert"></div>
    @foreach ($errorMessages as $message)
        <div class="field-error" role="alert">{{ $message }}</div>
    @endforeach
</div>
