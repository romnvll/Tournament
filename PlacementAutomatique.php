<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/terrainDao.class.php';
require 'class/creneauxDao.class.php';
require 'class/planificationDao.class.php';
require 'class/arbitreDao.class.php';
require 'class/equipeDao.class.php';
require 'class/categorie.class.php';
require 'Lang/lang.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, ['cache' => false, 'debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('PlacementAutomatique.twig');

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

$creneauxDao      = new creneauxDao();
$terrainDao       = new TerrainDao();
$planificationDao = new planificationDao();
$categorieDao     = new CategorieDao();

$tournoiInfo  = $tournois->getTournoiById($idTournoi);
$terrains     = $terrainDao->AfficherTerrains($idTournoi);
$listCreneaux = $creneauxDao->afficherCreneaux($idTournoi);
$rencontresAP = $planificationDao->afficherRencontresSansPlanification($idTournoi);
$categories   = $categorieDao->obtenirCategoriesDuTournoi($idTournoi);

$erreurs = [];

// ── Session : persistance des contraintes ───────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
$sessionKey = 'contraintes_tournoi_' . $idTournoi;

// ── POST : sauvegarde des contraintes ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sauvegarder_contraintes') {
    $contraintesTerrain = [];
    foreach ($_POST['contraintes'] ?? [] as $catId => $terrainIds) {
        $filtered = array_filter(array_map('intval', (array)$terrainIds));
        if (!empty($filtered)) {
            $contraintesTerrain[(int)$catId] = $filtered;
        }
    }
    $contraintesTerrain['garder_arbitres'] = isset($_POST['garder_arbitres']);
    $contraintesTerrain['pas_de_placement_pour_les_absents'] = isset($_POST['pas_de_placement_pour_les_absents']); // ← ajouter

    $_SESSION[$sessionKey] = $contraintesTerrain;
    header("Location: PlacementAutomatique.php?id_tournoi={$idTournoi}&contraintes_ok=1");
    exit();
}

$contraintesTerrain = $_SESSION[$sessionKey] ?? [];

$rencontresAP = $planificationDao->afficherRencontresSansPlanification(
    $idTournoi,
    !empty($contraintesTerrain['pas_de_placement_pour_les_absents'])
);



// ── POST : lancement du placement ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'placer') {

    if (empty($terrains)) {
        $erreurs[] = "Aucun terrain disponible pour ce tournoi.";
    } elseif (empty($rencontresAP)) {
        $erreurs[] = "Toutes les rencontres sont déjà planifiées.";
    } else {

        // ── 1. Trier les rencontres ──────────────────────────────────────────
        // Tour ASC → catégorie alphabétique → poule | phases finales en dernier
      
           usort($rencontresAP, function ($a, $b) {
    $afinal = ($a['phase_finale_id'] !== null) ? 1 : 0;
    $bfinal = ($b['phase_finale_id'] !== null) ? 1 : 0;
    if ($afinal !== $bfinal) return $afinal - $bfinal;

    $tourA = (int)($a['tour'] ?? 999);
    $tourB = (int)($b['tour'] ?? 999);
    if ($tourA !== $tourB) return $tourA - $tourB;

    $catCmp = strcmp($a['equipe1_categorie_nom'] ?? '', $b['equipe1_categorie_nom'] ?? '');
    if ($catCmp !== 0) return $catCmp;

    return strcmp($a['equipe1_poule_nom'] ?? '', $b['equipe1_poule_nom'] ?? '');
});

        // ── 2. Sauvegarder les arbitres, puis supprimer les créneaux sauf le premier ──
        // supprimerCreneau() efface aussi les lignes arbitres (rencontre_id IS NULL
        // AND label_id IS NULL), on les sauvegarde donc pour les recréer ensuite.
        // Remplace le bloc "Sauvegarder les arbitres avant suppression"
                $garderArbitres = !empty($contraintesTerrain['garder_arbitres']);

                $arbitresSauvegardes = [];
                if ($garderArbitres) {
                    $toutesLesPlanifs = $planificationDao->afficherPlanifications($idTournoi);
                    foreach ($toutesLesPlanifs as $p) {
                        if (!empty($p['arbitre_id']) && empty($p['rencontre_id']) && empty($p['label_id'])) {
                            $creneauNom = '00:00:00';
                            foreach ($listCreneaux as $cr) {
                                if ((int)$cr['creneau_id'] === (int)$p['creneau_id']) {
                                    $creneauNom = $cr['nom'];
                                    break;
                                }
                            }
                            $arbitresSauvegardes[] = [
                                'arbitre_id'  => (int)$p['arbitre_id'],
                                'terrain_id'  => (int)$p['terrain_id'],
                                'creneau_id'  => (int)$p['creneau_id'],
                                'creneau_nom' => $creneauNom,
                            ];
                        }
                    }
                }

        $premierCreneau = $listCreneaux[0];
        foreach ($listCreneaux as $cr) {
            if ($cr['creneau_id'] !== $premierCreneau['creneau_id']) {
                $creneauxDao->supprimerCreneau($cr['creneau_id']);
            }
        }

        // Recharger avec uniquement le premier créneau
        $listCreneaux = $creneauxDao->afficherCreneaux($idTournoi);

        // ── 3. Supprimer les labels existants, lire les arbitres ─────────────
        // Les labels sont supprimés pour libérer les terrains.
        // Les arbitres restent : leur terrain n'est PAS bloqué (une rencontre
        // peut coexister avec un arbitre sur le même créneau/terrain).
        $planificationsRestantes = $planificationDao->afficherPlanifications($idTournoi);

        foreach ($planificationsRestantes as $p) {
            if (!empty($p['label_id'])) {
                // Supprimer la ligne de planification du label
                $planificationDao->supprimerPlanification($p['planification_id']);
            }
        }

        // Recharger après suppression des labels
        $planificationsRestantes = $planificationDao->afficherPlanifications($idTournoi);

        // ── 4. Initialiser la grille ─────────────────────────────────────────
        // Seuls les arbitres sont pris en compte : ils bloquent d'autres arbitres
        // sur le même créneau, mais PAS le terrain (une rencontre peut s'y placer).
        $grid              = [];
        $equipesByCreneau  = [];
        $arbitresByCreneau = [];

        foreach ($listCreneaux as $cr) {
            $cid = $cr['creneau_id'];
            $grid[$cid]              = [];
            $equipesByCreneau[$cid]  = [];
            $arbitresByCreneau[$cid] = [];
            foreach ($terrains as $t) {
                $grid[$cid][$t['terrain_id']] = false;
            }
        }

        // Enregistrer les arbitres déjà présents pour contrainte d'unicité
        foreach ($planificationsRestantes as $p) {
            $cid = $p['creneau_id'];
            if (!isset($arbitresByCreneau[$cid])) $arbitresByCreneau[$cid] = [];
            if (!empty($p['arbitre_id'])) {
                $arbitresByCreneau[$cid][] = (int)$p['arbitre_id'];
            }
        }

        // Recréer les arbitres sauvegardés sur leurs créneaux d'origine.
        // Si le créneau a été supprimé, on le recrée avec son heure d'origine.
        foreach ($arbitresSauvegardes as $arb) {
            $cid = $arb['creneau_id'];
            $tid = $arb['terrain_id'];
            $aid = $arb['arbitre_id'];
            $nom = $arb['creneau_nom'];

            // Vérifier si le créneau existe encore dans $listCreneaux
            $creneauExiste = false;
            foreach ($listCreneaux as $cr) {
                if ((int)$cr['creneau_id'] === $cid) { $creneauExiste = true; break; }
            }

            // Si le créneau a été supprimé, le recréer avec son heure d'origine
            if (!$creneauExiste) {
                $heureFormatee = substr($nom, 0, 5); // "H:i:s" → "H:i"
                $creneauxDao->ajouterCreneau($heureFormatee, $idTournoi);
                $listCreneaux = $creneauxDao->afficherCreneaux($idTournoi);
                $newCreneau   = end($listCreneaux);
                $cid          = $newCreneau['creneau_id'];

                // Initialiser la grille pour ce nouveau créneau
                $grid[$cid]              = [];
                $equipesByCreneau[$cid]  = [];
                $arbitresByCreneau[$cid] = [];
                foreach ($terrains as $t) {
                    $grid[$cid][$t['terrain_id']] = false;
                }
            }

            // Réinsérer l'arbitre en base
            $planificationDao->ajouterOuModifierPlanification($tid, $cid, null, $idTournoi, $aid);

            if (!isset($arbitresByCreneau[$cid])) $arbitresByCreneau[$cid] = [];
            $arbitresByCreneau[$cid][] = $aid;
        }

        $lastCreneau = $listCreneaux[array_key_last($listCreneaux)];
        $pasHoraire  = (int)($tournoiInfo['pasHoraire'] ?? 30);

        // ── 4. Placer chaque rencontre ───────────────────────────────────────
        foreach ($rencontresAP as $rencontre) {
            $placed = false;
            $eq1    = isset($rencontre['equipe1_id'])        ? (int)$rencontre['equipe1_id']        : null;
            $eq2    = isset($rencontre['equipe2_id'])        ? (int)$rencontre['equipe2_id']        : null;
            $arb    = isset($rencontre['Arbitre'])           ? (int)$rencontre['Arbitre']           : null;
            $catId  = isset($rencontre['equipe1_categorie_id']) ? (int)$rencontre['equipe1_categorie_id'] : null;

            $terrainsAutorises = ($catId !== null && isset($contraintesTerrain[$catId]))
                ? $contraintesTerrain[$catId]
                : null;

            for ($ci = 0; $ci < count($listCreneaux); $ci++) {
                $creneau = $listCreneaux[$ci];
                $cid     = $creneau['creneau_id'];

                if ($eq1 && in_array($eq1, $equipesByCreneau[$cid] ?? [], true)) continue;
                if ($eq2 && in_array($eq2, $equipesByCreneau[$cid] ?? [], true)) continue;
                if ($arb && in_array($arb, $arbitresByCreneau[$cid] ?? [], true)) continue;

                foreach ($terrains as $terrain) {
                    $tid = $terrain['terrain_id'];
                    if ($terrainsAutorises !== null && !in_array($tid, $terrainsAutorises, true)) continue;
                    if ($grid[$cid][$tid]) continue;

                    // Placement trouvé
                    $planificationDao->ajouterOuModifierPlanification($tid, $cid, $rencontre['id'], $idTournoi);
                    $grid[$cid][$tid] = true;
                    if ($eq1) $equipesByCreneau[$cid][]  = $eq1;
                    if ($eq2) $equipesByCreneau[$cid][]  = $eq2;
                    if ($arb) $arbitresByCreneau[$cid][] = $arb;
                    $placed = true;
                    break 2;
                }
            }

            // Aucune place → créer un nouveau créneau
            if (!$placed) {
                $lastTime = DateTime::createFromFormat('H:i:s', $lastCreneau['nom'])
                         ?: DateTime::createFromFormat('H:i', $lastCreneau['nom']);
                $lastTime->add(new DateInterval('PT' . $pasHoraire . 'M'));
                $nouvelleHeure = $lastTime->format('H:i');

                $creneauxDao->ajouterCreneau($nouvelleHeure, $idTournoi);
                $listCreneaux = $creneauxDao->afficherCreneaux($idTournoi);
                $newCreneau   = end($listCreneaux);
                $lastCreneau  = $newCreneau;
                $newCid       = $newCreneau['creneau_id'];

                $grid[$newCid]              = [];
                $equipesByCreneau[$newCid]  = [];
                $arbitresByCreneau[$newCid] = [];
                foreach ($terrains as $t) {
                    $grid[$newCid][$t['terrain_id']] = false;
                }

                // Premier terrain autorisé
                $terrainChoisi = $terrains[0];
                foreach ($terrains as $terrain) {
                    if ($terrainsAutorises === null || in_array($terrain['terrain_id'], $terrainsAutorises, true)) {
                        $terrainChoisi = $terrain;
                        break;
                    }
                }
                $tid = $terrainChoisi['terrain_id'];

                $planificationDao->ajouterOuModifierPlanification($tid, $newCid, $rencontre['id'], $idTournoi);
                $grid[$newCid][$tid] = true;
                if ($eq1) $equipesByCreneau[$newCid][]  = $eq1;
                if ($eq2) $equipesByCreneau[$newCid][]  = $eq2;
                if ($arb) $arbitresByCreneau[$newCid][] = $arb;
            }
        }

        header("Location: PlacementDesRencontres.php?id_tournoi={$idTournoi}&auto_success=1");
        exit();
    }
}

// ── Rendu Twig ───────────────────────────────────────────────────────────────
echo $template->render([
    'email'              => $userData['email'],
    'pageEnCours'        => 'GestionDesRencontres',
    'idTournoi'          => $idTournoi,
    'tournoiInfo'        => $tournoiInfo,
    'terrains'           => $terrains,
    'listCreneaux'       => $listCreneaux,
    'rencontresAP'       => $rencontresAP,
    'categories'         => $categories,
    'ListeDesTournois'   => $tournois->afficherLesTournois($userData['id']),
    'erreurs'            => $erreurs,
    'contraintesTerrain' => $contraintesTerrain,
    'contraintes_ok'     => isset($_GET['contraintes_ok']),
]);