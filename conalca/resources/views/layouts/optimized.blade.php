<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- DNS prefetch for external resources -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    @vite('resources/css/app.css')
    @livewireStyles

    <!-- Optimized font loading -->
    <link rel="preload" href="{{ asset('build/assets/ProductSansBold-8dbeee80.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="{{ asset('build/assets/ProductSansRegular-b34cbb71.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="{{ asset('static/Montserrat-Thin.ttf') }}" as="font" type="font/ttf" crossorigin>

    <!-- Google Fonts with display=swap for better performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Product+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <title>@yield('title', 'CONALCA AI') - {{ config('app.name') }}</title>

    <!-- Cache control for HTML pages -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    @stack('styles')
    @stack('scripts')
</head>
<body>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded">
        Saltar al contenido principal
    </a>

    <div id="app">
        @yield('content')
    </div>

    <!-- Lazy load non-critical JavaScript -->
    <script>
        // Defer non-critical JavaScript loading
        window.addEventListener('load', function() {
            // Load additional scripts after page load
            const scripts = [
                // Add any non-critical scripts here
            ];

            scripts.forEach(src => {
                const script = document.createElement('script');
                script.src = src;
                script.defer = true;
                document.head.appendChild(script);
            });
        });

        // Preload critical images
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    </script>

    @livewireScripts
    @vite('resources/js/app.js')

    @stack('after-scripts')
</body>
</html>
