@php $editing = isset($student) && $student; @endphp

<form method="POST" class="stack-lg"
    action="{{ $editing ? route('admin.students.update', $student) : route('admin.students.store') }}">
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
                    value="{{ old('name', $student->name ?? '') }}">
            </div>

            <div class="field">
                <label for="email">Email *</label>
                <input id="email" class="input" type="email" name="email" maxlength="255" required autocomplete="off"
                    value="{{ old('email', $student->email ?? '') }}">
            </div>

            <div class="field">
                <label for="phone">Telefon</label>
                <input id="phone" class="input" type="text" name="phone" maxlength="20" placeholder="+998 90 123 45 67"
                    value="{{ old('phone', $student->phone ?? '') }}">
            </div>

            <div class="field">
                <label for="birth_date">Tug‘ilgan sana</label>
                <input id="birth_date" class="input" type="date" name="birth_date"
                    value="{{ old('birth_date', optional($student->birth_date ?? null)->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="password">Parol {{ $editing ? '' : '*' }}</label>
                <input id="password" class="input" type="password" name="password" minlength="8" autocomplete="new-password"
                    {{ $editing ? '' : 'required' }}>
                <div class="field-hint">{{ $editing ? 'O‘zgartirmasangiz, bo‘sh qoldiring' : 'Kamida 8 ta belgi' }}</div>
            </div>
        </div>
    </section>

    @unless ($editing)
        <section class="card card-pad stack">
            <h2 class="card-title">Filial va guruh</h2>

            <div class="form-grid">
                @if ($branches->count() > 1)
                    <div class="field">
                        <label for="branch_id">Filial *</label>
                        <select id="branch_id" name="branch_id" class="select" required>
                            <option value="">Filialni tanlang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $defaultBranchId) === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
                @endif

                <div class="field">
                    <label for="group_id">Guruh</label>
                    <select id="group_id" name="group_id" class="select">
                        <option value="">Keyinroq biriktiriladi</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) old('group_id') === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <div class="field-hint">Guruh tanlangan filialniki bo‘lishi kerak</div>
                </div>
            </div>
        </section>
    @endunless

    <div class="form-actions">
        <a href="{{ $editing ? route('admin.students.show', $student) : route('admin.students.index') }}" class="btn btn-lg">Bekor qilish</a>
        <button type="submit" class="btn btn-primary btn-lg">Saqlash</button>
    </div>
</form>
