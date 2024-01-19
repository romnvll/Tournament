<?php
require ('security.php');
require ('class/equipeDao.class.php');
require ('class/rencontreDao.class.php');

$rencontreDao = new RencontreDAO();
$IdTournoi = $_GET['id_tournoi'];
$rencontres = $rencontreDao->getRencontreByTournoi($IdTournoi);


usort($rencontres, function($a, $b) {
    if ($a['equipe1_poule_nom'] == $b['equipe1_poule_nom']) {
        return $a['rencontre_id'] - $b['rencontre_id'];
    }
    return strcmp($a['equipe1_poule_nom'], $b['equipe1_poule_nom']);
});





// Déterminer le nombre d'équipes dans chaque poule
$equipesParPoule = [];
foreach ($rencontres as $rencontre) {
    $poule = $rencontre['equipe1_poule_nom'];
    if (!isset($equipesParPoule[$poule])) {
        $equipesParPoule[$poule] = [];
    }
    $equipesParPoule[$poule][$rencontre['equipe1_id']] = true;
    $equipesParPoule[$poule][$rencontre['equipe2_id']] = true;
}


$tourCompteur = 1;
$poulePrecedente = null;
$rencontreCompteur = 0;

foreach ($rencontres as $rencontre) {
    if ($poulePrecedente !== $rencontre['equipe1_poule_nom']) {
        $tourCompteur = 1;
        $rencontreCompteur = 0;
        $poulePrecedente = $rencontre['equipe1_poule_nom'];
    }

    $tourNom = "tour" . $tourCompteur . "-" . $rencontre['equipe1_poule_nom'];

    if (!isset($rencontresOrganisees[$tourNom])) {
        $rencontresOrganisees[$tourNom] = [];
    }

    $rencontresOrganisees[$tourNom][] = $rencontre;
    $rencontreCompteur++;

    // Si nous avons parcouru le nombre de rencontres pour un tour
    if ($rencontreCompteur == count($equipesParPoule[$poulePrecedente]) / 2) {
        $tourCompteur++;
        $rencontreCompteur = 0;
    }
}

// ... (Votre code précédent pour organiser les rencontres par tour)

// Fonction de tri pour trier les tours en fonction de l'ordre des catégories et du numéro du tour
function triTours($a, $b) {
    $ordreCategories = ['U17', 'U15', 'U13', 'U13'];

    // Extraire la catégorie et le numéro du tour de la clé
    preg_match('/tour(\d+)-.*-(U\d+).*/', $a, $matchesA);
    preg_match('/tour(\d+)-.*-(U\d+).*/', $b, $matchesB);

    $tourA = intval($matchesA[1]);
    $categorieA = $matchesA[2];

    $tourB = intval($matchesB[1]);
    $categorieB = $matchesB[2];

    // Si les tours sont différents, triez en fonction du numéro du tour
    if ($tourA !== $tourB) {
        return $tourA - $tourB;
    }

    // Sinon, triez en fonction de l'ordre des catégories
    return array_search($categorieA, $ordreCategories) - array_search($categorieB, $ordreCategories);
}

// Triez les clés du tableau $rencontresOrganisees
uksort($rencontresOrganisees, 'triTours');

echo "<pre>";
print_r($rencontresOrganisees);
echo "</pre>";

foreach ($rencontres as $rencontre) {
    $rencontreDao->modifierRencontre($rencontre['rencontre_id'],null,null,$rencontre['num_terrain'],$rencontre['heure_rencontre'],null,$IdTournoi);
}
