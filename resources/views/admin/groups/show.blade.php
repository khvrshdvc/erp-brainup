<x-app-layout :crumb="__('Guruhlar') . ' / ' . $group->name" :title="$group->name"
    :subtitle="__($group->course?->name ?? 'Kurs tanlanmagan').' · '.$group->branch?->name">

    <x-slot name="actions">
        <a href="{{ route('attendance.create', $group) }}" class="btn">{{ __('Davomat') }}</a>
        <x-status-switch :action="route('admin.groups.status', $group)"
            :active="$group->isActive()"
            :label="$group->status->label()" />
        <x-edit-button :href="route('admin.groups.edit', $group)" />
    </x-slot>

    <section class="card card-pad">
        <div class="split" style="gap: 32px">
            <div><div class="label-up">{{ __('Holat') }}</div><x-badge :type="$group->status->badge()">{{ $group->status->label() }}</x-badge></div>
            <div><div class="label-up">{{ __('O‘qituvchi') }}</div><b>{{ $group->teacher?->name ?? '—' }}</b></div>
            <div><div class="label-up">{{ __('O‘quvchilar') }}</div><b>{{ $students->count() }} / {{ $group->capacity }}</b></div>
            <div>
                <div class="label-up">{{ __('Jadval') }}</div>
                <b>
                    @if ($group->schedule)
                        {{ collect($group->schedule['days'] ?? [])->map(fn ($day) => $days[$day] ?? $day)->implode(', ') }}
                        {{ ! empty($group->schedule['time']) ? '· '.$group->schedule['time'] : '' }}
                    @else
                        —
                    @endif
                </b>
            </div>
            <div>
                <div class="label-up">{{ __('Davr') }}</div>
                <b>{{ $group->starts_on?->format('d.m.Y') ?? '—' }} – {{ $group->ends_on?->format('d.m.Y') ?? '—' }}</b>
            </div>
        </div>
    </section>

    <section class="card" style="overflow: hidden">
        <div class="card-head">
            <h2 class="card-title">{{ __('O‘quvchilar') }}</h2>

            <form method="POST" action="{{ route('admin.groups.students.add', $group) }}" class="page-actions"
                x-data="{
                    url: @js(route('admin.groups.candidates', $group)),
                    q: '',
                    results: [],
                    picked: null,
                    open: false,
                    loading: false,
                    timer: null,
                    search() {
                        this.picked = null;
                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => this.load(), 250);
                    },
                    async load() {
                        this.loading = true;
                        try {
                            const response = await fetch(`${this.url}?q=${encodeURIComponent(this.q)}`, { headers: { Accept: 'application/json' } });
                            this.results = response.ok ? await response.json() : [];
                        } catch (e) {
                            this.results = [];
                        }
                        this.loading = false;
                        this.open = true;
                    },
                    pick(candidate) {
                        this.picked = candidate;
                        this.q = candidate.name;
                        this.open = false;
                    },
                }" @click.outside="open = false" @keydown.escape="open = false">
                @csrf
                <div style="position: relative; width: 260px">
                    <input type="text" class="input" style="width: 100%" placeholder="{{ __('O‘quvchini qidiring') }}" aria-label="{{ __('O‘quvchi') }}"
                        autocomplete="off" x-model="q" @input="search()" @focus="load()">
                    <input type="hidden" name="student_id" :value="picked ? picked.id : ''">
                    <div x-show="open" x-cloak class="card"
                        style="position: absolute; z-index: 20; top: calc(100% + 4px); left: 0; right: 0; max-height: 280px; overflow-y: auto; box-shadow: 0 8px 24px rgba(17, 24, 39, .12)">
                        <template x-for="candidate in results" :key="candidate.id">
                            <button type="button" class="t-row" style="width: 100%; display: block; text-align: left; background: none; border: 0; cursor: pointer; padding: 8px 12px"
                                @click="pick(candidate)">
                                <b x-text="candidate.name"></b>
                                <span class="card-sub" x-text="candidate.email"></span>
                            </button>
                        </template>
                        <div class="t-empty" x-show="! loading && results.length === 0">{{ __('O‘quvchi topilmadi') }}</div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" :disabled="! picked">{{ __('Qo‘shish') }}</button>
            </form>
        </div>

        <div class="t-scroll">
            <div class="t-min" style="min-width: 640px">
                <div class="t-head" style="--cols: 2.4fr 1.4fr 1fr 1fr">
                    <div>{{ __('O‘quvchi') }}</div>
                    <div>{{ __('Daraja') }}</div>
                    <div>@coins(__('BrainCoin'))</div>
                    <div></div>
                </div>

                @forelse ($students as $student)
                    <div class="t-row" style="--cols: 2.4fr 1.4fr 1fr 1fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $student->initials }}</span>
                            <a class="link" href="{{ route('admin.students.show', $student) }}">{{ $student->name }}</a>
                        </div>
                        <div>{{ __($student->level?->name ?? 'Uchqun') }}</div>
                        <div>{{ $student->xp }}</div>
                        <div class="t-right">
                            <form method="POST" action="{{ route('admin.groups.students.remove', [$group, $student]) }}"
                                onsubmit="return confirm(@js(__('O‘quvchini guruhdan chiqarasizmi?')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm">{{ __('Chiqarish') }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Guruhda o‘quvchi yo‘q') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
