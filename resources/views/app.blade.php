<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @if (config('banyira.demo.public'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <meta name="theme-color" content="#f5f5f7" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
    <meta name="description" content="Banyira connects Malawian businesses seeking financing with investors who want to support real businesses.">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="manifest" href="/manifest.webmanifest">
    <script>
        (function () {
            try {
                var p = localStorage.getItem('banyira-theme') || 'system';
                var dark = p === 'dark' || (p === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <x-inertia::head>
        <title>Banyira</title>
    </x-inertia::head>
</head>
<body>
    @if (config('banyira.demo.public'))
        <p role="note" class="border-b border-current/10 px-4 py-2 text-center text-[13px]">Demonstration site. All businesses and people are fictional and no real money moves. Please do not enter real personal details.</p>
    @endif
    <x-inertia::app />
</body>
</html>
