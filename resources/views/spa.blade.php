<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIP - App</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="modulepreload" crossorigin href="/assets/vendor-DQfLmYUC.js">
    <link rel="stylesheet" crossorigin href="/assets/index-BK2zQAfO.css">
</head>
<body style="margin:0;min-height:100vh;background:#f4f6fb;">
    <div id="app"></div>

    <script>
        window.__AUTH_USER__ = @json(request()->routeIs('login', 'register') ? null : auth()->user());
        window.__FLASH__ = {!! json_encode([
            'success' => session('success'),
            'error' => session('error'),
            'status' => session('status'),
            'warning' => session('warning'),
        ]) !!};
    </script>
    <script type="module" crossorigin src="/assets/index-BwWTfcTp.js"></script>
</body>
</html>


