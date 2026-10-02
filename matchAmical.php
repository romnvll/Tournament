
<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
require 'class/rencontreDao.class.php';
require 'Lang/lang.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, ['cache' => false, 'debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('matchAmical.twig');

// ── Vérification du tournoi ──────────────────────────────────────────────────
if (!isset($_GET['id_tournoi']) || (int)$_GET['id_tournoi'] === 0) {
    echo "Aucun tournoi actif en cours.";
    header("Refresh:3; url=ajoutTournoi.php");
    exit();
}

$idTournoi = (int)$_GET['id_tournoi'];
$tournois  = new tournoiDao();

if (
    $userData['role'] !== 'admin' &&
    $tournois->droitTournoiClub($idTournoi, $userData['id']) === null
) {
    exit;
}

$equipeDao    = new equipeDao();
$rencontresDao = new rencontreDao();

$tournoiInfo  = $tournois->getTournoiById($idTournoi);
$equipes      = $equipeDao->getAllEquipeByIdTournoi($idTournoi);


// Type de rencontre amical = 4
$typeRencontreAmical = 4;

$messages = [];
$erreurs = [];

// ── POST : création d'un match amical ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'creer_match_amical') {
    $equipe1Id = (int)($_POST['equipe1_id'] ?? 0);
    $equipe2Id = (int)($_POST['equipe2_id'] ?? 0);

    // Validations
    if ($equipe1Id === 0 || $equipe2Id === 0) {
        $erreurs[] = "Veuillez sélectionner deux équipes.";
    } elseif ($equipe1Id === $equipe2Id) {
        $erreurs[] = "Les deux équipes doivent être différentes.";
    } else {
        // Insérer la rencontre
        $resultat = $rencontresDao->insertRencontre(
            $equipe1Id,
            $equipe2Id,
            $idTournoi,
            $typeRencontreAmical
        );

        if ($resultat) {
            $messages[] = "Match amical créé avec succès !";
            // Recharger les équipes et rencontres
        } else {
            $erreurs[] = "Ce match amical existe déjà.";
        }
    }
}

// ── DELETE : supprimer un match amical ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_match') {
    $rencontreId = (int)($_POST['rencontre_id'] ?? 0);
    if ($rencontreId > 0) {
        
        $rencontresDao->supprimerRencontre($rencontreId);
        $messages[] = "Match amical supprimé.";
    }
}

// Recharger les rencontres amicales après chaque action
$rencontresAmicales = $rencontresDao->afficherRencontresParType($idTournoi, $typeRencontreAmical);

// ── Rendu Twig ───────────────────────────────────────────────────────────────
echo $template->render([
    'email'                 => $userData['email'],
    'pageEnCours'          => 'GestionDesRencontres',
    'idTournoi'            => $idTournoi,
    'tournoiInfo'          => $tournoiInfo,
    'equipes'              => $equipes,
    'rencontresAmicales'   => $rencontresAmicales,
    'messages'             => $messages,
    'erreurs'              => $erreurs,
    'ListeDesTournois'     => $tournois->afficherLesTournois($userData['id']),
]);
?>
