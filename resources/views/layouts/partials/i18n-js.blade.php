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
    ];
@endphp
<script>
    window.ERP_I18N = {!! json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
</script>
