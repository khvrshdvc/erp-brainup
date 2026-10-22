<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('O‘quvchilar')" :title="__('O‘quvchilar')" :subtitle="__('O‘quvchilarni qo‘shish va boshqarish')">
    <x-slot name="actions">
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">{{ __('+ O‘quvchi qo‘shish') }}</a>
    </x-slot>

    <form method="GET" action="{{ route('admin.students.index') }}" class="card filters">
        <div class="field">
            <label for="q">{{ __('Qidirish') }}</label>
            <input id="q" class="input" type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Ism, email yoki telefon') }}">
        </div>

        <div class="field">
            <label for="group">{{ __('Guruh') }}</label>
            <select id="group" name="group" class="select">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected((int) request('group') === $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="status">{{ __('Holat') }}</label>
            <select id="status" name="status" class="select">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach (\App\Enums\UserStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn">{{ __('Filtrlash') }}</button>
    </form>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 940px">
                <div class="t-head" style="--cols: 2.6fr 1.4fr 1.6fr .9fr 1.4fr 1.4fr .5fr">
                    <div>{{ __('O‘quvchi') }}</div>
                    <div>{{ __('Telefon') }}</div>
                    <div>{{ __('Filial') }}</div>
                    <div>{{ __('Guruhlar') }}</div>
                    <div>{{ __('Daraja') }} · @coins(__('BrainCoin'))</div>
                    <div>{{ __('Holat') }}</div>
                    <div></div>
                </div>

                @forelse ($students as $student)
                    <div class="t-row" style="--cols: 2.6fr 1.4fr 1.6fr .9fr 1.4fr 1.4fr .5fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $student->initials }}</span>
                            <div>
                                <a class="link" href="{{ route('admin.students.show', $student) }}">{{ $student->name }}</a>
                                <div class="card-sub" style="font-weight: 400">{{ $student->email }}</div>
                            </div>
                        </div>
                        <div>{{ $student->phone ?? '—' }}</div>
                        <div>{{ $student->branch?->name }}</div>
                        <div>{{ $student->study_groups_count }}</div>
                        <div>{{ __($student->level?->name ?? 'Uchqun') }} · {{ $student->xp }}</div>
                        <div>
                            <x-status-switch :action="route('admin.students.status', $student)"
                                :active="$student->isActive()"
                                :label="$student->status->label()" />
                        </div>
                        <div class="t-right"><x-edit-button :href="route('admin.students.edit', $student)" /></div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('O‘quvchi topilmadi') }}</div>
                @endforelse
            </div>
        </div>
    </section>

    <x-pager :paginator="$students" />
</x-app-layout>
