{{-- The login and password an admin has just issued (App\Support\IssuedCredentials); shown on this page only. --}}
@php $issued = \App\Support\IssuedCredentials::current(); @endphp

@if ($issued)
    @php
        $rows = [
            [__('Ism va familiya'), $issued['name']],
            [__('Filial'), $issued['branch'] ?: '—'],
            [__('Sayt manzili'), $issued['site']],
            [__('Login'), $issued['login']],
            [__('Parol'), $issued['password']],
        ];
        $text = collect($rows)->map(fn ($row) => $row[0].': '.$row[1])->implode("\n");
    @endphp

    <section class="card cred-card" x-data="issuedCredentials({ text: @js($text), label: @js(__('Nusxa olish')), copied: @js(__('Nusxa olindi')) })">
        <div class="card-head">
            <div>
                <h2 class="card-title">{{ __('Kirish ma‘lumotlari') }}</h2>
                <div class="card-sub cred-hide">{{ __('Parol faqat hozir ko‘rsatiladi. Nusxa oling yoki kartochkani chop eting.') }}</div>
            </div>
            <div class="page-actions cred-hide">
                <button type="button" class="btn btn-sm" @click="copy()" x-text="label">{{ __('Nusxa olish') }}</button>
                <button type="button" class="btn btn-sm btn-primary" @click="window.print()">{{ __('Kartochkani chop etish') }}</button>
            </div>
        </div>

        <div class="info-grid">
            @foreach ($rows as [$label, $value])
                <div class="info-item">
                    <span class="info-text">
                        <small>{{ $label }}</small>
                        <b @class(['cred-secret' => $loop->index >= 3])>{{ $value }}</b>
                    </span>
                </div>
            @endforeach
        </div>
    </section>
@endif
