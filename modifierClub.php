<?php
require ('security.php');
require ('class/clubDao.class.php');
require 'vendor/autoload.php';
require 'class/typeSportDao.class.php';
require 'Lang/lang.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('modifierClub.twig');

$club = new ClubDAO();
$typeSportDao = new TypeSportDAO();

// Pagination
$parPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $parPage;

$totalClubs = $club->compterClubs();
$totalPages = max(1, (int)ceil($totalClubs / $parPage));
$page = min($page, $totalPages); // évite de dépasser la dernière page
$offset = ($page - 1) * $parPage;

echo $template->render([
  'email' => $userData['email'],
  'pageEnCours' => 'GestionClub',
  'ListeDesClubs' => $club->afficherClubs($parPage, $offset),
  'isModify' => true,
  'afficheclub' => $club->getClubById($_GET['idclub'] ?? null),
  'idUser' => $userData['id'],
  'idTournoi' => $_GET['id_tournoi'] ?? null,
  'idClub' => $_GET['idclub'] ?? null,
  'listeSports' => $typeSportDao->getTousLesTypesDeSport(),
  'isAdmin' => $userData['role'],
  'pageCourante' => $page,
  'totalPages' => $totalPages,
]);