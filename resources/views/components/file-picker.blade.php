@props([
    'name',
    'id' => null,
    'required' => false,
    'extensions' => \App\Support\Upload::EXTENSIONS,
    'maxMb' => \App\Support\Upload::MAX_MB,
])

@php
    $id ??= $name;
    $types = strtoupper(implode(', ', array_diff($extensions, ['jpeg'])));
@endphp

<div class="field" x-data="filePicker({ maxMb: {{ (int) $maxMb }}, extensions: @js($extensions), types: @js($types) })">
    <div @class(['file-input', 'is-invalid' => $errors->has($name)])
        :class="{ drag: dragging, picked: file, 'is-invalid': error }"
        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)">

        <input x-ref="input" id="{{ $id }}" type="file" name="{{ $name }}" class="sr-only"
            accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}"
            @change="pick($event.target.files[0])" @required($required)>

        <svg class="file-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 11.5l-8.6 8.6a5 5 0 01-7.1-7.1l8.6-8.6a3.3 3.3 0 014.7 4.7l-8.6 8.6a1.7 1.7 0 01-2.4-2.4l7.9-7.9"/>
        </svg>

        <label for="{{ $id }}" class="file-input-text">
            <span x-show="! file">{{ $slot->isEmpty() ? 'Fayl tanlanmagan' : $slot }}</span>
            <span x-show="file" x-cloak><b x-text="file?.name"></b> <small x-text="file?.size"></small></span>
        </label>

        <button type="button" class="file-input-clear" x-show="file" x-cloak @click="clear()" aria-label="Faylni olib tashlash">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        <label for="{{ $id }}" class="btn btn-sm" x-text="file ? 'Almashtirish' : 'Tanlash'">Tanlash</label>
    </div>

    <div class="field-hint">{{ $types }} · {{ $maxMb }} MB gacha · faylni shu qatorga sudrab tashlash ham mumkin</div>
    <div class="field-error" x-show="error" x-cloak x-text="error" role="alert"></div>
    <x-field-error :name="$name" />
</div>
