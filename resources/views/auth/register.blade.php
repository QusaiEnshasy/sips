
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIP - Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="modulepreload" crossorigin href="/assets/vendor-DQfLmYUC.js">
    <link rel="stylesheet" crossorigin href="/assets/index-BK2zQAfO.css">
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
    </script>
    <script type="module" crossorigin src="/assets/index-D9r8Cv-D.js"></script>
</body>

</html>
