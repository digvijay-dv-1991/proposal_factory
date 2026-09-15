<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="user-id" content="{{ auth()->id() }}">

        <title>Profile — ALQIMI Capture Deck</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|jost:500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/capture-deck.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app">
            <header class="masthead profile-masthead">
                <div class="brand-row">
                    <div class="brand">
                        <x-brand-mark :size="30" />
                        <span class="brand-tag">Capture Deck</span>
                    </div>
                    <a href="{{ route('opportunities.index') }}" wire:navigate class="btn">&larr; Back to Opportunities</a>
                </div>
            </header>

            <div class="profile-page">
                <h1 class="profile-page-title">Profile</h1>

                <div class="profile-page-grid">
                    <livewire:profile.update-profile-information-form />
                    <livewire:profile.update-password-form />
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </body>
</html>
