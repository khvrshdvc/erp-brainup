{{-- Texts that scripts in public/js/erp.js need, in the interface language. --}}
@php
    $days = trans('dates.days_short');
    $i18n = [
        'months' => trans('dates.months_full'),
        'monthsGenitive' => trans('dates.months'),
        // Monday first, like the calendar grid.
        'weekdays' => array_merge(array_slice($days, 1), [$days[0]]),
        'dayMonth' => trans('dates.day_month'),
        'fileTypes' => __('Faqat :types fayl yuklash mumkin.'),
        'fileSize' => __('Fayl hajmi :max MB dan oshmasligi kerak.'),
        // Browser validation bubbles are shown in the interface language, not the browser's.
        'validity' => [
            'required' => __('Bu maydonni to‘ldiring'),
            'select' => __('Variantni tanlang'),
            'file' => __('Faylni tanlang'),
            'checkbox' => __('Davom etish uchun belgilang'),
            'email' => __('Haqiqiy email manzilini kiriting'),
            'tooShort' => __('Kamida :min ta belgi kiriting'),
            'min' => __('Qiymat :min dan kam bo‘lmasligi kerak'),
            'max' => __('Qiymat :max dan oshmasligi kerak'),
            'number' => __('To‘g‘ri qiymat kiriting'),
            'pattern' => __('Format noto‘g‘ri'),
        ],
    ];
@endphp
<script>
    window.ERP_I18N = {!! json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
</script>
