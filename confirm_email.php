<?php
require_once 'class/databaseInformations.php';
require_once 'vendor/autoload.php';
try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ]
    );
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    exit;
}


if (isset($_GET['token'])) {
    $token = $_GET['token'];

    //to do : affecter l'utilisateur à une licence
    require_once 'class/licenceDao.class.php';
    require_once 'class/categorie.class.php';
    


    $licenceDao = new LicenceDao();
    $categorie = new CategorieDao();
    
    
    $userId = $licenceDao->getIdUserByToken($token);
    $userinfo = $licenceDao->getUserInfosByToken($token);
    
    $email = $userinfo['email'];
    $prenom = $userinfo['prenom'];
    $nom = $userinfo['nom'];
    



    $licenceDao->creerLicenceParDefautFromToken($token);
   $stmt = $conn->prepare("UPDATE Utilisateurs SET email_confirme = 1 WHERE email_token = :token");
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        

$categorie->creerCategorie('MiniDebutants',     '#FFC300', $userId);
$categorie->creerCategorie('MiniDebrouillards', '#DAF7A6', $userId);
$categorie->creerCategorie('MiniConfirmés',     '#FFC300', $userId);
$categorie->creerCategorie('U11Mixte',          '#FF6F61', $userId);
$categorie->creerCategorie('U11F',              '#FF7F7F', $userId);
$categorie->creerCategorie('U11M',              '#F9E79F', $userId);
$categorie->creerCategorie('U13M',              '#AED6F1', $userId);
$categorie->creerCategorie('U13F',              '#85C1E9', $userId);
$categorie->creerCategorie('U13Mixte',          '#AED6F1', $userId);
$categorie->creerCategorie('U15F',              '#48C9B0', $userId);
$categorie->creerCategorie('U15M',              '#73C6B6', $userId);
$categorie->creerCategorie('U17F',              '#F39C12', $userId);
$categorie->creerCategorie('U17M',              '#E67E22', $userId);
$categorie->creerCategorie('Seniors M',         '#F5B041', $userId);
$categorie->creerCategorie('Seniors F',         '#E57373', $userId);
$categorie->creerCategorie('Loisirs',           '#FF7F7F', $userId);


        

        //Envoie de mail pour donner les liens

  include('./config.php');
$mail->setFrom('noreply.hbcat@gmail.com', 'HBCAT');
$mail->isHTML(true);
$mail->addAddress($email, "$prenom $nom");

$mail->Subject = "[Brackito] - Votre lien de connexion";


// Corps HTML
$mail->isHTML(true);
$mail->Body = "
<div style='font-family: Arial, sans-serif; color: #333;'>
    <div style='text-align: center; margin-bottom: 20px;'>
        <img src='https://brackito.net/logos/Logo.png' alt='Logo Brackito' style='max-width: 150px;'>
    </div>
    <h2 style='text-align: center; color: #0056b3;'>Accédez à votre plateforme de gestion de tournoi</h2>
    <p>Bonjour $prenom $nom,</p>
    <p>Comme convenu, voici le lien direct pour accéder à votre espace de gestion de tournoi :</p>
    <p style='text-align: center; margin: 30px 0;'>
        <a href='https://brackito.net/Auth' 
           style='display: inline-block; padding: 12px 20px; background-color: #28a745; color: #fff; 
                  text-decoration: none; border-radius: 6px; font-size: 16px;'>
            🔗 Accéder à ma plateforme
        </a>
    </p>
    <p>Si le bouton ne fonctionne pas, vous pouvez également copier/coller ce lien dans votre navigateur :</p>
    <p style='word-break: break-word;'>
        https://brackito.net/Auth
    </p>
    <p style='margin-top: 30px; font-size: 14px; color: #555;'>
        ℹ️ Pour plus de sécurité, conservez ce lien précieusement et ne le partagez pas avec des personnes non autorisées.
    </p>
    <hr style='margin: 30px 0;'>
    <p style='text-align: center; font-size: 12px; color: #999;'>
        Cet e-mail a été envoyé automatiquement par Brackito. Merci de ne pas y répondre directement.
    </p>
</div>
";

// Corps alternatif (texte brut)
$mail->AltBody = "Bonjour $prenom $nom,\n\n"
               . "Voici le lien pour accéder à votre plateforme de gestion de tournoi :\n\n"
               . "https://brackito.net/Auth\n\n"
               . "Pour plus de sécurité, conservez ce lien précieusement et évitez de le partager.\n\n"
               . "--\n"
               . "L'équipe Brackito\n";



$mail->CharSet = 'UTF-8';
$mail->send();
echo "Adresse email confirmée avec succès !";
sleep(2);

header("Location: Auth/login.php?confirmation=success");
        //Fin envoie de mail

        exit();
        
    } else {
        echo "Lien invalide ou déjà utilisé.";
    }
}
