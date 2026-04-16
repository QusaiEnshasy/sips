<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Trello Boards</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
            background: #f4f5f7;
        }

        .board {
            background: #0079bf;
            color: white;
            padding: 15px 20px;
            margin: 10px;
            border-radius: 8px;
            display: inline-block;
            min-width: 200px;
            cursor: pointer;
        }

        h1 {
            color: #172b4d;
        }
    </style>
</head>

<body>
    <h1>🗂️ بوردات Trello</h1>

    @if(empty($boards))
    <p>ما في بوردات أو في مشكلة بالاتصال.</p>
    @else
    @foreach($boards as $board)
    <div class="board">
        {{ $board['name'] }}
    </div>
    @endforeach
    @endif
</body>

</html>