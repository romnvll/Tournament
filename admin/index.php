<?php
session_start();
require_once '../secureCookies.php';
require_once '../security.php'; 
require_once '../class/utilisateurDao.class.php';
require_once '../class/tournoiDao.class.php';
require_once '../class/licenceDao.class.php';

// Twig
require_once '../vendor/autoload.php';
$loader = new \Twig\Loader\FilesystemLoader('.');
$twig = new \Twig\Environment($loader, [
    'cache' => false, // désactive le cache en dev
    'debug' => true
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());


// Vérification du rôle admin
if (!isset($userData) || $userData['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$utilisateurDAO = new UtilisateurDAO();
$tournoiDAO     = new TournoiDAO();
$licenceDAO     = new LicenceDAO();

// Récupération des données
$utilisateurs   = $utilisateurDAO->getAllUtilisateurs();
$tournoisEnCours = $tournoiDAO->getTournoisEnCours();

// Licences : on associe chaque licence à son utilisateur
$licences = [];
foreach ($utilisateurs as $u) {
    $licencesUser = $licenceDAO->getLicencesParUtilisateur((int)$u['id']);
    if (!empty($licencesUser)) {
        foreach ($licencesUser as $lic) {
            $licences[] = array_merge(
                ['nom_utilisateur' => $u['nom'], 'email_utilisateur' => $u['email']],
                $lic
            );
        }
    }
}

// Rendu Twig
echo $twig->render('index.twig', [
    'utilisateurs'   => $utilisateurs,
    'tournoisEnCours' => $tournoisEnCours,
    'licences'       => $licences,
    'user'           => $userData // pour afficher qui est connecté
]);
