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

// ── POST : sauvegarde des contraintes + ordre ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sauvegarder_contraintes') {

    // Sauvegarde de l'ordre de placement des catégories en base
    if (!empty($_POST['ordre_categories'])) {
        foreach ($_POST['ordre_categories'] as $catId => $ordre) {
            $categorieDao->mettreAJourOrdrePlacement((int)$catId, (int)$ordre);
        }
        $categories = $categorieDao->obtenirCategoriesDuTournoi($idTournoi);
    }

    // Sauvegarde des contraintes terrains
   
        $contraintesTerrain = [];

        // On enregistre TOUTES les catégories, même celles sans terrain coché (tableau vide = non placée)
        foreach ($categories as $cat) {
            $catId = (int)$cat['id_categorie'];
            $terrainIds = $_POST['contraintes'][$catId] ?? [];
            $filtered = array_filter(array_map('intval', (array)$terrainIds));
            $contraintesTerrain[$catId] = array_values($filtered); // [] si aucun terrain coché
        }

    $contraintesTerrain['garder_arbitres']                   = isset($_POST['garder_arbitres']) ? 1 : 0;
    $contraintesTerrain['pas_de_placement_pour_les_absents'] = isset($_POST['pas_de_placement_pour_les_absents']);
    $contraintesTerrain['une_categorie_par_creneau']         = isset($_POST['une_categorie_par_creneau']) ? 1 : 0;

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
        usort($rencontresAP, function ($a, $b) {
            // Phases finales en dernier
            $afinal = ($a['phase_finale_id'] !== null && $a['phase_finale_id'] !== '' || (int)($a['type_rencontre_id'] ?? 0) === 3) ? 1 : 0;
            $bfinal = ($b['phase_finale_id'] !== null && $b['phase_finale_id'] !== '' || (int)($b['type_rencontre_id'] ?? 0) === 3) ? 1 : 0;
            if ($afinal !== $bfinal) return $afinal - $bfinal;

            // Tour ASC
            $tourA = (int)($a['tour'] ?? 999);
            $tourB = (int)($b['tour'] ?? 999);
            if ($tourA !== $tourB) return $tourA - $tourB;

            // Ordre personnalisé via ordrePlacementAuto (NULL = en dernier)
            $ordreA = isset($a['equipe1_categorie_ordre']) && $a['equipe1_categorie_ordre'] !== null
                ? (int)$a['equipe1_categorie_ordre'] : null;
            $ordreB = isset($b['equipe1_categorie_ordre']) && $b['equipe1_categorie_ordre'] !== null
                ? (int)$b['equipe1_categorie_ordre'] : null;

            if ($ordreA === null && $ordreB === null) {
                return strcmp($a['equipe1_categorie_nom'] ?? '', $b['equipe1_categorie_nom'] ?? '');
            }
            if ($ordreA === null) return 1;
            if ($ordreB === null) return -1;

            if ($ordreA !== $ordreB) return $ordreA - $ordreB;

            // À même ordre et même tour : trier par poule
            return strcmp($a['equipe1_poule_nom'] ?? '', $b['equipe1_poule_nom'] ?? '');
        });

        // ── 2. Sauvegarder les arbitres avant suppression des créneaux ───────
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

        
        // Recharger avec uniquement le premier créneau
        $listCreneaux = $creneauxDao->afficherCreneaux($idTournoi);

        

        $planificationsRestantes = $planificationDao->afficherPlanifications($idTournoi);

        // ── 4. Initialiser la grille ─────────────────────────────────────────
$grid                = [];
$equipesByCreneau    = [];
$arbitresByCreneau   = [];
$categorieByCreneau  = [];
$rencontresByCreneau = [];
$tourByCreneau       = [];
$phaseFinaleByCreneau = [];

foreach ($listCreneaux as $cr) {
    $cid = $cr['creneau_id'];
    $grid[$cid]                = [];
    $equipesByCreneau[$cid]    = [];
    $arbitresByCreneau[$cid]   = [];
    $categorieByCreneau[$cid]  = null;
    $rencontresByCreneau[$cid] = [];
    $tourByCreneau[$cid]       = null;
    $phaseFinaleByCreneau[$cid] = false;
    foreach ($terrains as $t) {
        $grid[$cid][$t['terrain_id']] = false;
    }
}

            // ← NOUVEAU : marquer les cases déjà occupées dans la grille
            foreach ($planificationsRestantes as $p) {
                $cid = (int)$p['creneau_id'];
                $tid = (int)$p['terrain_id'];

                if (!isset($grid[$cid])) continue;

                if (!empty($p['rencontre_id']) || !empty($p['label_id'])) {
                    $grid[$cid][$tid] = true; // case occupée
                }

                if (!empty($p['arbitre_id'])) {
                    $arbitresByCreneau[$cid][] = (int)$p['arbitre_id'];
                }

                if (!empty($p['equipe1_id'])) {
                    $equipesByCreneau[$cid][] = (int)$p['equipe1_id'];
                }
                if (!empty($p['equipe2_id'])) {
                    $equipesByCreneau[$cid][] = (int)$p['equipe2_id'];
                }

                // Reconstituer catégorie/tour/phase du créneau
                if (!empty($p['rencontre_id'])) {
                    if (!empty($p['equipe1_categorie_id'])) {
                        $categorieByCreneau[$cid] = (int)$p['equipe1_categorie_id'];
                    }
                    if (!empty($p['tour'])) {
                        $tourByCreneau[$cid] = (int)$p['tour'];
                    }
                    $estPF = (!empty($p['phase_finale_id'])) || (int)($p['type_rencontre_id'] ?? 0) === 3;
                    if ($estPF) $phaseFinaleByCreneau[$cid] = true;
                }
            }

        // Enregistrer les arbitres déjà présents
        foreach ($planificationsRestantes as $p) {
            $cid = $p['creneau_id'];
            if (!isset($arbitresByCreneau[$cid])) $arbitresByCreneau[$cid] = [];
            if (!empty($p['arbitre_id'])) {
                $arbitresByCreneau[$cid][] = (int)$p['arbitre_id'];
            }
        }

        // Recréer les arbitres sauvegardés
        foreach ($arbitresSauvegardes as $arb) {
            $cid = $arb['creneau_id'];
            $tid = $arb['terrain_id'];
            $aid = $arb['arbitre_id'];
            $nom = $arb['creneau_nom'];

            $creneauExiste = false;
            foreach ($listCreneaux as $cr) {
                if ((int)$cr['creneau_id'] === $cid) { $creneauExiste = true; break; }
            }

            if (!$creneauExiste) {
                $heureFormatee = substr($nom, 0, 5);
                $creneauxDao->ajouterCreneau($heureFormatee, $idTournoi);
                $listCreneaux = $creneauxDao->afficherCreneaux($idTournoi);
                $newCreneau   = end($listCreneaux);
                $cid          = $newCreneau['creneau_id'];

                $grid[$cid]                = [];
                $equipesByCreneau[$cid]    = [];
                $arbitresByCreneau[$cid]   = [];
                $categorieByCreneau[$cid]  = null;
                $rencontresByCreneau[$cid] = [];
                foreach ($terrains as $t) {
                    $grid[$cid][$t['terrain_id']] = false;
                }
            }

            $planificationDao->ajouterOuModifierPlanification($tid, $cid, null, $idTournoi, $aid);

            if (!isset($arbitresByCreneau[$cid])) $arbitresByCreneau[$cid] = [];
            $arbitresByCreneau[$cid][] = $aid;
        }

        $lastCreneau  = $listCreneaux[array_key_last($listCreneaux)];
        $pasHoraire   = (int)($tournoiInfo['pasHoraire'] ?? 30);
        $uneCategorie = !empty($contraintesTerrain['une_categorie_par_creneau']);

        // ── 5. Placer chaque rencontre ───────────────────────────────────────
        foreach ($rencontresAP as $rencontre) {
            $placed = false;
            $eq1    = isset($rencontre['equipe1_id'])           ? (int)$rencontre['equipe1_id']           : null;
            $eq2    = isset($rencontre['equipe2_id'])           ? (int)$rencontre['equipe2_id']           : null;
            $arb    = isset($rencontre['Arbitre'])              ? (int)$rencontre['Arbitre']              : null;
            $catId  = isset($rencontre['equipe1_categorie_id']) ? (int)$rencontre['equipe1_categorie_id'] : null;
             $tourRencontre = isset($rencontre['tour']) ? (int)$rencontre['tour'] : null;
            $estPhaseFinale = ($rencontre['phase_finale_id'] !== null && $rencontre['phase_finale_id'] !== '')               || (int)($rencontre['type_rencontre_id'] ?? 0) === 3; // ← ligne ajoutée
            // Si aucun terrain sélectionné pour cette catégorie → on skip la rencontre
            if ($catId !== null && isset($contraintesTerrain[$catId]) && empty($contraintesTerrain[$catId])) {
                continue; // Aucun terrain autorisé = catégorie non placée
            }
            $terrainsAutorises = ($catId !== null && isset($contraintesTerrain[$catId]))
                ? $contraintesTerrain[$catId]
                : null;

            // Nb max de rencontres de cette catégorie par créneau = nb terrains autorisés
            $nbTerrainsDispos = $terrainsAutorises !== null ? count($terrainsAutorises) : count($terrains);

            for ($ci = 0; $ci < count($listCreneaux); $ci++) {
                $creneau = $listCreneaux[$ci];
                $cid     = $creneau['creneau_id'];

                if ($eq1 && in_array($eq1, $equipesByCreneau[$cid] ?? [], true)) continue;
                if ($eq2 && in_array($eq2, $equipesByCreneau[$cid] ?? [], true)) continue;
                if ($arb && in_array($arb, $arbitresByCreneau[$cid] ?? [], true)) continue;

                if ($uneCategorie) {
                        if ($categorieByCreneau[$cid] !== null && $categorieByCreneau[$cid] !== $catId) continue;

                        $estPhaseFinale = ($rencontre['phase_finale_id'] !== null && $rencontre['phase_finale_id'] !== '')
                                    || (int)($rencontre['type_rencontre_id'] ?? 0) === 3;

                        // Phase finale ne peut pas aller sur un créneau avec des rencontres normales
                        if ($estPhaseFinale && $tourByCreneau[$cid] !== null) continue;

                        // Rencontre normale ne peut pas aller sur un créneau avec des phases finales
                        if (!$estPhaseFinale && $phaseFinaleByCreneau[$cid]) continue;

                        if (!$estPhaseFinale && $tourByCreneau[$cid] !== null && $tourRencontre !== null && $tourByCreneau[$cid] !== $tourRencontre) continue;

                        $nbDejaPlaces = $rencontresByCreneau[$cid][$catId] ?? 0;
                        if ($nbDejaPlaces >= $nbTerrainsDispos) continue;
                    }

                foreach ($terrains as $terrain) {
                    $tid = $terrain['terrain_id'];
                    if ($terrainsAutorises !== null && !in_array($tid, $terrainsAutorises, true)) continue;
                    if ($grid[$cid][$tid]) continue;

                    // Placement trouvé
                    $planificationDao->ajouterOuModifierPlanification($tid, $cid, $rencontre['id'], $idTournoi);
                    $grid[$cid][$tid]          = true;
                    $categorieByCreneau[$cid]  = $catId;
                    $tourByCreneau[$cid]      = $tourRencontre; // ← ajouter
                    $phaseFinaleByCreneau[$cid]  = $estPhaseFinale; // ← ajouter
                    $rencontresByCreneau[$cid][$catId] = ($rencontresByCreneau[$cid][$catId] ?? 0) + 1;
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

                $grid[$newCid]               = [];
                $equipesByCreneau[$newCid]   = [];
                $arbitresByCreneau[$newCid]  = [];
                $categorieByCreneau[$newCid] = null;
                $rencontresByCreneau[$newCid] = [];
                $phaseFinaleByCreneau[$newCid] = false;
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
                $grid[$newCid][$tid]          = true;
                $categorieByCreneau[$newCid]  = $catId;
                $tourByCreneau[$newCid]      = $tourRencontre;
                $phaseFinaleByCreneau[$newCid] = $estPhaseFinale; // ← ajouter
                $rencontresByCreneau[$newCid][$catId] = 1;
                if ($eq1) $equipesByCreneau[$newCid][]  = $eq1;
                if ($eq2) $equipesByCreneau[$newCid][]  = $eq2;
                if ($arb) $arbitresByCreneau[$newCid][] = $arb;
            }
        }

        header("Location: PlacementDesRencontres.php?id_tournoi={$idTournoi}&auto_success=1");
        exit();
    }
}

// ── Trier les catégories par ordrePlacementAuto pour l'affichage ─────────────
usort($categories, function ($a, $b) {
    $ordreA = isset($a['ordrePlacementAuto']) && $a['ordrePlacementAuto'] !== null
        ? (int)$a['ordrePlacementAuto'] : PHP_INT_MAX;
    $ordreB = isset($b['ordrePlacementAuto']) && $b['ordrePlacementAuto'] !== null
        ? (int)$b['ordrePlacementAuto'] : PHP_INT_MAX;
    if ($ordreA !== $ordreB) return $ordreA - $ordreB;
    return strcmp($a['Nom_categorie'] ?? '', $b['Nom_categorie'] ?? '');
});

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