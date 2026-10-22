@props(['name'])

@error($name)
    <div class="field-error" role="alert">{{ $message }}</div>
@enderror
