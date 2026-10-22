<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        @include('layouts.partials.theme-init')
        @include('layouts.partials.i18n-js')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Onest:wght@300;400;500;600&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/erp.css') }}?v={{ hash_file('xxh3', public_path('css/erp.css')) }}">
    </head>
    <body class="erp-body">
        <div class="auth-tools">
            <x-language-switcher />
            <x-theme-toggle />
        </div>

        <div class="auth-wrap">
            <div class="auth-brand">
                <x-brand-logo variant="dark" class="logo-on-light" />
                <x-brand-logo class="logo-on-dark" />
            </div>
            <div class="auth-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
