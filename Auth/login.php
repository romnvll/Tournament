<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    $httpsUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $httpsUrl", true, 301);
    exit();
}

ob_start();
session_start();
require_once '../class/databaseInformations.php';
require_once '../class/utilisateurDao.class.php';
require_once '../Lang/lang.php';

if (!isset($_SESSION['captcha_question'])) {
    $a = rand(1, 10);
    $b = rand(1, 10);
    $_SESSION['captcha_answer'] = $a + $b;
    $_SESSION['captcha_question'] = "$a + $b = ?";
}

if (isset($_COOKIE['auth'])) {
    header("Location: ../tableauDeBord.php");
    exit;
}

if (isset($_POST['btn-login'])) {
    if (!isset($_POST['captcha']) || $_POST['captcha'] != $_SESSION['captcha_answer']) {
        $errMSG = "Captcha incorrect. Veuillez réessayer.";
        $a = rand(1, 10);
        $b = rand(1, 10);
        $_SESSION['captcha_answer'] = $a + $b;
        $_SESSION['captcha_question'] = "$a + $b = ?";
    } else {
        $email = $_POST['email'];
        $upass = $_POST['pass'];

        $stmt = $conn->prepare("
            SELECT u.id, u.nom, u.prenom, u.email, u.role, u.email_confirme, u.password
            FROM Utilisateurs u
            WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();

        $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
        $count = $res->num_rows;

        if ($count == 1 && password_verify($upass, $row['password']) && $row['email_confirme'] == 1) {
            require_once '../secureCookies.php';
            $data = [
                'id' => $row['id'],
                'email' => $row['email'],
                'nom' => $row['nom'],
                'prenom' => $row['prenom'],
                'role' => $row['role'],
                'exp' => time() + (48 * 60 * 60)
            ];
            $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $signature = hash_hmac('sha256', $payload, $secret);
            $token = base64_encode(json_encode([
                'payload' => $payload,
                'signature' => $signature
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            setcookie('auth', $token, time() + (48 * 60 * 60), "/", "", false, true);

            $stmtTournois = $conn->prepare("SELECT COUNT(*) AS total, SUM(isArchived) AS archived FROM Tournois");
            $stmtTournois->execute();
            $resultTournois = $stmtTournois->get_result();
            $dataTournois = $resultTournois->fetch_assoc();
            $stmtTournois->close();
            $user = new UtilisateurDAO();

            header("Location: ../ajoutTournoi.php");
            $user->mettreAJourDerniereConnexion($row['id']);
            exit;
        } elseif ($count == 1) {
            $errMSG = "Mauvais mot de passe";
        } else {
            $errMSG = "Mail non trouvé";
        }

        $a = rand(1, 10);
        $b = rand(1, 10);
        $_SESSION['captcha_answer'] = $a + $b;
        $_SESSION['captcha_question'] = "$a + $b = ?";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Connexion — Brackito</title>
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
    overflow-x: hidden;   /* remplace overflow: hidden */
    overflow-y: auto;     /* autorise le scroll vertical */
    position: relative;
}

        /* ── Fond animé ── */
        .bg-blobs {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .mobile-warning {
    display: none;   /* seule déclaration ici */
    position: relative;
    z-index: 10;
    max-width: 440px;
    margin: 0 auto 12px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(245, 158, 11, 0.4);
    border-radius: 12px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #92400e;
    gap: 10px;
    align-items: center;
    /* on retire le display: flex; ici */
}

@media (max-width: 480px) {
    .mobile-warning {
        display: flex;   /* uniquement appliqué sous 480px */
    }
}


        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: floatBlob linear infinite;
        }

        .blob-1 {
            width: 500px; height: 500px;
            background: #818cf8;
            top: -150px; left: -100px;
            animation-duration: 18s;
        }
        .blob-2 {
            width: 380px; height: 380px;
            background: #c084fc;
            bottom: -100px; right: -80px;
            animation-duration: 22s;
            animation-direction: reverse;
        }
        .blob-3 {
            width: 260px; height: 260px;
            background: #60a5fa;
            top: 40%; left: 60%;
            animation-duration: 15s;
        }

        @keyframes floatBlob {
            0%   { transform: translate(0, 0) scale(1); }
            33%  { transform: translate(40px, -30px) scale(1.05); }
            66%  { transform: translate(-20px, 40px) scale(0.95); }
            100% { transform: translate(0, 0) scale(1); }
        }

        /* ── Particules sportives ── */
        .sport-icons {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

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

        /* ── Card ── */
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

        .login-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow:
                0 25px 60px rgba(79, 70, 229, 0.25),
                0 0 0 1px rgba(255,255,255,0.6) inset;
            overflow: hidden;
        }

        /* ── Header de la card ── */
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

        /* ── Formulaire ── */
        .card-body-custom {
            padding: 32px 40px 36px;
        }

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

        .field-group {
            margin-bottom: 20px;
        }

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

        .field-label i {
            color: var(--primary);
            font-size: 0.85rem;
        }

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

        .field-input::placeholder {
            color: #b0b7c3;
            font-weight: 400;
        }

        /* Captcha */
        .captcha-block {
            background: linear-gradient(135deg, var(--primary-light), rgba(118, 75, 162, 0.08));
            border: 1px solid rgba(79, 70, 229, 0.2);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .captcha-question {
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .captcha-input {
            width: 80px;
            flex-shrink: 0;
            padding: 10px 14px;
            border: 2px solid rgba(79, 70, 229, 0.25);
            border-radius: 9px;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--bg-dark);
            background: white;
            text-align: center;
            outline: none;
            transition: all 0.25s;
        }

        .captcha-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        /* Bouton submit */
        .btn-login {
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

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(79, 70, 229, 0.45);
        }

        .btn-login:hover::before { opacity: 1; }

        .btn-login:active { transform: translateY(0); }

        /* Lien mot de passe oublié */
        .forgot-link {
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

        .forgot-link:hover { opacity: 0.75; color: var(--primary); }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: #d1d5db;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Footer card */
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
            .captcha-block { flex-direction: column; align-items: center; text-align: center; }
            .captcha-input { width: 100px; }
        }
    </style>
</head>
<body>

    <!-- Fond animé : blobs -->
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <!-- Icônes sportives flottantes -->
    <div class="sport-icons" aria-hidden="true">
        <i class="fas fa-futbol sport-icon"  style="top:10%; left:8%;  animation-duration:6s;  animation-delay:0s;"></i>
        <i class="fas fa-basketball sport-icon" style="top:25%; left:90%; animation-duration:8s;  animation-delay:1s;"></i>
        <i class="fas fa-volleyball sport-icon" style="top:60%; left:5%;  animation-duration:7s;  animation-delay:2s;"></i>
        <i class="fas fa-trophy sport-icon"  style="top:75%; left:85%; animation-duration:9s;  animation-delay:0.5s;"></i>
        <i class="fas fa-medal sport-icon"   style="top:5%;  left:55%; animation-duration:5s;  animation-delay:3s;"></i>
        <i class="fas fa-flag-checkered sport-icon" style="top:85%; left:40%; animation-duration:10s; animation-delay:1.5s;"></i>
        <i class="fas fa-stopwatch sport-icon" style="top:45%; left:92%; animation-duration:7s;  animation-delay:4s;"></i>
        <i class="fas fa-star sport-icon"    style="top:50%; left:2%;  animation-duration:11s; animation-delay:2.5s;"></i>
        <i class="fas fa-shield-halved sport-icon" style="top:88%; left:15%; animation-duration:6s; animation-delay:0.8s;"></i>
        <i class="fas fa-ranking-star sport-icon"  style="top:15%; left:75%; animation-duration:9s; animation-delay:3.5s;"></i>
    </div>
<div class="mobile-warning">
    <i class="fas fa-circle-info" style="color:#f59e0b; font-size:1.1rem; flex-shrink:0;"></i>
    <span>L'espace organisateur est conçu pour ordinateur. Sur téléphone, certaines fonctionnalités peuvent être plus difficiles à utiliser.</span>
</div>
    <!-- Card de connexion -->
    <div class="login-wrapper">
        <div class="login-card">

            <!-- Hero header -->
            <div class="card-hero">
                <a href="../index.php" class="logo-link">
                    <img src="../logos/Logo.png" class="logo-img" alt="Brackito">
                </a>
                <p class="hero-title">Espace organisateur</p>
                <div class="hero-badge">
                    <i class="fas fa-shield-halved"></i>
                    Accès sécurisé
                </div>
            </div>

            <!-- Corps du formulaire -->
            <div class="card-body-custom">

                <?php if (isset($_GET['confirmation']) && $_GET['confirmation'] == 'success') : ?>
                    <div class="alert-modern alert-success-modern">
                        <i class="fas fa-circle-check"></i>
                        <?= t('confirmationAdresseMail') ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($errMSG)) : ?>
                    <div class="alert-modern alert-danger-modern">
                        <i class="fas fa-triangle-exclamation"></i>
                        <?php echo htmlspecialchars($errMSG); ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="on">

                    <!-- Email -->
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

                    <!-- Mot de passe -->
                    <div class="field-group">
                        <label class="field-label" for="pass">
                            <i class="fas fa-lock"></i>
                            <?= t('motDePasse') ?>
                        </label>
                        <input
                            type="password"
                            name="pass"
                            id="pass"
                            class="field-input"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <!-- Captcha -->
                    <div class="captcha-block">
                        <span class="captcha-question">
                            <i class="fas fa-robot" style="opacity:.6; margin-right:6px;"></i>
                            <?php echo $_SESSION['captcha_question']; ?>
                        </span>
                        <input
                            type="text"
                            name="captcha"
                            id="captcha"
                            class="captcha-input"
                            placeholder="?"
                            required
                            inputmode="numeric"
                            autocomplete="off"
                        >
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-login" name="btn-login">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        <?= t('login') ?>
                    </button>

                    <!-- Mot de passe oublié -->
                    <a href="../lostPassword.php" class="forgot-link">
                        <i class="fas fa-key"></i>
                        <?= t('motdePasseOublie') ?>
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