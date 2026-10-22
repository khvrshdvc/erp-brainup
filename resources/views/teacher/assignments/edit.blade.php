<x-app-layout :crumb="__('Vazifalar') . ' / ' . __('Tahrirlash')" :title="__('Vazifani tahrirlash')" :subtitle="$assignment->title">
    @include('teacher.assignments._form')
</x-app-layout>
