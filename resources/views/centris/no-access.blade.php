<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors *">
    <title>Accès refusé</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: white;
        }

        .message-container {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            margin: 20px;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .message-container h1 {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .message-container p {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <div class="icon">🔒</div>
        <h1>Accès refusé</h1>
        <p>{{ $message ?? "Vous n'avez pas accès à ces propriétés." }}</p>
    </div>
</body>
</html>
