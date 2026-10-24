<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @vite(['resources/css/editor.css', 'resources/js/editor.js'])
    </head>
    <body class="studio-body antialiased">
        <a href="#studio-workspace" class="studio-skip">Skip to code editor</a>
        {{ $slot }}
        <flux:toast />
        @fluxScripts
    </body>
</html>
