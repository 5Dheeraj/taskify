<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Training Notice Template</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 40px;
            background: #fdfdfd;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .meta {
            font-size: 14px;
            margin-bottom: 30px;
        }
        .content {
            font-size: 16px;
            line-height: 1.8;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
            font-size: 14px;
        }
        .signature {
            margin-top: 60px;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>📢 Training Notice</h1>
    </div>

    <div class="meta">
        <p><strong>Title:</strong> {{ $notice->title }}</p>
        <p><strong>Status:</strong> {{ ucfirst($notice->status) }}</p>
        <p><strong>Date:</strong> {{ $notice->created_at->format('d M Y') }}</p>
    </div>

    <div class="content">
        {!! nl2br(e($notice->message)) !!}
    </div>

    <div class="signature">
        <p>Authorized By</p>
        <p><strong>Training Department</strong></p>
    </div>

</body>
</html>
