<?php
require('security.php');
require('class/clubDao.class.php');

$idClub = intval($_GET['idclub'] ?? 0);

if ($idClub <= 0) {
    header('Location: modifierClub.php');
    exit;
}

$clubDao = new ClubDAO();
$club = $clubDao->getClubById($idClub);

if ($club['utilisateur_id'] !== $userData['id'] && $userData['role'] !== 'admin') {
    header('Location: modifierClub.php?erreur=non_autorise');
    exit;
}

try {
    $clubDao->supprimerClub($idClub);
    header('Location: modifierClub.php?succes=supprime');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        // Violation de clé étrangère = club utilisé ailleurs
        echo "Impossible de supprimer ce club, il est utilisé dans un tournoi"; // Affiche le message d'erreur pour le débogage
        echo "<br><a href='modifierClub.php'>Retour à la gestion des clubs</a>"; // Lien pour revenir à la page de gestion des clubs
        
        //header('Location: modifierClub.php?erreur=club_utilise');
    } else {
        header('Location: modifierClub.php?erreur=inconnu');
    }
}
exit;