<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'Lang/lang.php';

$tournois = new tournoiDao();


if (!isset ($_GET['id_tournoi']) || $_GET['id_tournoi'] == 0) {
  echo "Aucun tournoi actif en cours.";
  header("Refresh:3; url=ajoutTournoi.php");
  exit();
}

if ($tournois->droitTournoiClub($_GET['id_tournoi'], $userData['id']) == null) {
    
  exit;
}



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);



$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('statsByClubs.twig');



// Créer un formatteur pour la date
// Récupérer la date en tant que chaîne
$dateString = $tournois->getTournoiById($_GET['id_tournoi'])['dateDebut'];

// Convertir la chaîne de date en objet DateTime
$dateTime = DateTime::createFromFormat('Y-m-d', $dateString);

if ($dateTime === false) {
    // Gérer l'erreur de conversion si nécessaire
    var_dump('Erreur de conversion de la date');
} else {
    // Créer l'IntlDateFormatter
    $formatter = new IntlDateFormatter(
        'fr_FR', // Locale
        IntlDateFormatter::LONG, // Type de format
        IntlDateFormatter::NONE, // Pas d'heure
        null, // Fuseau horaire par défaut
        IntlDateFormatter::GREGORIAN, // Calendrier
        'dd MMMM yyyy' // Format
    );

    // Formatter la date
    $dateFormatted = $formatter->format($dateTime);

    // Afficher la date formatée
    //var_dump($dateFormatted); // Devrait retourner "28 octobre 2024"
}


echo $template->render([
 'email' => $userData['email'],
  
  'pageEnCours' => 'Stats',
    //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
    //'afficherLesTournois' => $tournoi->getAllTournoi(),
    'ListeDesTournois' => $tournois->afficherLesTournois($userData['id']),
    'idTournoi' => $_GET['id_tournoi'],
    'statTournoi' => $tournois->statsTournoi($_GET['id_tournoi']),
    'infoTournoiEnCours' => $tournois->getTournoiById($_GET['id_tournoi']),
    'dateformat' =>  $dateFormatted
  
  
  ]);