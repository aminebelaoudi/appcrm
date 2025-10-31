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
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Effet de grille 3D subtil en arrière-plan */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(90deg, #f0f0f0 1px, transparent 1px),
                linear-gradient(#f0f0f0 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.3;
            z-index: 0;
        }

        .container {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.9);
            max-width: 420px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            z-index: 1;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .header {
            padding: 25px 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            position: relative;
        }

        .header.success::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .header.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            position: relative;
        }

        .header.error::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .icon-container {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.15),
                inset 0 -5px 10px rgba(0, 0, 0, 0.1),
                inset 0 5px 10px rgba(255, 255, 255, 0.3);
            animation: float 3s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .icon {
            font-size: 40px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        }

        .header h1 {
            color: white;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }

        .header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 14px;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 24px;
        }

        .info-box {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 
                0 10px 25px -5px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transform: translateZ(0);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 700;
            color: #4b5563;
            font-size: 13px;
            letter-spacing: 0.3px;
        }

        .info-value {
            color: #111827;
            font-size: 13px;
            font-weight: 600;
            text-align: right;
            max-width: 60%;
            word-break: break-all;
        }

        .error-details {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.15);
        }

        .error-details h3 {
            color: #92400e;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .error-details p {
            color: #78350f;
            font-size: 13px;
            line-height: 1.6;
        }

        .button-container {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            text-align: center;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .btn:hover::before {
            opacity: 1;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: 
                0 10px 25px -5px rgba(139, 92, 246, 0.4),
                0 0 0 1px rgba(139, 92, 246, 0.1),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 15px 35px -5px rgba(139, 92, 246, 0.5),
                0 0 0 1px rgba(139, 92, 246, 0.2),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            color: #374151;
            border: 2px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .footer {
            padding: 16px 24px;
            background: linear-gradient(180deg, #fafafa 0%, #f5f5f5 100%);
            text-align: center;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .footer p {
            color: #6b7280;
            font-size: 11px;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            letter-spacing: 0.3px;
        }

        .status-badge.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .status-badge.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .countdown-text {
            text-align: center;
            margin-top: 14px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
            padding: 8px 16px;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        #countdown {
            display: inline-block;
            min-width: 20px;
            font-weight: 800;
            color: #8b5cf6;
            font-size: 14px;
        }

        /* Animation pour le checkmark en 3D */
        .checkmark {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: block;
            stroke-width: 4;
            stroke: white;
            stroke-miterlimit: 10;
            margin: 0 auto;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
            animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
        }

        .checkmark-circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 4;
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

        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
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
                </div>

                <div class="button-container">
                    <a href="/properties?locationId={{ $locationId }}" class="btn btn-primary">
                        Accéder aux propriétés →
                    </a>
                </div>

                <div class="countdown-text">
                    Fermeture automatique dans <span id="countdown">4</span> secondes...
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
            @endif
        </div>

        <div class="footer">
            <p>{{ $success ? 'Votre connexion GHL est maintenant active et sécurisée.' : 'Besoin d\'aide ? Contactez le support technique.' }}</p>
        </div>
    </div>

    @if($success)
    <script>
        // Compteur de fermeture
        let countdown = 4;
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

        // Fermeture automatique de la fenêtre après 4 secondes
        setTimeout(function() {
            // Essayer de fermer la fenêtre (fonctionne si ouverte via window.open)
            window.close();
        }, 4000);
    </script>
    @endif
</body>
</html>
