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
                <input id="name" class="input" type="text" name="name" maxlength="120" required
                    value="{{ old('name', $branch->name ?? '') }}">
            </div>

            <div class="field">
                <label for="manager_name">{{ __('Menejer') }}</label>
                <input id="manager_name" class="input" type="text" name="manager_name" maxlength="120"
                    value="{{ old('manager_name', $branch->manager_name ?? '') }}">
            </div>
        </div>

        <div class="field">
            <label for="address">{{ __('Manzil') }}</label>
            <input id="address" class="input" type="text" name="address" maxlength="255"
                value="{{ old('address', $branch->address ?? '') }}">
        </div>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active ?? true))>
            {{ __('Filial faol') }}
        </label>
    </section>

    <div class="form-actions">
        <a href="{{ route('admin.branches.index') }}" class="btn btn-lg">{{ __('Bekor qilish') }}</a>
        <button type="submit" class="btn btn-primary btn-lg">{{ __('Saqlash') }}</button>
    </div>
</form>
