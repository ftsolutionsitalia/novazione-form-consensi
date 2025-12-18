<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errore - Novazione</title>
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
            background: linear-gradient(135deg, var(--gray-800) 0%, var(--gray-700) 50%, var(--gray-600) 100%);
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            animation: shake 0.6s ease 0.3s both;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
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

        .error-details {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 15px 20px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            text-align: left;
        }

        .error-details .label {
            font-size: 12px;
            color: #991b1b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .error-details .value {
            font-size: 14px;
            color: #b91c1c;
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

        .help-section {
            margin-top: 30px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
        }

        .help-section-title {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 15px;
            font-size: 14px;
        }

        .help-list {
            text-align: left;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .help-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            color: var(--gray-600);
            font-size: 14px;
        }

        .help-list li i {
            color: var(--primary-500);
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
            <div class="result-icon">
                <i class="fas fa-times"></i>
            </div>

            <h1 class="result-title">Si è verificato un errore</h1>

            <p class="result-message">
                Non siamo riusciti a elaborare la tua richiesta. Ti preghiamo di riprovare o contattarci direttamente.
            </p>

            <?php if (isset($_GET['message']) && !empty($_GET['message'])): ?>
            <div class="error-details">
                <div class="label"><i class="fas fa-exclamation-triangle"></i> Dettagli errore</div>
                <div class="value"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></div>
            </div>
            <?php endif; ?>

            <div class="result-actions">
                <a href="index.php#contact" class="btn btn-primary">
                    <i class="fas fa-redo"></i>
                    Riprova
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home"></i>
                    Torna alla Home
                </a>
            </div>

            <div class="help-section">
                <div class="help-section-title">
                    <i class="fas fa-question-circle"></i> Possibili soluzioni:
                </div>
                <ul class="help-list">
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Verifica che tutti i campi obbligatori siano compilati</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Assicurati che l'email inserita sia valida</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Verifica che il numero di telefono sia nel formato corretto</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Ricorda di accettare l'informativa sulla privacy</span>
                    </li>
                </ul>
            </div>

            <div class="footer-note">
                <p>
                    <i class="fas fa-headset"></i>
                    Se il problema persiste,
                    <a href="mailto:info@novazione.it">contattaci</a> direttamente
                    oppure chiamaci al <a href="tel:+390212345678">+39 02 1234567</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
