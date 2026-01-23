<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр файла</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            font-family: 'Inter', sans-serif;
            background: #f5f6f8;
            color: #1a1a1a;
        }
        .viewer-header {
            padding: 12px 16px;
            background: #fff;
            border-bottom: 1px solid #e2e2e2;
        }
        .viewer-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .viewer-content iframe,
        .viewer-content object {
            width: 100%;
            height: 100%;
            border: none;
            background: #fff;
        }
        .viewer-content img,
        .viewer-content video {
            max-width: 100%;
            max-height: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }
        .viewer-link {
            display: inline-flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #004aad;
            color: #004aad;
            text-decoration: none;
            background: #fff;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="viewer-header">Просмотр файла</div>
    <div class="viewer-content">
        @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
            <img src="{{ $doc }}" alt="Файл">
        @elseif($ext === 'pdf')
            <iframe src="{{ $doc }}"></iframe>
        @elseif($ext === 'mp4')
            <video src="{{ $doc }}" controls></video>
        @else
            <a class="viewer-link" href="{{ $doc }}" target="_blank" rel="noopener">Скачать файл</a>
        @endif
    </div>
</body>
</html>
