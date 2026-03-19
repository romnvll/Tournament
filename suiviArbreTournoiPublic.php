<?php
/**
 * suiviArbreTournoi.php
 * Page publique — Suivi de l'arbre final d'un tournoi (lecture seule, sans authentification).
 */

require_once 'vendor/autoload.php';
require_once 'class/rencontreDao.class.php';
require_once 'class/tournoiDao.class.php';
require 'Lang/lang.php';

// ── Paramètres ────────────────────────────────────────────────────────────────
$tournoi_id   = filter_input(INPUT_GET, 'id_tournoi',    FILTER_VALIDATE_INT);
$categorie_id = filter_input(INPUT_GET, 'categorie_id',  FILTER_VALIDATE_INT);

if (!$tournoi_id || !$categorie_id) {
    http_response_code(400);
    die('Paramètres manquants ou invalides.');
}

// ── Twig ──────────────────────────────────────────────────────────────────────
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));

// ── Données ───────────────────────────────────────────────────────────────────
$tournoiManager = new RencontreDAO();
$data           = $tournoiManager->afficherArbreTournoi($tournoi_id, $categorie_id);
$tournoiDao      = new tournoiDao();
// ── Rendu ─────────────────────────────────────────────────────────────────────
$template = $twig->load('suiviArbreTournoiPublic.twig');
echo $template->render([
    'arbre'        => $data,
    'nomCategorie' => $data['nomCategorie'] ?? 'Catégorie Inconnue',
    'idTournoi'    => $tournoi_id,
    'categorieId'  => $categorie_id,
     'infoTournoiEnCours'=> $tournoiDao->getTournoiById($_GET['id_tournoi']),

]);
