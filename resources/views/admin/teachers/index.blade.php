<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('O‘qituvchilar')" :title="__('O‘qituvchilar')" :subtitle="__('O‘qituvchilarni qo‘shish va filiallarga biriktirish')">
    <x-slot name="actions">
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">{{ __('+ O‘qituvchi qo‘shish') }}</a>
    </x-slot>

    <form method="GET" action="{{ route('admin.teachers.index') }}" class="card filters">
        <div class="field">
            <label for="q">{{ __('Qidirish') }}</label>
            <input id="q" class="input" type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Ism, email yoki telefon') }}">
        </div>
        <button type="submit" class="btn">{{ __('Qidirish') }}</button>
    </form>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 900px">
                <div class="t-head" style="--cols: 2.6fr 1.4fr 2.2fr .9fr 1.4fr .5fr">
                    <div>{{ __('O‘qituvchi') }}</div>
                    <div>{{ __('Telefon') }}</div>
                    <div>{{ __('Filiallar') }}</div>
                    <div>{{ __('Guruhlar') }}</div>
                    <div>{{ __('Holat') }}</div>
                    <div></div>
                </div>

                @forelse ($teachers as $teacher)
                    <div class="t-row" style="--cols: 2.6fr 1.4fr 2.2fr .9fr 1.4fr .5fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $teacher->initials }}</span>
                            <div>
                                {{ $teacher->name }}
                                <div class="card-sub" style="font-weight: 400">{{ $teacher->email }}</div>
                            </div>
                        </div>
                        <div>{{ $teacher->phone ?? '—' }}</div>
                        <div>{{ $teacher->branches->pluck('name')->implode(', ') }}</div>
                        <div>{{ $teacher->teaching_groups_count }}</div>
                        <div>
                            <x-status-switch :action="route('admin.teachers.status', $teacher)"
                                :active="$teacher->isActive()"
                                :label="$teacher->status->label()" />
                        </div>
                        <div class="t-right"><x-edit-button :href="route('admin.teachers.edit', $teacher)" /></div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('O‘qituvchi topilmadi') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
