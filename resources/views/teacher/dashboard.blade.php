<x-app-layout
    crumb="Bosh sahifa"
    :title="'Xush kelibsiz, '.\Illuminate\Support\Str::before(auth()->user()->name, ' ').'!'"
    :subtitle="\App\Support\UzDate::long(now())">

    <x-slot name="actions">
        <a href="{{ route('groups.index') }}" class="btn btn-primary">Davomat olish</a>
    </x-slot>

    <div class="kpi-grid">
        <x-stat-card label="Mening guruhlarim" :value="$groups->count()" icon="groups" note="Faol va rejalashtirilgan" />
        <x-stat-card label="Jami o‘quvchilar" :value="$students" icon="students" note="Barcha guruhlar bo‘yicha" />
        <x-stat-card label="Bugungi davomat" :value="$todayMarked" icon="calendar" note="Siz belgilagan yozuvlar" />
    </div>

    <section class="card" style="overflow: hidden">
        <div class="card-head">
            <h2 class="card-title">Mening guruhlarim</h2>
        </div>
        <div class="t-scroll">
            <div class="t-min">
                <div class="t-head" style="--cols: 2.4fr 1fr 1.2fr 1fr">
                    <div>Guruh</div>
                    <div>O‘quvchilar</div>
                    <div>Holat</div>
                    <div></div>
                </div>
                @forelse ($groups as $group)
                    <div class="t-row" style="--cols: 2.4fr 1fr 1.2fr 1fr">
                        <div style="font-weight: 600">{{ $group->name }}</div>
                        <div>{{ $group->students_count }}</div>
                        <div><x-badge :type="$group->status->badge()">{{ $group->status->label() }}</x-badge></div>
                        <div class="t-right">
                            <a href="{{ route('attendance.create', $group) }}" class="btn btn-sm">Davomat</a>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">Sizga guruh biriktirilmagan</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
