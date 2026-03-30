<?php

require('security.php');
require 'class/rencontreDao.class.php';
require 'class/labelsDao.class.php';
require('class/categorie.class.php');

/* ══════════════════════════════════════════════════════════════════
   Récupération des données communes (POST + GET)
══════════════════════════════════════════════════════════════════ */
$action          = $_POST['action']               ?? 'creer_matchs';
$categorieId     = $_POST['categorieId']          ?? null;
$qualifiesNombre = intval($_POST['qualifiesNombre'] ?? 0);
$equipes         = $_POST['equipes']              ?? [];
$idTournoi       = $_GET['id_tournoi']            ?? null;

// Validation minimale
if (!$categorieId || !$qualifiesNombre || !$idTournoi) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']);
    exit;
}

/* ══════════════════════════════════════════════════════════════════
   Calculs communs aux deux actions
══════════════════════════════════════════════════════════════════ */
$poules   = array_keys($equipes);
$nbPoules = count($poules);

// Organiser les équipes qualifiées par position dans chaque poule
$equipesParPosition = [];
foreach ($equipes as $pouleId => $equipeIds) {
    foreach ($equipeIds as $index => $equipeId) {
        $position = $index + 1;
        if ($position <= $qualifiesNombre) {
            $equipesParPosition[$position][$pouleId] = [
                'id'       => $equipeId,
                'poule'    => $pouleId,
                'position' => $position,
            ];
        }
    }
}

// Rassembler toutes les équipes qualifiées dans l'ordre des positions
$toutesEquipesQualifiees = [];
for ($pos = 1; $pos <= $qualifiesNombre; $pos++) {
    if (isset($equipesParPosition[$pos])) {
        foreach ($equipesParPosition[$pos] as $equipe) {
            $toutesEquipesQualifiees[] = $equipe;
        }
    }
}

$nombreTotalEquipes = count($toutesEquipesQualifiees);

/* ══════════════════════════════════════════════════════════════════
   Phases finales
══════════════════════════════════════════════════════════════════ */
$rencontre = new RencontreDAO();

$phasesACreer = [
    ['libelle' => '8ème de finale',          'ordre' => 1],
    ['libelle' => 'Quart de finale',          'ordre' => 2],
    ['libelle' => 'Demi-finale',              'ordre' => 3],
    ['libelle' => 'Finale',                   'ordre' => 4],
    ['libelle' => 'Match pour la 3ème place', 'ordre' => 5],
];

/**
 * Crée les phases finales manquantes pour un tournoi.
 */
function creerPhasesFinalesSiAbsentes(RencontreDAO $rencontre, int $idTournoi, array $phasesACreer): void
{
    foreach ($phasesACreer as $phase) {
        if (!$rencontre->phasesFinalesExistent($idTournoi, $phase['ordre'])) {
            $rencontre->insertPhaseFinale($idTournoi, $phase['libelle'], $phase['ordre']);
        }
    }
}

$ordrePhases    = [16 => 1, 8 => 2, 4 => 3, 2 => 4];
$phaseOrdre     = $ordrePhases[$nombreTotalEquipes] ?? 2;
$phasesLibelles = [
    1 => '8ème de finale',
    2 => 'Quart de finale',
    3 => 'Demi-finale',
    4 => 'Finale',
];

/* ══════════════════════════════════════════════════════════════════
   Nom de catégorie
══════════════════════════════════════════════════════════════════ */
$categorieDao = new CategorieDao();
$nomCategorie = $categorieDao->obtenirCategorie($categorieId)['Nom_categorie'];

/* ══════════════════════════════════════════════════════════════════
   Sauvegarde du nombre de qualifiés dans elimination_config.
   INSERT … ON DUPLICATE KEY UPDATE : jamais de doublon grâce à
   la contrainte UNIQUE KEY uq_config (tournoi_id, categorie_id).
══════════════════════════════════════════════════════════════════ */


/* ══════════════════════════════════════════════════════════════════
   ACTION 1 — preparer_arbre
   Crée les phases finales + les labels uniquement.
   Répond en JSON (appelé en AJAX depuis le Twig).
══════════════════════════════════════════════════════════════════ */
if ($action === 'preparer_arbre') {

    try {
        creerPhasesFinalesSiAbsentes($rencontre, (int)$idTournoi, $phasesACreer);

        $labelsDao = new LabelDao();
        $labelsDao->creerLabelsEliminationDirecte(
            $nomCategorie,
            $nombreTotalEquipes,
            $idTournoi,
            $categorieId
        );

        $rencontre->sauvegarderQualifiesParPoule((int)$idTournoi, (int)$categorieId, $qualifiesNombre);

        header('Location: EliminationDirect.php?id_tournoi=' . $idTournoi . '&idCategorie=' . $categorieId . '&success=arbre_prepare');
        exit;

    } catch (Exception $e) {
       // header('Location: EliminationDirect.php?id_tournoi=' . $idTournoi . '&idCategorie=' . $categorieId . '&error=' . urlencode($e->getMessage()));
        exit;
    }
}
/* ══════════════════════════════════════════════════════════════════
   ACTION 2 — creer_matchs
   Crée les phases, les labels, ET les rencontres.
   Redirection classique après traitement.
══════════════════════════════════════════════════════════════════ */

// 1. Phases finales
creerPhasesFinalesSiAbsentes($rencontre, (int)$idTournoi, $phasesACreer);

// 2. Identifiant de la phase correspondant au nombre d'équipes qualifiées
$phaseId = $rencontre->getPhaseFinaleId($idTournoi, $phaseOrdre);

// 3. Labels (au cas où "Préparer l'arbre" n'aurait pas été appelé avant)
$labelsDao = new LabelDao();
$labelsDao->creerLabelsEliminationDirecte(
    $nomCategorie,
    $nombreTotalEquipes,
    $idTournoi,
    $categorieId
);

// 4. Mémoriser le choix en base
$rencontre->sauvegarderQualifiesParPoule((int)$idTournoi, (int)$categorieId, $qualifiesNombre);
// 5. Construire les matchs selon la logique de croisement des poules
$matchs = [];

if ($qualifiesNombre === 1) {

    // 1 qualifié par poule : 1er de poule A vs 1er de poule B, etc.
    for ($i = 0; $i < count($toutesEquipesQualifiees); $i += 2) {
        if (isset($toutesEquipesQualifiees[$i], $toutesEquipesQualifiees[$i + 1])) {
            $e1 = $toutesEquipesQualifiees[$i];
            $e2 = $toutesEquipesQualifiees[$i + 1];
            $matchs[] = [
                'equipe1' => $e1,
                'equipe2' => $e2,
                'type'    => '1er de poule ' . $e1['poule'] . ' vs 1er de poule ' . $e2['poule'],
            ];
        }
    }

} else {

    // Plusieurs qualifiés : croisement position impaire (poule actuelle)
    // vs position+1 (poule suivante, rotation circulaire)
    for ($i = 0; $i < $nbPoules; $i++) {
        $pouleActuelle = $poules[$i];
        $pouleSuivante = $poules[($i + 1) % $nbPoules];

        for ($pos = 1; $pos <= $qualifiesNombre; $pos++) {

            // Les positions paires sont déjà traitées avec la position précédente
            if ($pos % 2 === 0) {
                continue;
            }

            $positionAdverse = $pos + 1;

            // Pas de position adverse au-delà du nombre de qualifiés
            if ($positionAdverse > $qualifiesNombre) {
                continue;
            }

            if (
                isset($equipesParPosition[$pos][$pouleActuelle]) &&
                isset($equipesParPosition[$positionAdverse][$pouleSuivante])
            ) {
                $e1 = $equipesParPosition[$pos][$pouleActuelle];
                $e2 = $equipesParPosition[$positionAdverse][$pouleSuivante];

                $matchs[] = [
                    'equipe1' => $e1,
                    'equipe2' => $e2,
                    'type'    => $pos . 'er de poule ' . $e1['poule']
                              . ' vs ' . $positionAdverse . 'ème de poule ' . $e2['poule'],
                ];
            }
        }
    }
}

// 6. Insérer les rencontres en base
foreach ($matchs as $match) {
    $rencontre->insertRencontrePhaseFinale(
        $match['equipe1']['id'],
        $match['equipe2']['id'],
        $idTournoi,
        $phaseId
    );
}

// 7. Redirection vers la page de gestion
header('Location: EliminationDirect.php?id_tournoi=' . $idTournoi . '&idCategorie=' . $categorieId);
exit;