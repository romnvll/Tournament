<?php
require 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());



require 'class/planificationDao.class.php';
require 'class/tournoiDao.class.php';

$tournoi = new tournoiDao();
$infoTournoi = $tournoi->getTournoiById($_GET['idTournoi']);

$planificationDao = new PlanificationDao();
$creneauxArbitres = $planificationDao->afficherCreneauxArbitres($_GET['idTournoi']);


$template = $twig->load('affichagePlanningArbitre.twig');


echo $template->render([
    'creneauxArbitres' => $creneauxArbitres,
    'idTournoi' => $_GET['idTournoi'],
    'infoTournoi' => $infoTournoi['nom'],

]);
