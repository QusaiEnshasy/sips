<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>SIP - App</title>
    @php
        $vendorAsset = 'assets/vendor-DQfLmYUC.js';
        $cssAsset = 'assets/index-Cxt7YYcM.css';
        $jsAsset = 'assets/index-eB-YqcqT.js';
        $vendorVersion = file_exists(public_path($vendorAsset)) ? filemtime(public_path($vendorAsset)) : time();
        $cssVersion = file_exists(public_path($cssAsset)) ? filemtime(public_path($cssAsset)) : time();
        $jsVersion = file_exists(public_path($jsAsset)) ? filemtime(public_path($jsAsset)) : time();
    @endphp
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="modulepreload" crossorigin href="/{{ $vendorAsset }}?v={{ $vendorVersion }}">
    <link rel="stylesheet" crossorigin href="/{{ $cssAsset }}?v={{ $cssVersion }}">
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
    <script type="module" crossorigin src="/{{ $jsAsset }}?v={{ $jsVersion }}"></script>
</body>
</html>


