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
        // Registrar SW también en login para habilitar PWA desde la primera visita
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(()=>{});
            });
        }
    </script>
</body>
</html>
