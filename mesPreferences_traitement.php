<?php
require 'security.php';
require_once 'class/categorie.class.php';
$dao = new CategorieDao($pdo);

if (isset($_POST['oldPassword']) && isset($_POST['newPassword1']) && isset($_POST['newPassword2'])) {
    require_once 'class/clubDao.class.php';
    $clubDao = new ClubDao();

    // Vérifier si les deux nouveaux mots de passe sont identiques
    if ($_POST['newPassword1'] === $_POST['newPassword2']) {
        $success = $clubDao->changeClubPassword($userData['id'], $_POST['oldPassword'], $_POST['newPassword1']);

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


if (isset($_POST['ChangeColor']) ) {

    foreach ($_POST as $key => $value) {
    // Vérifie si la clé est numérique
    if (is_numeric($key)) {
        echo "Clé: " . htmlspecialchars($key) . ", Valeur: " . htmlspecialchars($value) . "<br>";

        // Assurez-vous que $dao et $userData sont définis avant cette boucle
        $change = $dao->changerCouleurCategorie($key, $value, $userData['id']);

        // Affiche le résultat de la fonction changerCouleurCategorie
        header("Location: " . $_SERVER['HTTP_REFERER']);

    } 
}

}









// Ajout
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $dao->creerCategorie($_POST['nom'], $_POST['couleur'], $userData['id']);
    header('Location: categories.php?msg=created');
    exit;
}

// Mise à jour
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $dao->mettreAJourCategorie((int)$_POST['id'], $_POST['nom'], $_POST['couleur'], $userData['id']);
    header('Location: categories.php?msg=updated');
    exit;
}

// Suppression
if (isset($_GET['delete'])) {
    $dao->supprimerCategorie((int)$_GET['delete'],$userData['id']);
    header('Location: categories.php?msg=deleted');
    exit;
}


// Récupération pour l’affichage
$categories = $dao->obtenirToutesLesCategories($userData['id']);
