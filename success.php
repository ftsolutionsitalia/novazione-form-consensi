<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richiesta Inviata - Novazione</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .result-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, var(--primary-900) 0%, var(--primary-700) 50%, var(--primary-500) 100%);
        }

        .result-card {
            background: white;
            border-radius: var(--radius-2xl);
            padding: 60px 50px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: var(--shadow-2xl);
            animation: fadeInUp 0.6s ease;
        }

        .result-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
        }

        .result-icon.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            animation: bounceIn 0.6s ease 0.3s both;
        }

        .result-icon.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .result-title {
            font-size: 28px;
            color: var(--gray-900);
            margin-bottom: 15px;
        }

        .result-message {
            color: var(--gray-500);
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .result-hash {
            background: var(--gray-100);
            padding: 15px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
        }

        .result-hash .label {
            font-size: 12px;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
        }

        .result-hash .value {
            font-family: monospace;
            font-size: 14px;
            color: var(--primary-600);
            word-break: break-all;
        }

        .result-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all var(--transition-normal);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--gray-300);
            color: var(--gray-600);
        }

        .btn-outline:hover {
            border-color: var(--primary-500);
            color: var(--primary-600);
        }

        .checklist {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background: var(--primary-50);
            border-radius: var(--radius-lg);
            border: 1px solid var(--primary-200);
        }

        .checklist-title {
            font-weight: 600;
            color: var(--primary-800);
            margin-bottom: 15px;
            font-size: 14px;
        }

        .checklist-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            color: var(--gray-600);
            font-size: 14px;
        }

        .checklist-item i {
            color: var(--success);
            margin-top: 3px;
        }

        .footer-note {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-400);
            font-size: 13px;
        }

        .footer-note a {
            color: var(--primary-500);
        }

        @media (max-width: 480px) {
            .result-card {
                padding: 40px 25px;
            }

            .result-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="result-page">
        <div class="result-card">
            <div class="result-icon success">
                <i class="fas fa-check"></i>
            </div>

            <h1 class="result-title">Richiesta Inviata!</h1>

            <p class="result-message">
                Grazie per averci contattato. La tua richiesta è stata registrata con successo.
                Ti abbiamo inviato un'email di conferma con una copia del documento di consenso.
            </p>

            <?php if (isset($_GET['hash']) && !empty($_GET['hash'])): ?>
            <div class="result-hash">
                <div class="label">Identificativo Pratica</div>
                <div class="value"><?php echo htmlspecialchars($_GET['hash']); ?></div>
            </div>
            <?php endif; ?>

            <div class="checklist">
                <div class="checklist-title">
                    <i class="fas fa-shield-alt"></i> Cosa abbiamo fatto per te:
                </div>
                <div class="checklist-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Registrato il tuo consenso con marca temporale</span>
                </div>
                <div class="checklist-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Generato un documento PDF certificato con hash di integrità</span>
                </div>
                <div class="checklist-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Inviato una copia del documento alla tua email</span>
                </div>
                <div class="checklist-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Il nostro team ti contatterà entro 24 ore</span>
                </div>
            </div>

            <div class="result-actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Torna alla Home
                </a>
                <a href="index.php#contact" class="btn btn-outline">
                    <i class="fas fa-paper-plane"></i>
                    Nuova Richiesta
                </a>
            </div>

            <div class="footer-note">
                <p>
                    <i class="fas fa-envelope"></i>
                    Non hai ricevuto l'email? Controlla la cartella spam oppure
                    <a href="mailto:<?php echo AZIENDA_EMAIL ?? 'info@novazione.it'; ?>">contattaci</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
