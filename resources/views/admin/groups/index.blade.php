<x-app-layout crumb="Bosh sahifa / Guruhlar" title="Guruhlar" subtitle="Guruhlarni yaratish va boshqarish">
    <x-slot name="actions">
        <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">+ Yangi guruh</a>
    </x-slot>

    <form method="GET" action="{{ route('admin.groups.index') }}" class="card filters">
        <div class="field">
            <label for="q">Qidirish</label>
            <input id="q" class="input" type="text" name="q" value="{{ request('q') }}" placeholder="Guruh nomi">
        </div>

        <div class="field">
            <label for="status">Holat</label>
            <select id="status" name="status" class="select">
                <option value="">Barchasi</option>
                @foreach (\App\Enums\GroupStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn">Filtrlash</button>
    </form>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 900px">
                <div class="t-head" style="--cols: 2fr 1.4fr 1.6fr 1.8fr 1fr 1.5fr .5fr">
                    <div>Guruh</div>
                    <div>Kurs</div>
                    <div>Filial</div>
                    <div>O‘qituvchi</div>
                    <div>O‘quvchilar</div>
                    <div>Holat</div>
                    <div></div>
                </div>

                @forelse ($groups as $group)
                    <div class="t-row" style="--cols: 2fr 1.4fr 1.6fr 1.8fr 1fr 1.5fr .5fr">
                        <div><a class="link" style="font-weight: 600" href="{{ route('admin.groups.show', $group) }}">{{ $group->name }}</a></div>
                        <div>{{ $group->course?->name ?? '—' }}</div>
                        <div>{{ $group->branch?->name }}</div>
                        <div>{{ $group->teacher?->name ?? '—' }}</div>
                        <div>{{ $group->students_count }} / {{ $group->capacity }}</div>
                        <div><x-status-switch :action="route('admin.groups.status', $group)"
                                :active="$group->status === \App\Enums\GroupStatus::Active"
                                :label="$group->status->label()" /></div>
                        <div class="t-right"><x-edit-button :href="route('admin.groups.edit', $group)" /></div>
                    </div>
                @empty
                    <div class="t-empty">Guruh topilmadi</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
