<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/assistanceDao.class.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$assistanceDao = new AssistanceDao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';
    $utilisateur_id = $userData['id'];

    // On insère le message tel quel (avec ses balises <img>)
    $assistanceDao->ajouterDemande($utilisateur_id, $message);

    header("Location: assistance.php?success=1");
    exit;
}

$template = $twig->load('assistance.twig');
echo $template->render([
    'pageEnCours' =>  'Users',
    'email' => $userData['email'],
    'pageEnCours' => 'Assistance',
    'success' => isset($_GET['success']) ? (int)$_GET['success'] : 0,
    'idTournoi' => $_GET['id_tournoi'] ?? 0
]);
