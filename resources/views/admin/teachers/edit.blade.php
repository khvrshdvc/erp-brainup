<x-app-layout :crumb="__('O‘qituvchilar') . ' / ' . __('Tahrirlash')" :title="__('O‘qituvchini tahrirlash')" :subtitle="$teacher->name">
    @include('admin.teachers._form')
</x-app-layout>
