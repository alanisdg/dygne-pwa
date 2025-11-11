<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Dygne PWA') }} - Login</title>

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#000000">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Safe-area padding for notches */
        :root { 
            --safe-top: env(safe-area-inset-top); 
            --safe-bottom: env(safe-area-inset-bottom); 
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 antialiased flex items-center justify-center p-4 pt-[calc(1rem+var(--safe-top))] pb-[calc(1rem+var(--safe-bottom))]">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="mx-auto h-16 w-16 rounded-full bg-black/90 flex items-center justify-center text-white text-2xl font-bold">D</div>
            <h1 class="mt-4 text-2xl font-semibold">Bienvenido</h1>
            <p class="text-sm text-gray-500">Inicia sesión para continuar</p>
        </div>

        <div class="bg-white shadow-sm rounded-xl p-5">
            <form method="POST" action="{{ url('/login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Usuario o correo</label>
                    <input id="email" name="email" type="text" inputmode="email" autocomplete="username" required
                           class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-base outline-none focus:ring-2 focus:ring-black/70 focus:border-black/70"
                           placeholder="usuario@ejemplo.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-base outline-none focus:ring-2 focus:ring-black/70 focus:border-black/70"
                           placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 select-none">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-black focus:ring-black">
                        <span class="text-sm text-gray-600">Recordarme</span>
                    </label>
                    <a href="#" class="text-sm font-medium text-black/80 hover:text-black">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-black text-white py-3 font-semibold active:scale-[.99]">
                    Iniciar sesión
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-gray-500">
            ¿No tienes cuenta? <a class="font-medium text-black hover:underline" href="#">Regístrate</a>
        </p>
    </div>

    <script>
        (function(){
            console.log('[PWA][login] location', location.href);
            console.log('[PWA][login] protocol/host', location.protocol, location.host);
            console.log('[PWA][login] display-mode', window.matchMedia('(display-mode: standalone)').matches ? 'standalone' : 'browser');
            console.log('[PWA][login] navigator.standalone (iOS)', ("standalone" in navigator) ? navigator.standalone : 'n/a');

            // Check manifest
            fetch('/manifest.json', { cache: 'no-store' })
                .then(r => console.log('[PWA][login] manifest status', r.status))
                .catch(e => console.warn('[PWA][login] manifest fetch error', e));

            // Service worker registration with logs
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js', { scope: '/' })
                        .then(reg => {
                            console.log('[PWA][login] SW registered @', reg.scope);
                            if (reg.installing) { console.log('[PWA][login] SW installing'); reg.installing.addEventListener('statechange', () => console.log('[PWA][login] installing state', reg.installing && reg.installing.state)); }
                            if (reg.waiting) { console.log('[PWA][login] SW waiting'); }
                            if (reg.active) { console.log('[PWA][login] SW active'); }
                            reg.addEventListener('updatefound', () => console.log('[PWA][login] updatefound'));
                            navigator.serviceWorker.addEventListener('controllerchange', () => console.log('[PWA][login] controllerchange'));
                        })
                        .catch(err => console.error('[PWA][login] SW register error', err));
                });
            }

            // Install prompt handling
            let deferredPrompt = null;
            window.addEventListener('beforeinstallprompt', (e) => {
                console.log('[PWA][login] beforeinstallprompt fired', e);
                e.preventDefault();
                deferredPrompt = e;
                const btn = document.getElementById('installBtn');
                if (btn) btn.hidden = false;
            });

            window.addEventListener('appinstalled', () => {
                console.log('[PWA][login] appinstalled');
            });

            window.launchPwaInstall = async function() {
                const btn = document.getElementById('installBtn');
                if (!deferredPrompt) { console.warn('[PWA][login] No deferredPrompt available'); return; }
                btn && (btn.disabled = true);
                try {
                    await deferredPrompt.prompt();
                    const choice = await deferredPrompt.userChoice;
                    console.log('[PWA][login] userChoice', choice);
                } catch (err) {
                    console.error('[PWA][login] prompt error', err);
                } finally {
                    deferredPrompt = null;
                    btn && (btn.hidden = true);
                }
            }
        })();
    </script>
    <button id="installBtn" hidden onclick="launchPwaInstall()" class="fixed inset-x-4 bottom-4 z-50 rounded-lg bg-black text-white py-3 font-semibold shadow-lg">
        Instalar app
    </button>
</body>
</html>
