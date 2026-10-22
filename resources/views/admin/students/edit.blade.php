<x-app-layout :crumb="__('O‘quvchilar') . ' / ' . __('Tahrirlash')" :title="__('O‘quvchini tahrirlash')" :subtitle="$student->name">
    @include('admin.students._form')
</x-app-layout>
