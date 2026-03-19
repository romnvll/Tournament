<?php
// Inclure les dépendances nécessaires (autoloader, config, etc.)
require 'security.php';
require_once 'vendor/autoload.php';
require_once 'class/rencontreDao.class.php'; // Adapter selon votre architecture
require_once 'class/planificationDao.class.php';
require 'Lang/lang.php';

// Initialiser Twig
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));

// Récupérer les paramètres
$tournoi_id = $_GET['id_tournoi'] ?? null;
$categorie_id = $_GET['categorie_id'] ?? null;

if (!$tournoi_id || !$categorie_id) {
    die('Paramètres manquants');
}

// Instancier votre classe (adapter selon votre architecture)
$tournoiManager = new RencontreDAO(); // Ou le nom de votre classe

// Récupérer les données
$data = $tournoiManager->afficherArbreTournoi($tournoi_id, $categorie_id);

$planificationDao = new PlanificationDAO();
$planificationDao->convertLabelsToRencontres($tournoi_id);


$template = $twig->load('gestionArbreTournoi.twig');
echo $template->render([
    'pageEnCours' => 'GestionDesRencontres',
    'email' => $userData['email'],
    'arbre' => $data,
    'nomCategorie' => $data['nomCategorie'] ?? 'Catégorie Inconnue',
    'tournoiEnCours' => $_GET['id_tournoi'],
     'idTournoi'=> $_GET['id_tournoi'],
]);



?>