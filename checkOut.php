<?php
require ('security.php');
require 'vendor/autoload.php';
require_once 'class/licenceDao.class.php';
require_once 'class/utilisateurDao.class.php';

$utilisateurDao = new UtilisateurDao();
$licenceDao = new LicenceDao();

$typeLicence = $_POST['type_licence'] ?? null;
$idUtilisateur = $_POST['idUtilisateur'] ?? null;
$id_tournoi = $_POST['idTournoi'] ?? null;

if (!$typeLicence || !$idUtilisateur) {
    die("Paramètres manquants");
}

$licenceDetails = $licenceDao->getLicenceTypeByName($typeLicence);
$users = $utilisateurDao->getUtilisateurById($idUtilisateur);

if (!$licenceDetails || !$users) {
    die("Licence ou utilisateur introuvable");
}

// Licence gratuite → mise à jour directe
if ($licenceDetails['nom'] === "Gratuite") {
    $licenceDao->modifierTypeLicence($users['id'], $licenceDetails['id']);
    $licenceDao->reinitialiserDateFin($users['id']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

\Stripe\Stripe::setApiKey($apiStripeKey);

// Création session Stripe Checkout
$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'], // CB
    'line_items' => [[
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => 'Licence ' . $licenceDetails['nom'],
            ],
            'unit_amount' => $licenceDetails['prix'] * 100, // en centimes
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => 'https://'. $_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']) .'/checkOutSucess.php?idUtilisateur='.$users['id'].'&idLicence='.$licenceDetails['id'].'&id_tournoi='. $id_tournoi,
    'cancel_url' => $_SERVER['HTTP_REFERER'] ,
]);

// Redirection vers Stripe
header("Location: " . $session->url);
exit;
