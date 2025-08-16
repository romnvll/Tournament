<?php
session_start();
require_once 'class/databaseInformations.php';
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

if (!isset($_SESSION['captcha_question'])) {
    $a = rand(1, 10);
    $b = rand(1, 10);
    $_SESSION['captcha_answer'] = $a + $b;
    $_SESSION['captcha_question'] = "$a + $b = ?";
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des champs
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $captcha = $_POST['captcha'] ?? '';
    $cgu = isset($_POST['cgu']);

    if ($captcha != $_SESSION['captcha_answer']) {
        $errors[] = "Captcha incorrect.";
    }

    if (!$prenom || !$nom || !$email || !$password || !$confirm || !$cgu) {
        $errors[] = "Tous les champs sont requis.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide.";
    }

    if ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérifie si l'utilisateur existe déjà
    
    $stmt = $conn->prepare("SELECT id FROM Utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Cette adresse email est déjà utilisée.";
    }

    if (empty($errors)) {
       
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        // Insère l'utilisateur avec email non confirmé
        $stmt = $conn->prepare("INSERT INTO Utilisateurs (prenom, nom, email, password, email_token, email_confirme) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$prenom, $nom, $email, $hashed, $token]);

        // Envoie l'email de confirmation

         include('./config.php');
$mail->setFrom('noreply.hbcat@gmail.com', 'HBCAT');
$mail->isHTML(true);
$mail->addAddress($email, "$prenom $nom");

$mail->Subject = "[Brackito] - Confirmez votre adresse email";


// Body HTML
$mail->Body = "
<div style='font-family: Arial, sans-serif; color: #333;'>
    <div style='text-align: center; margin-bottom: 20px;'>
        <img src='https://brackito.net/logos/Logo.png' alt='Logo Brackito' style='max-width: 150px;'>
    </div>
    <h2 style='text-align: center; color: #0056b3;'>Bienvenue sur Brackito !</h2>
    <p>Bonjour $prenom $nom,</p>
    <p>Merci de t'être inscrit(e) sur notre plateforme de gestion de tournois.<br>
    Afin de finaliser ton inscription et de sécuriser ton compte, nous devons vérifier ton adresse e-mail.</p>
    <p style='text-align: center; margin: 30px 0;'>
        <a href='http://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . "/confirm_email.php?token=$token' 
           style='display: inline-block; padding: 12px 20px; background-color: #0056b3; color: #fff; 
                  text-decoration: none; border-radius: 6px; font-size: 16px;'>
            ✅ Vérifier mon e-mail
        </a>
    </p>
    <p>Si le bouton ne fonctionne pas, copie et colle le lien ci-dessous dans ton navigateur :</p>
    <p style='word-break: break-word;'>
        http://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . "/confirm_email.php?token=$token
    </p>
   
    <hr style='margin: 30px 0;'>
    <p style='text-align: center; font-size: 12px; color: #999;'>
        Cet e-mail a été envoyé automatiquement par Brackito. Merci de ne pas y répondre directement.
    </p>
</div>
";

// Corps alternatif (texte brut)
$mail->AltBody = "Bonjour $prenom $nom,\n\n"
               . "Merci de t'être inscrit(e) sur Brackito.\n"
               . "Pour vérifier ton adresse e-mail et activer ton compte, clique sur le lien suivant :\n\n"
               . "http://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . "/confirm_email.php?token=$token\n\n"
               . "Ce lien est valable pendant 24 heures.\n"
               . "Si tu n'es pas à l'origine de cette inscription, ignore simplement ce message.\n\n"
               . "--\n"
               . "L'équipe Brackito\n";


$mail->CharSet = 'UTF-8';
$mail->send();

$success = "Un email de confirmation a été envoyé.";

    }
}

// Affichage
echo $twig->render('register.twig', [
    'error' => $errors ? implode('<br>', $errors) : null,
    'success' => $success,
    'captcha_question' => $_SESSION['captcha_question']
]);
