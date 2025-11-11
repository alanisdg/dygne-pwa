<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Dygne PWA">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Dygne PWA">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    
    <title>{{ config('app.name', 'Dygne PWA') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- PWA Diagnostics & SW Registration -->
    <script>
        (function(){
            console.log('[PWA] location', location.href);
            console.log('[PWA] protocol/host', location.protocol, location.host);
            console.log('[PWA] display-mode', window.matchMedia('(display-mode: standalone)').matches ? 'standalone' : 'browser');
            console.log('[PWA] navigator.standalone (iOS)', ("standalone" in navigator) ? navigator.standalone : 'n/a');

            // Check manifest availability
            fetch('/manifest.json', { cache: 'no-store' })
                .then(r => console.log('[PWA] manifest status', r.status))
                .catch(e => console.warn('[PWA] manifest fetch error', e));

            // Service worker registration with detailed logs
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js', { scope: '/' })
                        .then(reg => {
                            console.log('[PWA] SW registered @', reg.scope);
                            if (reg.installing) { console.log('[PWA] SW installing'); reg.installing.addEventListener('statechange', () => console.log('[PWA] installing state', reg.installing && reg.installing.state)); }
                            if (reg.waiting) { console.log('[PWA] SW waiting'); }
                            if (reg.active) { console.log('[PWA] SW active'); }
                            reg.addEventListener('updatefound', () => console.log('[PWA] updatefound'));
                            navigator.serviceWorker.addEventListener('controllerchange', () => console.log('[PWA] controllerchange'));
                        })
                        .catch(err => console.error('[PWA] SW register error', err));
                });
            } else {
                console.warn('[PWA] ServiceWorker not supported in this browser');
            }

            // Install prompt diagnostics
            let deferredPrompt = null;
            window.addEventListener('beforeinstallprompt', (e) => {
                console.log('[PWA] beforeinstallprompt fired', e);
                e.preventDefault();
                deferredPrompt = e;
                const btn = document.getElementById('installBtn');
                if (btn) btn.hidden = false;
            });

            window.addEventListener('appinstalled', () => {
                console.log('[PWA] appinstalled');
            });

            window.launchPwaInstall = async function() {
                const btn = document.getElementById('installBtn');
                if (!deferredPrompt) { console.warn('[PWA] No deferredPrompt available'); return; }
                btn && (btn.disabled = true);
                try {
                    await deferredPrompt.prompt();
                    const choice = await deferredPrompt.userChoice;
                    console.log('[PWA] userChoice', choice);
                } catch (err) {
                    console.error('[PWA] prompt error', err);
                } finally {
                    deferredPrompt = null;
                    btn && (btn.hidden = true);
                }
            }
        })();
    </script>
</head>
<body class="font-sans antialiased bg-gray-100">
    <div id="app">
        <!-- Vue app will be mounted here -->
    </div>
    <!-- Install button (shows only when eligible) -->
    <button id="installBtn" hidden onclick="launchPwaInstall()" class="fixed inset-x-4 bottom-4 z-50 rounded-lg bg-black text-white py-3 font-semibold shadow-lg">
        Instalar app
    </button>
</body>
</html>
