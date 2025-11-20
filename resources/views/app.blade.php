<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA basics -->
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Dygne">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <title>{{ config('app.name', 'Dygne PWA') }}</title>
     @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead 

  
  </head>
  <body class="font-sans antialiased bg-gray-100">
    @inertia

<script>
(function () {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {

            const registration = await navigator.serviceWorker.register('/sw.js');

            const token = localStorage.getItem("auth_token");
            const user = {id: 1}

            if (token  ) {
                subscribeToPush(user, token); // <-- ahora sí existe y se ejecuta a la hora correcta
            } else {
                console.log("no hay user or token todavia");
            }
        });
    }
})();
</script>

  </body>
</html>
