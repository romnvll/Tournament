<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/rencontreDao.class.php';

$tournois = new tournoiDao();
$rencontre = new RencontreDAO();

if (!isset($_GET['id_tournoi']) || $_GET['id_tournoi'] == 0) {
    echo "Aucun tournoi actif en cours.";
    header("Refresh:3; url=ajoutTournoi.php");
    exit();
}

if ($tournois->droitTournoiClub($_GET['id_tournoi'], $userData['id']) == null) {
    exit;
}

$idTournoi = $_GET['id_tournoi'];

// Suppression d'une rencontre spécifique de phase finale
if (isset($_GET['idRencontre'])) {
    $idRencontre = $_GET['idRencontre'];
    
    if ($rencontre->supprimerRencontrePhaseFinale($idRencontre)) {
        echo "Rencontre supprimée avec succès.";
    } else {
        echo "Erreur lors de la suppression de la rencontre.";
    }
    
    header("Refresh:2; url=suppressionRencontres.php?id_tournoi=" . $idTournoi);
    exit();
}

// Suppression de toutes les rencontres d'une phase finale
if (isset($_GET['idPhaseFinale'])) {
    $idPhaseFinale = $_GET['idPhaseFinale'];
    
    if ($rencontre->supprimerRencontresParPhaseFinale($idPhaseFinale, $idTournoi)) {
        echo "Rencontres de la phase finale supprimées avec succès.";
    } else {
        echo "Erreur lors de la suppression des rencontres.";
    }
    
    header("Refresh:2; url=suppressionRencontres.php?id_tournoi=" . $idTournoi);
    exit();
}

// Suppression des rencontres d'une poule (code existant)
if (isset($_GET['idPoule'])) {
    $idPoule = $_GET['idPoule'];
    
    if ($rencontre->supprimerRencontresParPoule($_GET['idPoule'])) {
        echo "Rencontres de la poule supprimées avec succès.";
    } else {
        echo "Erreur lors de la suppression des rencontres de la poule.";
    }
    
    header("Refresh:2; url=suppressionRencontres.php?id_tournoi=" . $idTournoi);
    exit();
}

echo "Aucune action spécifiée.";
header("Refresh:2; url=suppressionRencontres.php?id_tournoi=" . $idTournoi);