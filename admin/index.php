<?php
session_start();
require_once '../secureCookies.php';
require_once '../security.php';
require_once '../class/utilisateurDao.class.php';
require_once '../class/tournoiDao.class.php';
require_once '../class/licenceDao.class.php';
require_once '../class/assistanceDao.class.php'; // NOUVEAU

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
$assistanceDAO  = new AssistanceDao(); // NOUVEAU

// Récupération des données
$utilisateurs = $utilisateurDAO->getAllUtilisateurs();
$tournois     = $tournoiDAO->afficherTousLesTournois(); // tous les tournois (actifs + archivés), avec nb d'équipes et type de sport

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

// Demandes d'assistance (NOUVEAU)
$demandesAssistance = $assistanceDAO->getToutesLesDemandes();

// -----------------------------
// Calcul des statistiques globales
// -----------------------------

// Utilisateurs par rôle
$statsUtilisateursParRole = ['admin' => 0, 'organisateur' => 0, 'arbitre' => 0, 'club' => 0];
foreach ($utilisateurs as $u) {
    if (isset($statsUtilisateursParRole[$u['role']])) {
        $statsUtilisateursParRole[$u['role']]++;
    }
}
$totalUtilisateurs        = count($utilisateurs);
$utilisateursNonConfirmes = count(array_filter($utilisateurs, fn($u) => (int)$u['email_confirme'] === 0));

// Tournois actifs / archivés
$tournoisActifs   = count(array_filter($tournois, fn($t) => (int)$t['isArchived'] === 0));
$tournoisArchives = count(array_filter($tournois, fn($t) => (int)$t['isArchived'] === 1));
$totalTournois    = count($tournois);

// Licences : revenu total et licences expirées (hors licences gratuites)
$revenuTotalLicences = 0.0;
$licencesExpirees    = 0;
$today               = date('Y-m-d');
foreach ($licences as $l) {
    if ($l['type_licence'] !== 'Gratuite') {
        $revenuTotalLicences += (float)$l['prix'];
        if (!empty($l['date_fin']) && $l['date_fin'] < $today) {
            $licencesExpirees++;
        }
    }
}

// Demandes d'assistance : total et récentes (7 derniers jours)
$totalDemandesAssistance   = count($demandesAssistance);
$demandesAssistanceRecentes = count(array_filter($demandesAssistance, function ($d) {
    return strtotime($d['date_creation']) >= strtotime('-7 days');
}));

// Rendu Twig
echo $twig->render('index.twig', [
    'utilisateurs'       => $utilisateurs,
    'tournois'           => $tournois,
    'licences'           => $licences,
    'demandesAssistance' => $demandesAssistance,
    'user'               => $userData, // pour afficher qui est connecté

    // stats globales
    'statsUtilisateursParRole'   => $statsUtilisateursParRole,
    'totalUtilisateurs'          => $totalUtilisateurs,
    'utilisateursNonConfirmes'   => $utilisateursNonConfirmes,
    'tournoisActifs'             => $tournoisActifs,
    'tournoisArchives'           => $tournoisArchives,
    'totalTournois'              => $totalTournois,
    'revenuTotalLicences'        => $revenuTotalLicences,
    'licencesExpirees'           => $licencesExpirees,
    'totalDemandesAssistance'    => $totalDemandesAssistance,
    'demandesAssistanceRecentes' => $demandesAssistanceRecentes,
]);