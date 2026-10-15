<?php
require 'security.php';
require 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, ['cache' => false, 'debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));

require 'class/tournoiDao.class.php';
require 'class/TournoiBackupDao.class.php';
require 'class/SauvegardeStockage.class.php';
require 'Lang/lang.php';

$tournoiDao = new tournoiDao();
$backupDao = new TournoiBackupDao();
$stockage = new SauvegardeStockage();

$template = $twig->load('sauvegardeTournoi.twig');

echo $template->render([
    'email' => $userData['email'],
    'pageEnCours' => 'SauvegardeTournois',
    'ListeDesTournois' => $tournoiDao->afficherLesTournois($userData['id']),
    'SauvegardesStockees' => $stockage->lister((int) $userData['id']),
    'nombreTournoisActuel' => $backupDao->compterTournoisUtilisateur((int) $userData['id']),
    'limiteTournois' => $backupDao->limiteTournoisUtilisateur((int) $userData['id']),
    'status' => $_GET['status'] ?? null,
    'message' => $_GET['message'] ?? null,
    'tournoiId' => $_GET['idTournoi'] ?? null,
]);