<x-app-layout
    :crumb="__('Bosh sahifa')"
    :title="__('Xush kelibsiz, :name!', ['name' => \Illuminate\Support\Str::before(auth()->user()->name, ' ')])"
    :subtitle="\App\Support\UzDate::long(now())">

    <x-slot name="actions">
        <a href="{{ route('groups.index') }}" class="btn btn-primary">{{ __('Davomat olish') }}</a>
    </x-slot>

    <div class="kpi-grid">
        <x-stat-card :label="__('Mening guruhlarim')" :value="$groups->count()" icon="groups" :note="__('Faol va rejalashtirilgan')" />
        <x-stat-card :label="__('Jami o‘quvchilar')" :value="$students" icon="students" :note="__('Barcha guruhlar bo‘yicha')" />
        <x-stat-card :label="__('Bugungi davomat')" :value="$todayMarked" icon="calendar" :note="__('Siz belgilagan yozuvlar')" />
    </div>

    <section class="card" style="overflow: hidden">
        <div class="card-head">
            <h2 class="card-title">{{ __('Mening guruhlarim') }}</h2>
        </div>
        <div class="t-scroll">
            <div class="t-min">
                <div class="t-head" style="--cols: 2.4fr 1fr 1.2fr 1fr">
                    <div>{{ __('Guruh') }}</div>
                    <div>{{ __('O‘quvchilar') }}</div>
                    <div>{{ __('Holat') }}</div>
                    <div></div>
                </div>
                @forelse ($groups as $group)
                    <div class="t-row" style="--cols: 2.4fr 1fr 1.2fr 1fr">
                        <div style="font-weight: 600">{{ $group->name }}</div>
                        <div>{{ $group->students_count }}</div>
                        <div><x-status-indicator :active="$group->isActive()" :label="$group->status->label()" /></div>
                        <div class="t-right">
                            <a href="{{ route('attendance.create', $group) }}" class="btn btn-sm">{{ __('Davomat') }}</a>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Sizga guruh biriktirilmagan') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
