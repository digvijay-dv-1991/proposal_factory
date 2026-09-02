<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jost:500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/auth.css', 'resources/js/app.js'])
    </head>
    <body class="auth-body">
        <div class="auth-topbar"></div>

        <div class="auth-shell">
            <div class="auth-card" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 60)" x-bind:class="{ 'is-visible': shown }">
                <div class="auth-panel-brand">
                    <a href="{{ route('login') }}" wire:navigate class="auth-brand">
                        <x-brand-mark :size="40" />
                        <span class="auth-brand-name">Capture Deck</span>
                    </a>

                    <p class="auth-tagline">
                        {{ __('Capture management for government-contracting teams — opportunities, gap analysis, and bid decisions in one place.') }}
                    </p>
                </div>

                <div class="auth-panel-form">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
