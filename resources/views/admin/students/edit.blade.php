<x-app-layout :crumb="__('O‘quvchilar') . ' / ' . __('Tahrirlash')" :title="__('O‘quvchini tahrirlash')" :subtitle="$student->name">
    @include('admin.students._form')

    <form method="POST" action="{{ route('admin.students.password', $student) }}" class="card card-pad stack"
        onsubmit="return confirm(@js(__('Yangi parol yaratilsinmi? Eski parol ishlamay qoladi.')))">
        @csrf

        <h2 class="card-title">{{ __('Parolni yangilash') }}</h2>
        <div class="field-hint">{{ __('Yangi parol yaratiladi va faqat bir marta ko‘rsatiladi.') }}</div>

        <div>
            <button type="submit" class="btn">{{ __('Parolni yangilash') }}</button>
        </div>
    </form>
</x-app-layout>
