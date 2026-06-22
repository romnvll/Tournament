<?php
require('security.php');
require 'vendor/autoload.php';
require 'class/rencontreDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';

require 'class/clubDao.class.php';
require 'class/licenceDao.class.php';
require 'Lang/lang.php';

$tournois     = new tournoiDao();
$poules       = new PouleManager();
$equipeDao    = new EquipeDAO();
$rencontreDao = new RencontreDAO();
$clubDao      = new ClubDAO();

$licenceDao = new LicenceDao();
$licence    = $licenceDao->getLicencesParUtilisateur($userData['id'])[0];

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));

// ──────────────────────────────────────────────────────────────────────────
// Paramètres de navigation (étapes : tournoi > catégorie > poule)
// ──────────────────────────────────────────────────────────────────────────
$idTournoi = isset($_GET['id_tournoi']) ? (int) $_GET['id_tournoi'] : 0;

if ($idTournoi === 0) {
    echo "Aucun tournoi sélectionné.";
    header("Refresh:3; url=ajoutTournoi.php");
    exit();
}

if (
    $userData['role'] !== 'admin' &&
    $tournois->droitTournoiClub($idTournoi, $userData['id']) === null
) {
    exit;
}

$idCategorie = isset($_GET['categorie']) ? (int) $_GET['categorie'] : null;
$idPoule     = isset($_GET['poule']) ? (int) $_GET['poule'] : null;

$message     = null;
$messageType = null;

// ──────────────────────────────────────────────────────────────────────────
// Traitement du formulaire de création + ajout d'équipe
// ──────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_equipe') {

    $nomEquipe   = trim($_POST['nom_equipe'] ?? '');
    $nomCoach    = trim($_POST['nom_coach'] ?? '') ?: null;
    $pouleIdPost = (int) ($_POST['poule_id'] ?? 0);
    $categorieIdPost = (int) ($_POST['categorie_id'] ?? 0);
    $tournoiIdPost   = (int) ($_POST['tournoi_id'] ?? 0);

    $modeClub = $_POST['mode_club'] ?? 'existant'; // 'existant' ou 'nouveau'

    try {
        if ($nomEquipe === '') {
            throw new Exception("Le nom de l'équipe est obligatoire.");
        }
        if ($pouleIdPost <= 0) {
            throw new Exception("Aucune poule sélectionnée.");
        }

        // ── 1. Déterminer le club_id (existant ou nouveau) ─────────────
        if ($modeClub === 'nouveau') {
            $nomClub = trim($_POST['nom_nouveau_club'] ?? '');
            if ($nomClub === '') {
                throw new Exception("Le nom du nouveau club est obligatoire.");
            }

            // Type de sport : handball par défaut (id = 1). Ajuste si besoin.
            $typeSportId = 1;

            $clubDao->ajouterClub($nomClub, null, $typeSportId, (int) $userData['id']);

            // Récupérer l'id du club nouvellement créé
            $clubs   = $clubDao->afficherClubs();
            $clubId  = null;
            foreach (array_reverse($clubs) as $c) {
                if ($c['nom'] === $nomClub) {
                    $clubId = (int) $c['id'];
                    break;
                }
            }
            if ($clubId === null) {
                throw new Exception("Erreur lors de la création du club.");
            }
        } else {
            $clubId = (int) ($_POST['club_id'] ?? 0);
            if ($clubId <= 0) {
                throw new Exception("Veuillez sélectionner un club existant.");
            }
        }

        // ── 2. Créer l'équipe (sans la rattacher directement à la poule) ───
        $equipeDao->ajouterEquipe(
            $nomEquipe,
            $categorieIdPost,
            $tournoiIdPost,
            null, // on ajoute à la poule nous-même ensuite, via PouleManager
            $clubId,
            $nomCoach
        );

        // Récupérer l'ID de l'équipe qu'on vient de créer
        $equipesDuTournoi = $equipeDao->getAllEquipeByIdTournoi($tournoiIdPost);
        $equipeId = null;
        foreach ($equipesDuTournoi as $e) {
            if ($e['nom'] === $nomEquipe) {
                $equipeId = (int) $e['id'];
                break;
            }
        }
        if ($equipeId === null) {
            throw new Exception("L'équipe a été créée mais n'a pas pu être retrouvée.");
        }

        // ── 3. Détecter si la poule est en aller-retour ────────────────
        $isMatchRetour = detecterMatchRetour($rencontreDao, $pouleIdPost);

        // ── 4. Ajouter l'équipe à la poule : déclenche la génération
        //       automatique des rencontres manquantes ──────────────────
        $poules->addEquipeToPoule($equipeId, $pouleIdPost, $tournoiIdPost, $isMatchRetour);

        $message     = "L'équipe \"$nomEquipe\" a été créée et ajoutée à la poule avec succès. Les rencontres manquantes ont été générées automatiquement.";
        $messageType = 'success';

        $idCategorie = $categorieIdPost;
        $idPoule     = $pouleIdPost;

    } catch (Exception $e) {
        $message     = $e->getMessage();
        $messageType = 'danger';
    }
}

/**
 * Détecte si une poule fonctionne en aller-retour en regardant
 * si une paire d'équipes existe deux fois (deux tours distincts).
 */
function detecterMatchRetour(RencontreDAO $rencontreDao, int $pouleId): bool
{
    $equipes = $rencontreDao->getEquipesPresentesByPoule($pouleId);
    $n       = count($equipes);

    if ($n < 2) {
        return false;
    }

    $rencontres   = $rencontreDao->afficherRencontresParType(0, TYPE_RENCONTRE_POULE);
    // Filtrage manuel : on regarde plutôt directement via getRencontreByPoule
    // pour rester cohérent avec la structure existante de la poule.
    $rencontresPoule = $rencontreDao->getRencontreByPoule($pouleId, TYPE_RENCONTRE_POULE, 'tour');

    $nbRencontres   = count($rencontresPoule);
    $nbAllerSimple  = ($n * ($n - 1)) / 2;

    // S'il y a sensiblement plus de rencontres que pour un aller simple,
    // on considère que la poule est en aller-retour.
    return $nbRencontres > $nbAllerSimple;
}

// ──────────────────────────────────────────────────────────────────────────
// Données pour les étapes du formulaire
// ──────────────────────────────────────────────────────────────────────────
$listeCategories = $equipeDao->getAllCategorieByIdTournoi($idTournoi);

$listePoules = [];
if ($idCategorie !== null) {
    $toutesPoules = $poules->getAllPoulesByTournoi($idTournoi, false); // poules normales uniquement
    foreach ($toutesPoules as $p) {
        if ((int) $p['fk_idcategorie'] === (int) $idCategorie) {
            $listePoules[] = $p;
        }
    }
}

$listeClubs = $clubDao->afficherClubs();

$equipesDeLaPoule = [];
if ($idPoule !== null) {
    $equipesDeLaPoule = $equipeDao->getAllEquipesByPouleId($idPoule);
}

$template = $twig->load('AjouterEquipeDernierMoment.twig');
echo $template->render([
    'email'        => $userData['email'],
    'pageEnCours'  => 'AjouterEquipeDernierMoment',

    'ListeDesTournois'  => $tournois->afficherLesTournois($userData['id']),
    'idTournoi'         => $idTournoi,

    'listeCategories'   => $listeCategories,
    'idCategorie'       => $idCategorie,

    'listePoules'       => $listePoules,
    'idPoule'           => $idPoule,

    'equipesDeLaPoule'  => $equipesDeLaPoule,

    'listeClubs'        => $listeClubs,

    'message'     => $message,
    'messageType' => $messageType,

    'licence' => $licence,
]);
