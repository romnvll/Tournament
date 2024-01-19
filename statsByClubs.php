<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';

$tournois = new tournoiDao();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);



$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('statsByClubs.twig');



echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' => 'Stats',
    //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
    //'afficherLesTournois' => $tournoi->getAllTournoi(),
    'ListeDesTournois' => $tournois->afficherLesTournois(),
    'idTournoi' => $_GET['id_tournoi'],
    'statTournoi' => $tournois->statsTournoi($_GET['id_tournoi']),
  
  
  ]);