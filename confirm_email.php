<?php
require_once 'class/databaseInformations.php';
try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
$categorie->creerCategorie('MiniDebutants',     '#FFC300', $userId);



    $licenceDao->creerLicenceParDefautFromToken($token);
    $stmt = $conn->prepare("UPDATE Utilisateurs SET email_confirme = 1 WHERE email_token = ?");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        echo "Adresse email confirmée avec succès !";
        // Rediriger vers la page de connexion ou une autre page
        header("Location: Auth/");
        exit();
        
    } else {
        echo "Lien invalide ou déjà utilisé.";
    }
}
