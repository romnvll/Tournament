<?php
require ('security.php');
require_once 'class/licenceDao.class.php';

$licenceDao = new LicenceDao();

$idUtilisateur = $_GET['idUtilisateur'] ?? null;
$idLicence = $_GET['idLicence'] ?? null;

if ($idUtilisateur && $idLicence) {
    $licenceDao->modifierTypeLicence($idUtilisateur, $idLicence);
   $licenceDetails = $licenceDao->getLicenceDetailsByLicenceId($idLicence);
    $licenceDao->updateLicenceForUser($idUtilisateur, $idLicence, $licenceDetails['duree_jours']);
    echo "✅ Licence activée avec succès";
} else {
    echo "Erreur : paramètres manquants";
}
