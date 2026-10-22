@php $editing = isset($branch) && $branch; @endphp

<form method="POST" class="stack-lg"
    action="{{ $editing ? route('admin.branches.update', $branch) : route('admin.branches.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <section class="card card-pad stack">
        <div class="form-grid">
            <div class="field">
                <label for="name">{{ __('Filial nomi *') }}</label>
                <input id="name" type="text" name="name" maxlength="120" required autofocus
                    @class(['input', 'is-invalid' => $errors->has('name')])
                    @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif
                    value="{{ old('name', $branch->name ?? '') }}">
                <x-field-error name="name" id="name-error" />
            </div>

            <div class="field">
                <label for="manager_name">{{ __('Menejer') }}</label>
                <input id="manager_name" type="text" name="manager_name" maxlength="120"
                    @class(['input', 'is-invalid' => $errors->has('manager_name')])
                    @if ($errors->has('manager_name')) aria-invalid="true" aria-describedby="manager_name-error" @endif
                    value="{{ old('manager_name', $branch->manager_name ?? '') }}">
                <x-field-error name="manager_name" id="manager_name-error" />
            </div>
        </div>

        <div class="field">
            <label for="address">{{ __('Manzil') }}</label>
            <input id="address" type="text" name="address" maxlength="255"
                @class(['input', 'is-invalid' => $errors->has('address')])
                @if ($errors->has('address')) aria-invalid="true" aria-describedby="address-error" @endif
                value="{{ old('address', $branch->address ?? '') }}">
            <x-field-error name="address" id="address-error" />
        </div>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(session()->hasOldInput() ? old('is_active') : ($branch->is_active ?? true))>
            {{ __('Filial faol') }}
        </label>
    </section>

    <div class="form-actions">
        <a href="{{ route('admin.branches.index') }}" class="btn btn-lg">{{ __('Bekor qilish') }}</a>
        <button type="submit" class="btn btn-primary btn-lg">{{ __('Saqlash') }}</button>
    </div>
</form>
