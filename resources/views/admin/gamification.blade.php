<x-app-layout crumb="Sozlamalar" title="Gamifikatsiya" subtitle="XP qoidalari, darajalar va nishonlar">
    <form method="POST" action="{{ route('admin.gamification.update') }}" class="stack-lg">
        @csrf
        @method('PUT')

        <section class="card" style="overflow: hidden">
            <div class="card-head">
                <div>
                    <h2 class="card-title">XP qoidalari</h2>
                    <div class="card-sub">O‘zgartirilgan qoidalar faqat yangi davomatlarga ta‘sir qiladi</div>
                </div>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>

            <div class="t-scroll">
                <div class="t-min" style="min-width: 560px">
                    <div class="t-head" style="--cols: 3fr 1.2fr 1fr">
                        <div>Qoida</div>
                        <div>XP</div>
                        <div>Faol</div>
                    </div>

                    @foreach ($rules as $rule)
                        <div class="t-row" style="--cols: 3fr 1.2fr 1fr">
                            <div style="font-weight: 600">{{ $rule->title }}</div>
                            <div>
                                <input type="number" min="0" max="1000" class="input input-sm"
                                    name="rules[{{ $rule->id }}][points]" value="{{ old('rules.'.$rule->id.'.points', $rule->points) }}">
                            </div>
                            <div>
                                <label class="check">
                                    <input type="checkbox" name="rules[{{ $rule->id }}][is_active]" value="1" @checked($rule->is_active)>
                                    Yoqilgan
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </form>

    <div class="split" style="align-items: flex-start">
        <section class="card" style="flex: 2 1 360px; overflow: hidden">
            <div class="card-head"><h2 class="card-title">Darajalar</h2></div>
            @foreach ($levels as $level)
                <div class="t-row" style="--cols: 48px 1fr auto">
                    <span class="avatar avatar-sm">{{ $loop->iteration }}</span>
                    <div style="font-weight: 600">{{ $level->name }}</div>
                    <div class="card-sub">{{ number_format($level->min_xp, 0, '', ' ') }} XP</div>
                </div>
            @endforeach
        </section>

        <section class="card card-pad stack" style="flex: 3 1 420px">
            <h2 class="card-title">Nishonlar</h2>
            <div class="achv-grid">
                @foreach ($badges as $badge)
                    <div class="achv">
                        <div class="ico">{{ $badge->icon }}</div>
                        <b>{{ $badge->title }}</b>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
