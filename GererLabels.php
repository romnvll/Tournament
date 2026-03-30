<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/labelsDao.class.php';
session_start();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,

]);

if (($_SESSION['id_tournoi']) == null) {

    $_SESSION['id_tournoi'] = $_GET['id_tournoi'];
}

$labels = new LabelDao();

// FIX 1: Fusionné isset() et la condition en une seule ligne
if (isset($_POST['labelModif']) && $_POST['labelModif'] == true) {
    $labels->updateLabel($_POST['labelId'], $_POST['description'], $_POST['couleur']);
}


$listeDesLabels = $labels->getLabelsByTournoiId((int)$_GET['id_tournoi']);


//bouton retour
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
// Récupère le nom de domaine
        $domainName = $_SERVER['HTTP_HOST'];
        // Récupère le chemin de base en excluant la page actuelle
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

        $url = $protocol . $domainName . $basePath . "PlacementDesRencontres.php?id_tournoi=" . urlencode($_SESSION['id_tournoi']);
       
//


if (isset($_POST['labelModif']) && $_POST['labelModif'] == true) {
    $labelId = $_POST['labelId'];
    $description = $_POST['description'];
    $couleur = $_POST['couleur'];

    $labelDao = new LabelDao();
    $labelDao->updateLabel($labelId, $description, $couleur);

   
    exit;
}



// FIX 2: Ajout de isset() sur $_GET['action']
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'addLabel') {
    $description = $_GET['description'];
    $couleur = $_GET['couleur'];
    $tournoi_id = $_SESSION['id_tournoi'];


    $labelDao = new LabelDao();
    $labelDao->ajouterLabel($description, $couleur, $tournoi_id);

    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    // Récupère le nom de domaine
    $domainName = $_SERVER['HTTP_HOST'];
    // Récupère le chemin de base en excluant la page actuelle
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
    $url = $protocol . $domainName . $basePath . "GererLabels.php?id_tournoi=" . urlencode($_SESSION['id_tournoi']);


    // Rediriger vers la page de gestion des labels
    header("Location: " . $url);
    exit;
}



// FIX 3: Ajout de isset() sur $_GET['action']
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delLabel') {
    $labelId = $_GET['labelId'];



    $labelDao = new LabelDao();
    try {
        $labelDao->supprimerLabel($labelId);

        // Rediriger avec un message de succès

        // Détermine le protocole HTTP ou HTTPS
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        // Récupère le nom de domaine
        $domainName = $_SERVER['HTTP_HOST'];
        // Récupère le chemin de base en excluant la page actuelle
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

        $url = $protocol . $domainName . $basePath . "GererLabels.php?id_tournoi=" . urlencode($_SESSION['id_tournoi']);
        $url = $url . "&message=Label supprimé avec succès";
        echo $url;
        header('Location: ' . $url);
        exit;
    } catch (Exception $e) {

        // Rediriger avec un message de succès

        // Détermine le protocole HTTP ou HTTPS
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        // Récupère le nom de domaine
        $domainName = $_SERVER['HTTP_HOST'];
        // Récupère le chemin de base en excluant la page actuelle
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

        $url = $protocol . $domainName . $basePath . "GererLabels.php?id_tournoi=" . urlencode($_SESSION['id_tournoi']);
        $url = $url . "&error=" . urlencode($e->getMessage()) . "";
        echo $url;
        header('Location: ' . $url);

        // Rediriger avec un message d'erreur
        //header('Location: ' . $_SERVER['HTTP_REFERER'] . '&error=' . urlencode($e->getMessage()));
        exit;
    }
}




$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('GererLabels.twig');


$error = isset($_GET['error']) ? $_GET['error'] : null;
$message = isset($_GET['message']) ? $_GET['message'] : null;

echo $template->render([
    // FIX 4: Utilisation de l'opérateur null coalescent pour éviter le warning si le cookie n'existe pas
    'email' => $_COOKIE['email'] ?? null,
    'pageEnCours' => 'GestionDesRencontres',
    'idTournoi' => $_GET['id_tournoi'],
    'labels' => $listeDesLabels,

    'error' => $error,
    'message' => $message,
    'urlRetour' => $url



]);