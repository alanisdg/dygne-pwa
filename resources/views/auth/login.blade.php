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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lilita+One&display=swap" rel="stylesheet">
    <style>
        /* Safe-area padding for notches */
        :root { 
            --safe-top: env(safe-area-inset-top); 
            --safe-bottom: env(safe-area-inset-bottom); 
        }
        .font-logo {
            font-family: 'Lilita One', cursive;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 antialiased flex items-center justify-center p-4 pt-[calc(1rem+var(--safe-top))] pb-[calc(1rem+var(--safe-bottom))]">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <div class="flex justify-center mb-4">
                <video
                    src="/logo.mp4"
                    autoplay
                    muted
                    loop
                    playsinline
                    class="h-14 w-14 object-cover"
                ></video>
            </div>
            <h1 class="text-3xl font-bold text-dark tracking-tight mb-2 font-logo">Dygne</h1>
            <p class="text-gray-400 text-sm">Inicia sesión para acceder a tu panel de monitoreo.</p>
        </div>

        <div class="bg-white shadow-sm rounded-xl p-5">
            <form id="loginForm" method="POST" action="{{ url('/login') }}" class="space-y-4">
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
                   
                </div>

                <button id="submitBtn" type="submit"
                        class="w-full rounded-lg bg-[#0f8b59] hover:bg-[#0c7148] text-white py-3 font-semibold active:scale-[.99] disabled:opacity-60 disabled:pointer-events-none">
                    Iniciar sesión
                </button>
                <p id="errorMsg" class="text-sm text-red-600 hidden"></p>
            </form>
        </div>

     
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
    <script>
        // Frontend login against external API (app.dygne.com)
        (function(){
            // Si ya hay token, saltar el login y mandar directo al dashboard
            try {
                const existingToken = localStorage.getItem('auth_token');
                if (existingToken) {
                    window.location.href = '/app';
                    return;
                }
            } catch (e) {
                console.warn('[Login] No se pudo leer localStorage', e);
            }

            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const errorMsg = document.getElementById('errorMsg');
            if (!form) return;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                errorMsg && (errorMsg.classList.add('hidden'), errorMsg.textContent = '');
                submitBtn && (submitBtn.disabled = true);
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                try {
                    const res = await fetch('https://app.dygne.com/api/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        mode: 'cors',
                        body: JSON.stringify({ email, password })
                    });
                    if (!res.ok) {
                        throw new Error('Credenciales inválidas o error de red (' + res.status + ')');
                    }
                    const data = await res.json();
                    if (!data.access_token) {
                        throw new Error('Respuesta inesperada del servidor');
                    }
                    // Persist token, email, user and customer info
                    localStorage.setItem('auth_token', data.access_token);

                    try {
                        // Guardar user completo si viene en la respuesta, asegurando que role_id exista
                        if (data.user) {
                            const rawUser = data.user;
                            const normalizedRoleId = (typeof rawUser.role_id !== 'undefined')
                                ? rawUser.role_id
                                : (typeof rawUser.roleId !== 'undefined' ? rawUser.roleId : null);
                            const userToStore = Object.assign({}, rawUser, { role_id: normalizedRoleId });
                            localStorage.setItem('auth_user', JSON.stringify(userToStore));
                        }
                        // Guardar customer completo si viene en la respuesta raíz
                        if (data.customer) {
                            localStorage.setItem('auth_customer', JSON.stringify(data.customer));
                        }

                        // Email preferente desde la respuesta, si existe
                        const effectiveEmail = (data.user && data.user.email) ? data.user.email : email;
                        localStorage.setItem('auth_email', effectiveEmail);

                        // Nombre del cliente (puede venir como data.customer o anidado en data.user.customer)
                        const customerFromRoot = data.customer && data.customer.name ? data.customer.name : null;
                        const customerFromUser = data.user && data.user.customer && data.user.customer.name ? data.user.customer.name : null;
                        const customerName = customerFromRoot || customerFromUser || '';
                        if (customerName) {
                            localStorage.setItem('auth_customer_name', customerName);
                        }
                    } catch (storageErr) {
                        console.warn('[Login] Error guardando user/customer en localStorage', storageErr);
                    }
                    // Go to SPA
                    window.location.href = '/app';
                } catch (err) {
                    console.error('[Login] error', err);
                    if (errorMsg) {
                        errorMsg.textContent = err.message || 'No se pudo iniciar sesión';
                        errorMsg.classList.remove('hidden');
                    }
                } finally {
                    submitBtn && (submitBtn.disabled = false);
                }
            });
        })();
    </script>
    <button id="installBtn" hidden onclick="launchPwaInstall()" class="fixed inset-x-4 bottom-4 z-50 rounded-lg bg-black text-white py-3 font-semibold shadow-lg">
        Instalar app
    </button>
</body>
</html>
