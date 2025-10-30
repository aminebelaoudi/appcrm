<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'Connexion réussie' : 'Erreur de connexion' }} - GHL OAuth</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .header.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .header.error {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }

        .icon-container {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .icon {
            font-size: 48px;
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }

        .content {
            padding: 30px;
        }

        .info-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }

        .info-value {
            color: #212529;
            font-size: 14px;
            font-weight: 500;
            text-align: right;
            max-width: 60%;
            word-break: break-all;
        }

        .error-details {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .error-details h3 {
            color: #856404;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .error-details p {
            color: #856404;
            font-size: 13px;
            line-height: 1.5;
        }

        .button-container {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 2px solid #dee2e6;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            color: #6c757d;
            font-size: 13px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }

        /* Animation pour le checkmark */
        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: block;
            stroke-width: 3;
            stroke: white;
            stroke-miterlimit: 10;
            margin: 0 auto;
            animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
        }

        .checkmark-circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 3;
            stroke-miterlimit: 10;
            stroke: white;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark-check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px white;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $success ? 'success' : 'error' }}">
            <div class="icon-container">
                @if($success)
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                @else
                    <span class="icon">❌</span>
                @endif
            </div>
            
            <h1>{{ $success ? 'Connexion établie !' : 'Erreur de connexion' }}</h1>
            <p>{{ $message }}</p>
        </div>

        <div class="content">
            @if($success)
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Statut</span>
                        <span class="info-value">
                            <span class="status-badge success">✓ Connecté</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Token valide jusqu'au</span>
                        <span class="info-value">{{ $expiresAt ?? 'N/A' }}</span>
                    </div>
                </div>

                <div class="button-container">
                    <a href="/properties?locationId={{ $locationId }}" class="btn btn-primary">
                        Accéder aux propriétés →
                    </a>
                </div>

                <div style="text-align: center; margin-top: 15px; color: #6c757d; font-size: 13px;">
                    Redirection automatique dans <span id="countdown">3</span> secondes...
                </div>
            @else
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Statut</span>
                        <span class="info-value">
                            <span class="status-badge error">✗ Échec</span>
                        </span>
                    </div>
                </div>

                @if(isset($error))
                    <div class="error-details">
                        <h3>Détails de l'erreur</h3>
                        <p>{{ $error }}</p>
                    </div>
                @endif

                <div class="button-container">
                    <a href="/ghl/login" class="btn btn-primary">
                        Réessayer
                    </a>
                    <a href="/" class="btn btn-secondary">
                        Retour à l'accueil
                    </a>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>{{ $success ? 'Votre connexion GHL est maintenant active et sécurisée.' : 'Besoin d\'aide ? Contactez le support technique.' }}</p>
        </div>
    </div>

    @if($success)
    <script>
        // Compteur de redirection
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        const interval = setInterval(function() {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            if (countdown <= 0) {
                clearInterval(interval);
            }
        }, 1000);

        // Redirection automatique après 3 secondes
        setTimeout(function() {
            window.location.href = '/properties?locationId={{ $locationId }}';
        }, 3000);
    </script>
    @endif
</body>
</html>
