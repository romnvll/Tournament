<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    $httpsUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $httpsUrl", true, 301);
    exit();
}

ob_start();
session_start();
require_once '../class/databaseInformations.php';

// Générer une question captcha si non définie
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
       
// Connexion à la base de données
    $stmt = $conn->prepare("
    SELECT 
    u.id,
    u.nom,
    u.prenom,
    u.email,
    u.email_confirme,
    u.password    
FROM Utilisateurs u

WHERE email =  ? 
");

$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();



//fin

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

            if ($dataTournois['total'] > 0 && $dataTournois['total'] == $dataTournois['archived']) {
                header("Location: ../ajoutTournoi.php");
            } else {
                header("Location: ../ajoutTournoi.php");
                
            }
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
    <title>Connexion Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            background-color: white;
        }
        .logo-img {
            max-width: 150px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="login-card">
    <form method="post" autocomplete="on">
        <div class="text-center">
            <img src="../logos/Logo.png" class="logo-img img-fluid" alt="Logo">
        </div>

        <?php if (isset($errMSG)) : ?>
            <div class="alert alert-danger mt-3">
                <?php echo $errMSG; ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label" for="email">Adresse email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="pass">Mot de passe</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="pass" id="pass" class="form-control" placeholder="Mot de passe" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="captcha">Captcha : <?php echo $_SESSION['captcha_question']; ?></label>
            <input type="text" name="captcha" id="captcha" class="form-control" placeholder="Réponse" required>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary" name="btn-login">
                <i class="bi bi-box-arrow-in-right me-1"></i> Connexion
            </button>
        </div>
        <div class="text-center mt-3">
    <a href="../lostPassword.php" class="text-decoration-none">
        <i class="bi bi-key me-1"></i> Mot de passe perdu ?
    </a>
        </div>

        
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
