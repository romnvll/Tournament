<?php

require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/categorie.class.php';
require 'class/SponsorDAO.class.php';
require 'Lang/lang.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));

$template = $twig->load('tv_display.twig');

$tournoiDao   = new tournoiDao();
$categorieDao = new CategorieDao();
$rencontreDao = new RencontreDAO();
$pouleManager = new PouleManager();

// ── Tournoi ───────────────────────────────────────────────────────────────────
$idTournoi = isset($_GET['id_tournoi']) ? (int) $_GET['id_tournoi'] : 0;

if ($idTournoi === 0) {
    echo $template->render([
        'listeTournois'     => $tournoiDao->afficherTousLesTournois(),
        'infoTournoi'       => null,
        'categories'        => [],
        'donneesCategories' => [],
        'partenaires'       => [],
        'rotationTime'      => 12,
        'catIndex'          => 0,
        'nextCatIndex'      => 0,
        'nbCategories'      => 0,
        'isLastCat'         => false,
        'donneesCat'        => null,
        'idTournoi'         => 0,
    ]);
    exit;
}

$infoTournoi = $tournoiDao->getTournoiById($idTournoi);
if (empty($infoTournoi)) {
    die('Tournoi introuvable.');
}

$nombreRencontresPlanifiees = count($rencontreDao->getAllRencontresByTournoiId($idTournoi));

$nombreRencontresTerminees = $rencontreDao->getAllRencontresByTournoiId($idTournoi);
$nombreTerminees = count(array_filter(
    $nombreRencontresTerminees,
    fn($rencontre) => (int)$rencontre['isTerminated'] === 1
));

// ── Paramètres ────────────────────────────────────────────────────────────────
$rotationTime = isset($_GET['rotation']) ? (int) $_GET['rotation'] : 12;
$catIndex     = isset($_GET['cat'])      ? (int) $_GET['cat']      : 0;

// ── Catégories du tournoi ─────────────────────────────────────────────────────
$categories = $categorieDao->obtenirCategoriesDuTournoi($idTournoi);

// ── Données par catégorie ─────────────────────────────────────────────────────
$donneesCategories = [];

foreach ($categories as $cat) {
    $catId = (int) $cat['id_categorie'];

    // Toutes les poules de la catégorie
    $toutesLesPoules = array_values(array_filter(
        $pouleManager->getAllPoulesByTournoi($idTournoi, true),
        fn($p) => (int)$p['fk_idcategorie'] === $catId
    ));

    // Séparation poules normales / poules de classement (phase finale)
    $poulesClassement = array_values(array_filter($toutesLesPoules, fn($p) => (int)$p['is_classement'] === 1));
    $poulesNormales   = array_values(array_filter($toutesLesPoules, fn($p) => (int)$p['is_classement'] === 0));

    $classementsPoules = [];

    // Essaie d'abord les poules de classement si elles existent
    $poulesAEssayer = !empty($poulesClassement) ? $poulesClassement : $poulesNormales;

    foreach ($poulesAEssayer as $poule) {
        $isClassement = (int)$poule['is_classement'];
        $classement   = $rencontreDao->GetResultatDesPoules((int)$poule['id'], 1);
        if (!empty($classement)) {
            $classementsPoules[] = [
                'poule'         => $poule,
                'classement'    => $classement,
                'is_classement' => $isClassement,
            ];
        }
    }

    // Fallback : si les poules de classement existent mais sont toutes vides
    // (aucune équipe affectée encore), on affiche le classement des poules normales
    if (empty($classementsPoules) && !empty($poulesClassement)) {
        foreach ($poulesNormales as $poule) {
            $classement = $rencontreDao->GetResultatDesPoules((int)$poule['id'], 1);
            if (!empty($classement)) {
                $classementsPoules[] = [
                    'poule'         => $poule,
                    'classement'    => $classement,
                    'is_classement' => 0,
                ];
            }
        }
    }

    // Si la catégorie est configurée pour ne pas afficher le classement
    if ((int)($cat['afficherClassement'] ?? 1) === 0) {
        $classementsPoules = [];
    }

    $rencontresActives = $rencontreDao->getRencontreByCategorie($catId, $idTournoi, 1);

    $enCours = array_values(array_filter($rencontresActives, fn($r) => (int)$r['isTerminated'] === 2));
    $aVenir  = array_values(array_filter($rencontresActives, fn($r) => (int)$r['isTerminated'] === 0));

    $donneesCategories[] = [
        'categorie'         => $cat,
        'classementsPoules' => array_values($classementsPoules),
        'enCours'           => $enCours,
        'aVenir'            => $aVenir,
    ];
}

// ── Partenaires ───────────────────────────────────────────────────────────────
$partenaires = [];
if (!empty($infoTournoi['gestionPartenaires'])) {
    $sponsorDao  = new SponsorDAO();
    $partenaires = $sponsorDao->getSponsorsActifParClub($infoTournoi['utilisateur_id']);
}


$poulesFinales = $pouleManager->getAllPoulesFinalesByTournoi($idTournoi);
foreach ($poulesFinales as &$poule) {
  $poule['contenu'] = $pouleManager->getEquipesInPoule($poule['id']);
  $poule['hasRencontres'] = $pouleManager->checkRencontresInPoule($poule['id'],3);
}



// ── Navigation entre catégories ───────────────────────────────────────────────
$nbCategories = count($donneesCategories);
$catIndex     = $nbCategories > 0 ? $catIndex % $nbCategories : 0;
$nextCatIndex = ($catIndex + 1) % max(1, $nbCategories);
$isLastCat    = ($catIndex === $nbCategories - 1);
$donneesCat   = $donneesCategories[$catIndex] ?? null;

// ── Rendu ─────────────────────────────────────────────────────────────────────
echo $template->render([
    'listeTournois'              => [],
    'infoTournoi'                => $infoTournoi,
    'categories'                 => $categories,
    'donneesCategories'          => $donneesCategories,
    'partenaires'                => $partenaires,
    'rotationTime'               => $rotationTime,
    'idTournoi'                  => $idTournoi,
    'catIndex'                   => $catIndex,
    'nextCatIndex'               => $nextCatIndex,
    'nbCategories'               => $nbCategories,
    'isLastCat'                  => $isLastCat,
    'donneesCat'                 => $donneesCat,
    'nombreRencontresPlanifiees' => $nombreRencontresPlanifiees,
    'nombreRencontresTerminees'  => $nombreTerminees,
    'poulesFinales' => $poulesFinales,
]);