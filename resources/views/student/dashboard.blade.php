<x-app-layout
    crumb="Bosh sahifa"
    :title="'Salom, '.\Illuminate\Support\Str::before(auth()->user()->name, ' ').'!'"
    :subtitle="\App\Support\UzDate::long(now())">

    <div class="kpi-grid">
        <x-stat-card label="XP" :value="$student->xp" icon="xp" note="To‘plangan ballar" />
        <x-stat-card label="Daraja" :value="$student->level?->name ?? 'Boshlovchi'" icon="level" note="Joriy daraja" />
        <x-stat-card label="Davomat seriyasi" :value="$student->streak" icon="streak" note="Ketma-ket darslar" />
    </div>

    <section class="card" style="overflow: hidden">
        <div class="card-head">
            <h2 class="card-title">Mening guruhlarim</h2>
        </div>
        <div class="t-scroll">
            <div class="t-min" style="min-width: 520px">
                <div class="t-head" style="--cols: 2fr 2fr">
                    <div>Guruh</div>
                    <div>O‘qituvchi</div>
                </div>
                @forelse ($groups as $group)
                    <div class="t-row" style="--cols: 2fr 2fr">
                        <div style="font-weight: 600">{{ $group->name }}</div>
                        <div>{{ $group->teacher?->name ?? '—' }}</div>
                    </div>
                @empty
                    <div class="t-empty">Guruhga biriktirilmagan</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="card" style="overflow: hidden">
        <div class="card-head">
            <h2 class="card-title">So‘nggi davomat</h2>
        </div>
        <div class="t-scroll">
            <div class="t-min" style="min-width: 520px">
                <div class="t-head" style="--cols: 1fr 2fr 1.4fr">
                    <div>Sana</div>
                    <div>Guruh</div>
                    <div>Holat</div>
                </div>
                @forelse ($attendances as $attendance)
                    <div class="t-row" style="--cols: 1fr 2fr 1.4fr">
                        <div>{{ $attendance->lesson_date->format('d.m.Y') }}</div>
                        <div style="font-weight: 600">{{ $attendance->studyGroup?->name }}</div>
                        <div><x-badge :type="$attendance->status->badge()">{{ $attendance->status->label() }}</x-badge></div>
                    </div>
                @empty
                    <div class="t-empty">Hali davomat belgilanmagan</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
