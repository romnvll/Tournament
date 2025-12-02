<?php
require 'security.php';
require_once 'class/categorie.class.php';

$categorieDao = new CategorieDao();


if (isset($_POST['ajoutSponsor']) && $_POST['ajoutSponsor'] == '1') {
    $club_id = $_POST['club_id'];
    require_once 'class/SponsorDAO.class.php';
    $sponsorDao = new SponsorDAO();

   
    // Vérifier si un fichier a été téléchargé sans erreur
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'Sponsors/'; // Répertoire où vous souhaitez enregistrer les fichiers
        $nom = $_POST['nom'];

        // Créer le nouveau nom de fichier
        $newFileName = $club_id . '-Sponsors-' . $nom;
        echo $newFileName;
        // Obtenir l'extension du fichier
        $fileExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);

        // Déplacer le fichier téléchargé vers le répertoire souhaité avec le nouveau nom
        $destination = $uploadDir . $newFileName . '.' . $fileExtension;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
            echo "Le fichier a été téléchargé avec succès.";
        } else {
            echo "Une erreur est survenue lors du téléchargement du fichier.";
        }
    }

     $sponsorDao->ajouterSponsor(
        $_POST['nom'],
        $_POST['description'] ?? null,
        $_POST['lien_web'],
        $destination ?? null,
        $club_id,
        $_POST['telephone'] ?? null,
        $_POST['adresse'] ?? null
    ); 
        header("Location: " . $_SERVER['HTTP_REFERER']);
}

//modif d'un sponsor
if (isset($_POST['modifierSponsor']) && $_POST['modifierSponsor'] == '1') {
    require_once 'class/SponsorDAO.class.php';
    $sponsorDao = new SponsorDAO();

    // Récupérer l'ancien logo du sponsor
    $oldSponsorData = $sponsorDao->getSponsorById((int)$_POST['id']);
    $logo = $oldSponsorData['logo']; // Conserver l'ancien logo par défaut

    // Vérifier si un nouveau fichier a été téléchargé sans erreur
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'Sponsors/';
        $nom = $_POST['nom'];
        $newFileName = $_POST['club_id'] . '-Sponsors-' . $nom;
        $fileExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $destination = $uploadDir . $newFileName . '.' . $fileExtension;

        // Déplacer le fichier téléchargé vers le répertoire souhaité avec le nouveau nom
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
            $logo = $destination; // Mettre à jour le logo avec le nouveau fichier
        } else {
            echo "Une erreur est survenue lors du téléchargement du fichier.";
        }
    }

    // Appeler la méthode modifierSponsor avec le logo approprié
    $sponsorDao->modifierSponsor(
        (int)$_POST['id'],
        $_POST['nom'],
        $_POST['description'] ?? null,
        $_POST['lien_web'],
        $logo,
        (int)$_POST['club_id'],
        $_POST['telephone'] ?? null,
        $_POST['adresse'] ?? null
    );


if (isset($_POST['actif']) && isset($_POST['id'])) {
    require_once 'class/SponsorDAO.class.php';
    $sponsorDao = new SponsorDAO();
    $sponsorDao->modifierEtatActif((int)$_POST['id'], 1);
   
}
else {
    require_once 'class/SponsorDAO.class.php';
    $sponsorDao = new SponsorDAO();
    $sponsorDao->modifierEtatActif((int)$_POST['id'], 0);

}


   
    // Rediriger vers la page précédente
       header("Location: " . $_SERVER['HTTP_REFERER']);
}


//modification de l'état actif du sponsor


//fin modif sponsors
// ==================== GESTION DES EFFETS SONORES ====================

// Ajout effet sonore de DÉBUT
// Ajout effet sonore de DÉBUT depuis la banque
if (isset($_POST['ajouterEffetDebutBanque']) && $_POST['ajouterEffetDebutBanque'] == '1') {
    require_once 'class/utilisateurDao.class.php';
    $utilisateurDao = new UtilisateurDAO();
    
    $user_id = (int)$_POST['user_id'];
    $sonChoisi = $_POST['sonChoisi'];
    
    // Copier le son de la banque vers le répertoire de l'utilisateur
    $uploadDir = 'Audio/Effets/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = pathinfo($sonChoisi, PATHINFO_FILENAME);
    $newFileName = $user_id . '-debut-' . $fileName . '.mp3';
    $destination = $uploadDir . $newFileName;
    
    if (copy($sonChoisi, $destination)) {
        $utilisateurDao->ajouterEffetsSonores($user_id, $destination, null);
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Ajout effet sonore de FIN depuis la banque
if (isset($_POST['ajouterEffetFinBanque']) && $_POST['ajouterEffetFinBanque'] == '1') {
    require_once 'class/utilisateurDao.class.php';
    $utilisateurDao = new UtilisateurDAO();
    
    $user_id = (int)$_POST['user_id'];
    $sonChoisi = $_POST['sonChoisi'];
    
    // Copier le son de la banque vers le répertoire de l'utilisateur
    $uploadDir = 'Audio/Effets/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = pathinfo($sonChoisi, PATHINFO_FILENAME);
    $newFileName = $user_id . '-Fin-' . $fileName . '.mp3';
    $destination = $uploadDir . $newFileName;
    
    if (copy($sonChoisi, $destination)) {
        $utilisateurDao->ajouterEffetsSonores($user_id, null, $destination);
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}


if (isset($_POST['ajouterEffetDebut']) && $_POST['ajouterEffetDebut'] == '1') {
    require_once 'class/utilisateurDao.class.php';
    $utilisateurDao = new UtilisateurDAO();
    
    $user_id = (int)$_POST['user_id'];
    $uploadDir = 'Audio/Effets/';
    
    // Créer le répertoire s'il n'existe pas
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $destination = null;
    
    // Vérifier si un fichier a été uploadé
    if (isset($_FILES['effetDebutFile']) && $_FILES['effetDebutFile']['error'] == UPLOAD_ERR_OK) {
        $originalName = pathinfo($_FILES['effetDebutFile']['name'], PATHINFO_FILENAME);
        $newFileName = $user_id . '-debut-' . $originalName . '.mp3';
        $destination = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['effetDebutFile']['tmp_name'], $destination)) {
            $utilisateurDao->ajouterEffetsSonores($user_id, $destination, null);
        } else {
            echo "Erreur lors du téléchargement du fichier.";
        }
    }
    // Vérifier si un enregistrement a été fait
    elseif (isset($_POST['effetDebutRecorded']) && !empty($_POST['effetDebutRecorded'])) {
        $audioData = $_POST['effetDebutRecorded'];
        
        // Extraire le base64
        list($type, $data) = explode(';', $audioData);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        
        $newFileName = $user_id . '-debut-enregistrement-' . time() . '.mp3';
        $destination = $uploadDir . $newFileName;
        
        if (file_put_contents($destination, $data)) {
            $utilisateurDao->ajouterEffetsSonores($user_id, $destination, null);
        } else {
            echo "Erreur lors de l'enregistrement du fichier.";
        }
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Ajout effet sonore de FIN
if (isset($_POST['ajouterEffetFin']) && $_POST['ajouterEffetFin'] == '1') {
    require_once 'class/utilisateurDao.class.php';
    $utilisateurDao = new UtilisateurDAO();
    
    $user_id = (int)$_POST['user_id'];
    $uploadDir = 'Audio/Effets/';
    
    // Créer le répertoire s'il n'existe pas
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $destination = null;
    
    // Vérifier si un fichier a été uploadé
    if (isset($_FILES['effetFinFile']) && $_FILES['effetFinFile']['error'] == UPLOAD_ERR_OK) {
        $originalName = pathinfo($_FILES['effetFinFile']['name'], PATHINFO_FILENAME);
        $newFileName = $user_id . '-Fin-' . $originalName . '.mp3';
        $destination = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['effetFinFile']['tmp_name'], $destination)) {
            $utilisateurDao->ajouterEffetsSonores($user_id, null, $destination);
        } else {
            echo "Erreur lors du téléchargement du fichier.";
        }
    }
    // Vérifier si un enregistrement a été fait
    elseif (isset($_POST['effetFinRecorded']) && !empty($_POST['effetFinRecorded'])) {
        $audioData = $_POST['effetFinRecorded'];
        
        // Extraire le base64
        list($type, $data) = explode(';', $audioData);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        
        $newFileName = $user_id . '-Fin-enregistrement-' . time() . '.mp3';
        $destination = $uploadDir . $newFileName;
        
        if (file_put_contents($destination, $data)) {
            $utilisateurDao->ajouterEffetsSonores($user_id, null, $destination);
        } else {
            echo "Erreur lors de l'enregistrement du fichier.";
        }
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Suppression effet sonore de DÉBUT
if (isset($_GET['supprimerEffetDebut'])) {
    require_once 'class/utilisateurDao.class.php';
    $utilisateurDao = new UtilisateurDAO();
    
    // Récupérer l'utilisateur pour obtenir le chemin du fichier
    $utilisateur = $utilisateurDao->getUtilisateurById($userData['id']);
    
    if ($utilisateur && $utilisateur['effetsSonoreDebut']) {
        // Supprimer le fichier du serveur
        if (file_exists($utilisateur['effetsSonoreDebut'])) {
            unlink($utilisateur['effetsSonoreDebut']);
        }
        
        // Mettre à jour la base de données
        $utilisateurDao->supprimerEffetSonore($userData['id'], true);
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Suppression effet sonore de FIN
if (isset($_GET['supprimerEffetFin'])) {
    require_once 'class/utilisateurDao.class.php';
    $utilisateurDao = new UtilisateurDAO();
    
    // Récupérer l'utilisateur pour obtenir le chemin du fichier
    $utilisateur = $utilisateurDao->getUtilisateurById($userData['id']);
  
    if ($utilisateur && $utilisateur['effetsSonoreFin']) {
        // Supprimer le fichier du serveur
        if (file_exists($utilisateur['effetsSonoreFin'])) {
            unlink($utilisateur['effetsSonoreFin']);
        }
        
        // Mettre à jour la base de données avec le paramètre pour effetDebut = false
        $utilisateurDao->supprimerEffetSonore($userData['id'], false);
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

if (isset($_POST['oldPassword']) && isset($_POST['newPassword1']) && isset($_POST['newPassword2'])) {
    require_once 'class/utilisateurDao.class.php';
    $utilisateurDao = new UtilisateurDAO();

    // Vérifier si les deux nouveaux mots de passe sont identiques
    if ($_POST['newPassword1'] === $_POST['newPassword2']) {
        $success = $utilisateurDao->changerMotDePasse($userData['id'], $_POST['oldPassword'], $_POST['newPassword1']);
        
        // Vérifier si le changement de mot de passe a réussi
        if ($success) {
            header("Location: " . $_SERVER['HTTP_REFERER'] ."?status=success");
            exit();
        } else {
            // Gérer l'erreur, par exemple, afficher un message d'erreur
            echo "L'ancien mot de passe est incorrect.";
        }
    } else {
        // Gérer l'erreur, par exemple, afficher un message d'erreur
        echo "Les nouveaux mots de passe ne correspondent pas.";
    }
} else {
    // Gérer l'erreur, par exemple, afficher un message d'erreur
    echo "Tous les champs de mot de passe doivent être remplis.";
}

if (isset($_GET['supprimerSponsors'])) {
    require_once 'class/SponsorDAO.class.php';
    $sponsorDao = new SponsorDAO();

    // Récupérer les informations du sponsor, y compris le chemin du logo
    $sponsor = $sponsorDao->getSponsorById((int)$_GET['id']);

    if ($sponsor) {
        // Supprimer le fichier du serveur si le logo existe
        if ($sponsor['logo'] && file_exists($sponsor['logo'])) {
            unlink($sponsor['logo']);
        }

        // Supprimer le sponsor de la base de données
        $sponsorDao->supprimerSponsor((int)$_GET['id']);
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit(); // Assurez-vous de terminer le script après la redirection
}



if (isset($_POST['ChangeColor']) ) {

    foreach ($_POST as $key => $value) {
    // Vérifie si la clé est numérique
    if (is_numeric($key)) {
        echo "Clé: " . htmlspecialchars($key) . ", Valeur: " . htmlspecialchars($value) . "<br>";

        // Assurez-vous que $dao et $userData sont définis avant cette boucle
        $change = $categorieDao->changerCouleurCategorie($key, $value, $userData['id']);

        // Affiche le résultat de la fonction changerCouleurCategorie
        header("Location: " . $_SERVER['HTTP_REFERER']);

    } 
}

}








