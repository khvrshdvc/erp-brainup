@props(['name', 'id' => null])

@error($name)
    <div class="field-error" role="alert" @if ($id) id="{{ $id }}" @endif>{{ $message }}</div>
@enderror
