<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'Lang/lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    require_once 'class/databaseInformations.php';

    $stmt = $conn->prepare("SELECT id, nom, prenom FROM Utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $utilisateur = $result->fetch_assoc();
    $stmt->close();

    if ($utilisateur) {
        $token = bin2hex(random_bytes(32));

        $stmt = $conn->prepare("UPDATE Utilisateurs SET email_token = ? WHERE id = ?");
        $stmt->bind_param("si", $token, $utilisateur['id']);
        $stmt->execute();
        $stmt->close();

        $resetLink = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/reinitialiserMotDePasse.php?token=" . urlencode($token);

        $email = $_POST['email'];
        $prenom = $utilisateur['prenom'] ?? '';
        $nom = $utilisateur['nom'] ?? '';

        include('./config.php');

        $mail->setFrom('romain@brackito.net', 'BRACKITO');
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->addAddress($email, "$prenom $nom");
        $mail->Subject = "[Brackito] - Réinitialisation de votre mot de passe";
        $mail->Body = "
            <p>Bonjour <strong>$prenom $nom</strong>,</p>
            <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
            <p>Cliquez sur le lien ci-dessous pour en choisir un nouveau :</p>
            <p><a href=\"$resetLink\">$resetLink</a></p>
            <p>Ce lien est valable une seule fois.</p>
            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message.</p>
            <br>
            <p>— L'équipe Brackito</p>
        ";

        if ($mail->send()) {
            $successMsg = t('motDePasseOublieResetSucess');
        } else {
            $errorMsg = t('echecEnvoiEmail');
        }
    } else {
        $errorMsg = t('mailNonReconnue');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Mot de passe oublié — Brackito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: rgba(79, 70, 229, 0.12);
            --accent: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --bg-dark: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ── Fond animé blobs ── */
        .bg-blobs {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: floatBlob linear infinite;
        }

        .blob-1 { width: 500px; height: 500px; background: #818cf8; top: -150px; left: -100px; animation-duration: 18s; }
        .blob-2 { width: 380px; height: 380px; background: #c084fc; bottom: -100px; right: -80px; animation-duration: 22s; animation-direction: reverse; }
        .blob-3 { width: 260px; height: 260px; background: #60a5fa; top: 40%; left: 60%; animation-duration: 15s; }

        @keyframes floatBlob {
            0%   { transform: translate(0, 0) scale(1); }
            33%  { transform: translate(40px, -30px) scale(1.05); }
            66%  { transform: translate(-20px, 40px) scale(0.95); }
            100% { transform: translate(0, 0) scale(1); }
        }

        /* ── Icônes sportives flottantes ── */
        .sport-icons { position: fixed; inset: 0; pointer-events: none; z-index: 0; }

        .sport-icon {
            position: absolute;
            font-size: 1.4rem;
            opacity: 0.12;
            color: white;
            animation: driftIcon linear infinite;
        }

        @keyframes driftIcon {
            0%   { transform: translateY(0px) rotate(0deg); opacity: 0.08; }
            50%  { opacity: 0.18; }
            100% { transform: translateY(-30px) rotate(20deg); opacity: 0.08; }
        }

        /* ── Wrapper ── */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 20px;
            animation: cardIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Card ── */
        .login-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow:
                0 25px 60px rgba(79, 70, 229, 0.25),
                0 0 0 1px rgba(255,255,255,0.6) inset;
            overflow: hidden;
        }

        /* ── Hero header ── */
        .card-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 36px 40px 28px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-hero::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }

        .card-hero::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -30px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }

        .logo-link {
            display: inline-block;
            position: relative;
            z-index: 2;
            margin-bottom: 14px;
            transition: transform 0.3s ease;
        }

        .logo-link:hover { transform: scale(1.05); }

        .logo-img {
            max-height: 64px;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.25));
            border-radius: 5px;
        }

        .hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
            letter-spacing: 0.03em;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 5px 14px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 2;
        }

        /* ── Corps formulaire ── */
        .card-body-custom {
            padding: 32px 40px 36px;
        }

        /* Description */
        .reset-description {
            background: linear-gradient(135deg, var(--primary-light), rgba(118, 75, 162, 0.08));
            border: 1px solid rgba(79, 70, 229, 0.2);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.88rem;
            color: var(--bg-dark);
            line-height: 1.5;
        }

        .reset-description i {
            color: var(--primary);
            font-size: 1.1rem;
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* Alertes */
        .alert-modern {
            border-radius: 12px;
            padding: 13px 16px;
            margin-bottom: 22px;
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger-modern {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: var(--danger);
        }

        .alert-success-modern {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: var(--success);
        }

        /* Champ */
        .field-group { margin-bottom: 20px; }

        .field-label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--bg-dark);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        .field-label i { color: var(--primary); font-size: 0.85rem; }

        .field-input {
            width: 100%;
            padding: 13px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--bg-dark);
            background: #fafafa;
            transition: all 0.25s ease;
            outline: none;
        }

        .field-input:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .field-input::placeholder { color: #b0b7c3; font-weight: 400; }

        /* Bouton submit */
        .btn-submit {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(79, 70, 229, 0.45); }
        .btn-submit:hover::before { opacity: 1; }
        .btn-submit:active { transform: translateY(0); }

        /* Lien retour */
        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 18px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .back-link:hover { opacity: 0.75; color: var(--primary); }

        /* Footer */
        .card-footer-custom {
            background: #f9fafb;
            border-top: 1px solid var(--border);
            padding: 14px 40px;
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-wrapper { padding: 16px; }
            .card-hero { padding: 28px 24px 22px; }
            .card-body-custom { padding: 24px 24px 28px; }
            .card-footer-custom { padding: 14px 24px; }
        }
    </style>
</head>
<body>

    <!-- Fond animé -->
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <!-- Icônes sportives flottantes -->
    <div class="sport-icons" aria-hidden="true">
        <i class="fas fa-futbol sport-icon"       style="top:10%; left:8%;  animation-duration:6s;  animation-delay:0s;"></i>
        <i class="fas fa-basketball sport-icon"   style="top:25%; left:90%; animation-duration:8s;  animation-delay:1s;"></i>
        <i class="fas fa-volleyball sport-icon"   style="top:60%; left:5%;  animation-duration:7s;  animation-delay:2s;"></i>
        <i class="fas fa-trophy sport-icon"       style="top:75%; left:85%; animation-duration:9s;  animation-delay:0.5s;"></i>
        <i class="fas fa-medal sport-icon"        style="top:5%;  left:55%; animation-duration:5s;  animation-delay:3s;"></i>
        <i class="fas fa-flag-checkered sport-icon" style="top:85%; left:40%; animation-duration:10s; animation-delay:1.5s;"></i>
        <i class="fas fa-stopwatch sport-icon"    style="top:45%; left:92%; animation-duration:7s;  animation-delay:4s;"></i>
        <i class="fas fa-star sport-icon"         style="top:50%; left:2%;  animation-duration:11s; animation-delay:2.5s;"></i>
        <i class="fas fa-shield-halved sport-icon" style="top:88%; left:15%; animation-duration:6s; animation-delay:0.8s;"></i>
        <i class="fas fa-ranking-star sport-icon"  style="top:15%; left:75%; animation-duration:9s; animation-delay:3.5s;"></i>
    </div>

    <!-- Card -->
    <div class="login-wrapper">
        <div class="login-card">

            <!-- Hero -->
            <div class="card-hero">
                <a href="index.php" class="logo-link">
                    <img src="logos/Logo.png" class="logo-img" alt="Brackito">
                </a>
                <p class="hero-title">Mot de passe oublié</p>
                <div class="hero-badge">
                    <i class="fas fa-key"></i>
                    Réinitialisation sécurisée
                </div>
            </div>

            <!-- Formulaire -->
            <div class="card-body-custom">

                <!-- Description -->
                <div class="reset-description">
                    <i class="fas fa-circle-info"></i>
                    <span>Saisissez votre adresse e-mail et nous vous enverrons un lien pour réinitialiser votre mot de passe.</span>
                </div>

                <?php if (isset($successMsg)) : ?>
                    <div class="alert-modern alert-success-modern">
                        <i class="fas fa-circle-check"></i>
                        <?= $successMsg ?>
                    </div>
                <?php elseif (isset($errorMsg)) : ?>
                    <div class="alert-modern alert-danger-modern">
                        <i class="fas fa-triangle-exclamation"></i>
                        <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="field-group">
                        <label class="field-label" for="email">
                            <i class="fas fa-envelope"></i>
                            <?= t('adresseMail') ?>
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="field-input"
                            placeholder="votre@email.com"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        <?= t('envoyer') ?>
                    </button>

                    <a href="Auth/" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        Retour à la connexion
                    </a>
                </form>

            </div>

            <!-- Footer -->
            <div class="card-footer-custom">
                <i class="fas fa-trophy" style="color: #f59e0b; margin-right: 5px;"></i>
                Brackito &mdash; Gestion de tournois sportifs
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>