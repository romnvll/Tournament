<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'Lang/lang.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Ici, tu devrais normalement vérifier que l'email existe en base
    require_once 'class/databaseInformations.php';

    $stmt = $conn->prepare("SELECT id, nom, prenom FROM Utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $utilisateur = $result->fetch_assoc();
    $stmt->close();

    if ($utilisateur) {
    // 1. Générer un token sécurisé
    $token = bin2hex(random_bytes(32)); // 64 caractères aléatoires

    // 2. Sauvegarder le token en base de données
    $stmt = $conn->prepare("UPDATE Utilisateurs SET email_token = ? WHERE id = ?");
    $stmt->bind_param("si", $token, $utilisateur['id']);
    $stmt->execute();
    $stmt->close();

    // 3. Créer le lien de réinitialisation
    $resetLink = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/reinitialiserMotDePasse.php?token=" . urlencode($token);

    // 4. Envoi de l’email
    $email = $_POST['email'];
    $prenom = $utilisateur['prenom'] ?? '';
    $nom = $utilisateur['nom'] ?? '';

    include('./config.php'); // Fichier où PHPMailer est configuré

    $mail->setFrom('romain@brackito.net', 'BRACKITO');
    $mail->CharSet = 'UTF-8'; // 👈 ajoute cette ligne
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
    $errorMsg =  t('mailNonReconnue') ;
}

}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe perdu</title>
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

        .password-reset-card {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            background-color: white;
        }

        .logo-img {
            max-width: 150px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="password-reset-card">
    <div class="text-center">
        <a href="index.php"><img src="logos/Logo.png" class="logo-img img-fluid" alt="Logo"></a>
        <h5 class="mb-4"><?= t('motdePasseOublie') ?></h5>
    </div>

    <?php if (isset($successMsg)) : ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php elseif (isset($errorMsg)) : ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="email" class="form-label"><?= t('adresseMail') ?></label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="email" class="form-control" placeholder="<?= t('adresseMail') ?>" required>
            </div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-1"></i> <?= t('envoyer') ?> 
            </button>
        </div>
        <div class="text-center mt-3">
            <a href="Auth/" class="text-decoration-none">
                <i class="bi bi-arrow-left-circle me-1"></i> Retour à la connexion
            </a>
        </div>
    </form>
</div>
</body>
</html>
