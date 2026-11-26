<?php
require ('security.php');
require ('class/clubDao.class.php');
require 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, ['cache' => false]);

$club = new ClubDAO();

$nom = trim($_GET['nom'] ?? '');
$sport = trim($_GET['sport'] ?? '');
$parPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));

$totalClubs = $club->compterClubsFiltres($nom, $sport);
$totalPages = max(1, (int)ceil($totalClubs / $parPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

$template = $twig->load('_clubsTable.twig'); // fragment, voir ci-dessous

echo $template->render([
    'ListeDesClubs' => $club->rechercherClubs($nom, $sport, $parPage, $offset),
    'idUser' => $userData['id'],
    'isAdmin' => $userData['role'],
    'pageCourante' => $page,
    'totalPages' => $totalPages,
    'nomRecherche' => $nom,
    'sportRecherche' => $sport,
]);