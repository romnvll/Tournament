<?php

require ('security.php');
require 'vendor/autoload.php';
require_once 'class/licenceDao.class.php';
require 'Lang/lang.php';






$licenceDao = new LicenceDao();

$licence=$licenceDao->getLicencesParUtilisateur($userData['id'])[0];
$expiration = $licenceDao->retrograderLicencesExpirees();


$jourRestant = $licenceDao->getJoursRestantsLicence($userData['id']);

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('maLicence.twig');







echo $template->render([
  'email' => $userData['email'],
  'idUtilisateur' => $userData['id'],
  'pageEnCours' =>  'Users',
  'licenceDetails' => $licenceDao->getLicencesParUtilisateur($userData['id'])[0] ?? null,
  'idTournoi' => $_GET['id_tournoi'],
  'idClub' => $userData['id'],
  'TypesDeLicences' => $licenceDao->getTousLesTypesDeLicence(),
  'jourRestant' => $jourRestant,
  'licence' => $licence,
  
 
   
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),

]);
