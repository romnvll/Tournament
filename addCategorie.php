<?php
require 'security.php';
require 'vendor/autoload.php';



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => false,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());


require_once 'class/clubDao.class.php';
require_once 'class/databaseInformations.php';
require_once 'class/tournoiDao.class.php';
require_once 'class/categorie.class.php';
$tournoiDao = new tournoiDao();

$tousLesTournois = $tournoiDao->afficherLesTournois($userData['id']);





$categorie = new CategorieDao();

$categorie->creerCategorie($_POST['Nom_categorie'], $_POST['Couleur'], $userData['id']);
header("Location: " . $_SERVER['HTTP_REFERER']);
