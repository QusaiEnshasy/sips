
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIP - Register</title>
    @php
        $manifestPath = public_path('.vite/manifest.json');
        $manifest = file_exists($manifestPath)
            ? json_decode(file_get_contents($manifestPath), true)
            : [];

        $entry = $manifest['index.html'] ?? null;
        $jsAsset = isset($entry['file']) ? 'assets/' . basename($entry['file']) : null;
        $cssAssets = collect($entry['css'] ?? [])
            ->map(fn ($asset) => 'assets/' . basename($asset))
            ->values();
        $jsVersion = $jsAsset && file_exists(public_path($jsAsset)) ? filemtime(public_path($jsAsset)) : time();
    @endphp
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @foreach($cssAssets as $cssAsset)
        @if(file_exists(public_path($cssAsset)))
            <link rel="stylesheet" crossorigin href="/{{ $cssAsset }}?v={{ filemtime(public_path($cssAsset)) }}">
        @endif
    @endforeach
</head>

<body style="margin:0;min-height:100vh;background:#f4f6fb;">
    @if(session('success'))
    <div style="max-width:760px;margin:16px auto;padding:10px 14px;border-radius:10px;background:#e8f8ee;color:#166534;font-family:Arial,sans-serif;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="max-width:760px;margin:16px auto;padding:10px 14px;border-radius:10px;background:#fee2e2;color:#991b1b;font-family:Arial,sans-serif;">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div style="max-width:760px;margin:16px auto;padding:10px 14px;border-radius:10px;background:#fee2e2;color:#991b1b;font-family:Arial,sans-serif;">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div id="app"></div>

    <script>
        window.__AUTH_USER__ = @json(auth()->user());
        window.__REGISTER_OLD__ = @json(old());
        window.__REGISTER_ERRORS__ = @json($errors->toArray());
    </script>
    @if($jsAsset)
        <script type="module" crossorigin src="/{{ $jsAsset }}?v={{ $jsVersion }}"></script>
    @endif
</body>

</html>
