@php
    $editing = isset($teacher) && $teacher;
    $canEditCredentials = $canEditCredentials ?? true;
    $selected = collect(old('branches', $editing ? $teacher->branches->pluck('id')->all() : [$defaultBranchId]))
        ->filter()->map(fn ($id) => (int) $id)->all();
@endphp

<form method="POST" class="stack-lg"
    action="{{ $editing ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <section class="card card-pad stack">
        <h2 class="card-title">Shaxsiy ma‘lumotlar</h2>

        <div class="form-grid">
            <div class="field">
                <label for="name">To‘liq ism *</label>
                <input id="name" class="input" type="text" name="name" maxlength="120" required
                    value="{{ old('name', $teacher->name ?? '') }}">
            </div>

            @if ($canEditCredentials)
                <div class="field">
                    <label for="email">Email *</label>
                    <input id="email" class="input" type="email" name="email" maxlength="255" required autocomplete="off"
                        value="{{ old('email', $teacher->email ?? '') }}">
                </div>
            @endif

            <div class="field">
                <label for="phone">Telefon</label>
                <input id="phone" class="input" type="text" name="phone" maxlength="20" placeholder="+998 90 123 45 67"
                    value="{{ old('phone', $teacher->phone ?? '') }}">
            </div>

            @if ($canEditCredentials)
                <div class="field">
                    <label for="password">Parol {{ $editing ? '' : '*' }}</label>
                    <input id="password" class="input" type="password" name="password" minlength="8" autocomplete="new-password"
                        {{ $editing ? '' : 'required' }}>
                    <div class="field-hint">{{ $editing ? 'O‘zgartirmasangiz, bo‘sh qoldiring' : 'Kamida 8 ta belgi' }}</div>
                </div>
            @endif
        </div>

        @unless ($canEditCredentials)
            <div class="field-hint">Bu o‘qituvchi boshqa filiallarda ham ishlaydi, shuning uchun email va parolni faqat super admin o‘zgartira oladi.</div>
        @endunless
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">Filiallar *</h2>
        <div class="field-hint">O‘qituvchi bir nechta filialda dars bera oladi</div>

        <div class="check-row">
            @foreach ($branches as $branch)
                <label class="check">
                    <input type="checkbox" name="branches[]" value="{{ $branch->id }}" @checked(in_array($branch->id, $selected, true))>
                    {{ $branch->name }}
                </label>
            @endforeach
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-lg">Bekor qilish</a>
        <button type="submit" class="btn btn-primary btn-lg">Saqlash</button>
    </div>
</form>
