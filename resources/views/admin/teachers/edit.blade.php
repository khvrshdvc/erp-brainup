<x-app-layout :crumb="__('O‘qituvchilar') . ' / ' . __('Tahrirlash')" :title="__('O‘qituvchini tahrirlash')" :subtitle="$teacher->name">
    @include('admin.teachers._form')

    @if ($canEditCredentials)
        <form method="POST" action="{{ route('admin.teachers.password', $teacher) }}" class="card card-pad stack"
            onsubmit="return confirm(@js(__('Yangi parol yaratilsinmi? Eski parol ishlamay qoladi.')))">
            @csrf

            <h2 class="card-title">{{ __('Parolni yangilash') }}</h2>
            <div class="field-hint">{{ __('Yangi parol yaratiladi va faqat bir marta ko‘rsatiladi. O‘qituvchi birinchi kirishda uni o‘zgartiradi.') }}</div>

            <div>
                <button type="submit" class="btn">{{ __('Parolni yangilash') }}</button>
            </div>
        </form>
    @endif
</x-app-layout>
