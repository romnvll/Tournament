<?php
require ('security.php');
require_once 'class/licenceDao.class.php';

$licenceDao = new LicenceDao();

$idUtilisateur = $_GET['idUtilisateur'] ?? null;
$idLicence = $_GET['idLicence'] ?? null;
$idTournoi = $_GET['id_tournoi'] ?? null;

if ($idUtilisateur && $idLicence && $idTournoi) {
    $licenceDao->modifierTypeLicence($idUtilisateur, $idLicence);
    $licenceDetails = $licenceDao->getLicenceDetailsByLicenceId($idLicence);
    $licenceDao->updateLicenceForUser($idUtilisateur, $idLicence, $licenceDetails['duree_jours']);
    
    // Redirection
    header("Location: maLicence.php?id_tournoi=" . urlencode($idTournoi));
    exit; // Toujours mettre exit après un header Location
} else {
    echo "Erreur : paramètres manquants";
}
