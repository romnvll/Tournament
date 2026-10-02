<?php
session_start();
require_once 'class/databaseInformations.php';

// Initialiser les messages
$error = '';
$success = '';

// Étape 1 : Récupérer le token depuis l'URL
$token = $_GET['token'] ?? '';

// Étape 2 : Si le token est manquant ou vide
if (empty($token)) {
    $error = "Lien invalide.";
}

// Étape 3 : Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['new_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $token = $_POST['token'];

    if ($newPassword !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($newPassword) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier le token en base
        $stmt = $conn->prepare("SELECT id FROM Utilisateurs WHERE email_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Hacher le mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Mettre à jour le mot de passe et réinitialiser le token
            $stmt = $conn->prepare("UPDATE Utilisateurs SET password = ?, email_token = NULL WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $user['id']);
            $stmt->execute();
            $stmt->close();

            $success = "Votre mot de passe a été réinitialisé avec succès. <a href='Auth/'>Se connecter</a>";
        } else {
            $error = "Le lien de réinitialisation est invalide ou expiré.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reset-card {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            border-radius: 1rem;
            background-color: white;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
<div class="reset-card">

      <div class="text-center">
        <img src="logos/Logo.png" class="logo-img img-fluid" alt="Logo">
    </div>

    <h5 class="text-center mb-4">Réinitialiser le mot de passe</h5>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!$success && $token): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

       

            <div class="mb-3">
                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary">Réinitialiser</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
