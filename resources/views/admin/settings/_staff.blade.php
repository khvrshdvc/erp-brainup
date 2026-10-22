<section class="card card-pad stack">
    <div class="card-head" style="padding: 0; border: 0">
        <div>
            <h2 class="card-title">{{ $title }}</h2>
            @isset($hint)
                <div class="card-sub">{{ $hint }}</div>
            @endisset
        </div>
    </div>

    @forelse ($people as $person)
        @php
            $bag = $errors->getBag('staff_'.$person->id);
            $canEditCredentials = ! $person->isTeacher() || auth()->user()->can('manageTeacherCredentials', $person);
            $field = fn (string $key, $value) => $bag->any() ? old($key, $value) : $value;
        @endphp

        <details id="staff-{{ $person->id }}" class="card" style="padding: 12px 16px" {{ $bag->any() ? 'open' : '' }}>
            <summary style="cursor: pointer; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: center">
                <b>{{ $person->name }}</b>
                <span class="card-sub">
                    {{ $person->isTeacher() ? $person->branches->pluck('name')->implode(', ') : $person->branch?->name }}
                    · {{ $person->phone ?: $person->email }}
                </span>
            </summary>

            <form method="POST" action="{{ route('admin.settings.staff', $person) }}" class="stack" style="margin-top: 16px">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label for="staff-{{ $person->id }}-name">{{ __('Ism va familiya *') }}</label>
                        <input id="staff-{{ $person->id }}-name" class="input" type="text" name="name" maxlength="120" required
                            value="{{ $field('name', $person->name) }}">
                        @if ($bag->has('name'))<div class="field-hint" style="color: var(--err)">{{ $bag->first('name') }}</div>@endif
                    </div>

                    <div class="field">
                        <label for="staff-{{ $person->id }}-phone">{{ __('Telefon') }}</label>
                        <input id="staff-{{ $person->id }}-phone" class="input" type="text" name="phone" maxlength="20"
                            value="{{ $field('phone', $person->phone) }}">
                        @if ($bag->has('phone'))<div class="field-hint" style="color: var(--err)">{{ $bag->first('phone') }}</div>@endif
                    </div>

                    @if ($canEditCredentials)
                        <div class="field">
                            <label for="staff-{{ $person->id }}-email">{{ __('Email *') }}</label>
                            <input id="staff-{{ $person->id }}-email" class="input" type="email" name="email" maxlength="255" required autocomplete="off"
                                value="{{ $field('email', $person->email) }}">
                            @if ($bag->has('email'))<div class="field-hint" style="color: var(--err)">{{ $bag->first('email') }}</div>@endif
                        </div>

                        <div class="field">
                            <label for="staff-{{ $person->id }}-password">{{ __('Yangi parol') }}</label>
                            <input id="staff-{{ $person->id }}-password" class="input" type="password" name="password" minlength="8" autocomplete="new-password">
                            <div class="field-hint">{{ __('O‘zgartirmasangiz, bo‘sh qoldiring') }}</div>
                            @if ($bag->has('password'))<div class="field-hint" style="color: var(--err)">{{ $bag->first('password') }}</div>@endif
                        </div>
                    @else
                        <div class="field-hint" style="grid-column: 1 / -1">{{ __('Bu o‘qituvchi boshqa filiallarda ham ishlaydi, shuning uchun email va parolni faqat super admin o‘zgartira oladi.') }}</div>
                    @endif
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('Saqlash') }}</button>
                </div>
            </form>
        </details>
    @empty
        <div class="card-sub">{{ $empty }}</div>
    @endforelse
</section>
