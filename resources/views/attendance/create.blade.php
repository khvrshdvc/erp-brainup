@php
    $statuses = \App\Enums\AttendanceStatus::cases();
    $initial = $students->mapWithKeys(fn ($student) => [$student->id => $existing[$student->id] ?? 'present'])->all();
    $lessonDate = \Carbon\Carbon::parse($date);
@endphp

<x-app-layout crumb="Guruhlar" title="Davomat olish" subtitle="Dars davomatini belgilang">
    <div class="stack-lg"
        x-data="{
            state: @js($initial),
            setAll(value) { Object.keys(this.state).forEach(key => this.state[key] = value) },
            count(value) { return Object.values(this.state).filter(item => item === value).length }
        }">

        <section class="card filters">
            <div class="field" style="min-width: 260px">
                <label for="group">Guruh</label>
                <select id="group" class="select" onchange="window.location = this.value">
                    @foreach ($groups as $item)
                        <option value="{{ route('attendance.create', ['studyGroup' => $item->id, 'date' => $date]) }}"
                            @selected($item->id === $studyGroup->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

            <form method="GET" action="{{ route('attendance.create', $studyGroup) }}" class="field">
                <label for="date">Dars sanasi</label>
                <input id="date" class="input" type="date" name="date" value="{{ $date }}"
                    max="{{ today()->toDateString() }}" onchange="this.form.submit()">
            </form>

            <div class="erp-spacer"></div>

            @if ($students->isNotEmpty())
                <button type="button" class="btn" @click="setAll('present')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Barchasini keldi deb belgilash
                </button>
            @endif
        </section>

        <form method="POST" action="{{ route('attendance.store', $studyGroup) }}" class="stack-lg">
            @csrf
            <input type="hidden" name="lesson_date" value="{{ $date }}">

            <section class="card" style="overflow: hidden">
                <div class="card-head">
                    <h2 class="card-title">{{ \App\Support\UzDate::long($lessonDate) }} · {{ $studyGroup->name }}</h2>
                    <div class="att-summary">
                        Jami: <b>{{ $students->count() }}</b>
                        @foreach ($statuses as $status)
                            · {{ $status->label() }} <b x-text="count('{{ $status->value }}')">0</b>
                        @endforeach
                    </div>
                </div>

                <div class="t-scroll">
                    <div class="t-min" style="min-width: 640px">
                        <div class="t-head" style="--cols: 2.2fr 3.4fr">
                            <div>O‘quvchi</div>
                            <div>Holat</div>
                        </div>

                        @forelse ($students as $student)
                            <div class="t-row" style="--cols: 2.2fr 3.4fr">
                                <div class="t-name">
                                    <span class="avatar avatar-sm">{{ $student->initials }}</span>
                                    {{ $student->name }}
                                </div>

                                <div class="seg" role="group" aria-label="{{ $student->name }} davomati">
                                    @foreach ($statuses as $status)
                                        <label class="pill {{ $status->badge() }}"
                                            :class="{ 'on': state[{{ $student->id }}] === '{{ $status->value }}' }">
                                            <input type="radio" name="records[{{ $student->id }}]"
                                                value="{{ $status->value }}" x-model="state[{{ $student->id }}]">
                                            {{ $status->label() }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="t-empty">Guruhda o‘quvchi yo‘q</div>
                        @endforelse
                    </div>
                </div>
            </section>

            @if ($students->isNotEmpty())
                <div class="form-actions">
                    <a href="{{ route('groups.index') }}" class="btn btn-lg">Bekor qilish</a>
                    <button type="submit" class="btn btn-primary btn-lg" style="padding: 0 28px">Saqlash</button>
                </div>
            @endif
        </form>
    </div>
</x-app-layout>
