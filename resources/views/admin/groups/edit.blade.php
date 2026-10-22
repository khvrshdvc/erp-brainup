<x-app-layout :crumb="__('Guruhlar') . ' / ' . __('Tahrirlash')" :title="__('Guruhni tahrirlash')" :subtitle="$group->name">
    @include('admin.groups._form')
</x-app-layout>
