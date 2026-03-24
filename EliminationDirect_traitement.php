<?php
require ('security.php');
require 'class/rencontreDao.class.php';
require 'class/labelsDao.class.php';
require ('class/categorie.class.php');

$rencontre = new RencontreDAO();
$categorieId = $_POST['categorieId'];
$qualifiesNombre = intval($_POST['qualifiesNombre']);
$equipes = $_POST['equipes']; // Tableau organisé par poule
$idTournoi = $_GET['id_tournoi'];

// Organiser les équipes par position dans chaque poule
$equipesParPosition = [];
$poules = array_keys($equipes);

foreach ($equipes as $pouleId => $equipeIds) {
    foreach ($equipeIds as $index => $equipeId) {
        $position = $index + 1; // Position dans la poule (1, 2, 3, etc.)
        
        // Ne garder que les X premiers
        if ($position <= $qualifiesNombre) {
            $equipesParPosition[$position][$pouleId] = [
                'id' => $equipeId,
                'poule' => $pouleId,
                'position' => $position
            ];
        }
    }
}

// Rassembler toutes les équipes qualifiées dans un seul tableau
$toutesEquipesQualifiees = [];
for ($pos = 1; $pos <= $qualifiesNombre; $pos++) {
    if (isset($equipesParPosition[$pos])) {
        foreach ($equipesParPosition[$pos] as $equipe) {
            $toutesEquipesQualifiees[] = $equipe;
        }
    }
}

$nombreTotalEquipes = count($toutesEquipesQualifiees);

// Créer les matchs
$matchs = [];
// Créer les phases finales pour ce tournoi si elles n'existent pas encore
$phasesACreer = [
    ['libelle' => '8ème de finale',          'ordre' => 1],
    ['libelle' => 'Quart de finale',          'ordre' => 2],
    ['libelle' => 'Demi-finale',              'ordre' => 3],
    ['libelle' => 'Finale',                   'ordre' => 4],
    ['libelle' => 'Match pour la 3ème place', 'ordre' => 5],
];

foreach ($phasesACreer as $phase) {
    if (!$rencontre->phasesFinalesExistent($idTournoi, $phase['ordre'])) {
        $rencontre->insertPhaseFinale($idTournoi, $phase['libelle'], $phase['ordre']);
    }
}

$ordrePhases = [16 => 1, 8 => 2, 4 => 3, 2 => 4];
$phaseId = $rencontre->getPhaseFinaleId($idTournoi, $ordrePhases[$nombreTotalEquipes] ?? 2);

//fin création des phases finales pour un tournoi donné




$nbPoules = count($poules);

if ($qualifiesNombre == 1) {
    // Si on prend 1 seul par poule : 1er vs 1er entre poules différentes
    for ($i = 0; $i < count($toutesEquipesQualifiees); $i += 2) {
        if (isset($toutesEquipesQualifiees[$i]) && isset($toutesEquipesQualifiees[$i + 1])) {
            $matchs[] = [
                'equipe1' => $toutesEquipesQualifiees[$i],
                'equipe2' => $toutesEquipesQualifiees[$i + 1],
                'type' => '1er de poule ' . $toutesEquipesQualifiees[$i]['poule'] . 
                          ' vs 1er de poule ' . $toutesEquipesQualifiees[$i + 1]['poule']
            ];
        }
    }
} else {
    
    // Pour chaque poule
    for ($i = 0; $i < $nbPoules; $i++) {
        $pouleActuelle = $poules[$i];
        
        // Trouver la poule suivante (rotation circulaire)
        $pouleSuivante = $poules[($i + 1) % $nbPoules];
        
        // Pour chaque position : 1er de poule actuelle vs 2ème de poule suivante
        for ($pos = 1; $pos <= $qualifiesNombre; $pos++) {
            // Position adverse : si pos=1 on cherche pos=2, si pos=2 on cherche pos=1, etc.
            // On alterne pour croiser
            if ($pos % 2 == 1) {
                // Position impaire (1, 3, 5...) : chercher position+1 dans poule suivante
                $positionAdverse = $pos + 1;
                if ($positionAdverse > $qualifiesNombre) continue; // Pas de position suivante
            } else {
                // Position paire (2, 4, 6...) : on a déjà créé ce match avec la position précédente
                continue;
            }
            
            if (isset($equipesParPosition[$pos][$pouleActuelle]) && 
                isset($equipesParPosition[$positionAdverse][$pouleSuivante])) {
                
                $equipe1 = $equipesParPosition[$pos][$pouleActuelle];
                $equipe2 = $equipesParPosition[$positionAdverse][$pouleSuivante];
                
                $matchs[] = [
                    'equipe1' => $equipe1,
                    'equipe2' => $equipe2,
                    'type' => $pos . 'er de poule ' . $equipe1['poule'] . 
                              ' vs ' . $positionAdverse . 'ème de poule ' . $equipe2['poule']
                ];
            }
        }
    }
}

echo "<div class='alert alert-info'>";
echo "Nombre d'équipes qualifiées : " . $nombreTotalEquipes . "<br>";
echo "Nombre de matchs créés : " . count($matchs) . "<br>";
echo "Phase ID sélectionnée : ";


echo $phaseId;
echo "</div>";

// Affichage des matchs
echo "<h2>Matchs de la phase éliminatoire - Catégorie ID: $categorieId</h2>";
echo "<p><strong>Phase : ";
$phasesLibelles = [1 => "8ème de finale", 2 => "Quart de finale", 3 => "Demi-finale", 4 => "Finale"];
echo $phasesLibelles[$phaseId] ?? "Phase inconnue";
echo "</strong> ($nombreTotalEquipes équipes qualifiées)</p>";
echo "<div class='container'>";


//getNomCategorieById
$categorieDao = new CategorieDao();
$nomCategorie = $categorieDao->obtenirCategorie($categorieId)['Nom_categorie'];


//création des labels pour la phase d'élimination directe
$labelsDao = new LabelDao();
$labelsCrees = $labelsDao->creerLabelsEliminationDirecte($nomCategorie, $nombreTotalEquipes, $idTournoi, $categorieId);




foreach ($matchs as $match) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>" . $match['type'] . "</h5>";
    echo "<p class='card-text'>";
    echo "Équipe " . $match['equipe1']['id'] . " (Position " . $match['equipe1']['position'] . " - Poule " . $match['equipe1']['poule'] . ")";
    echo " <strong>VS</strong> ";
    echo "Équipe " . $match['equipe2']['id'] . " (Position " . $match['equipe2']['position'] . " - Poule " . $match['equipe2']['poule'] . ")";
    echo "</p>";
    echo "</div>";
    echo "</div>";
    
    $rencontre->insertRencontrePhaseFinale(
        $match['equipe1']['id'],
        $match['equipe2']['id'],
        $idTournoi,
        $phaseId
    );
}

echo "</div>";

echo "<div class='alert alert-success mt-3'>";
echo count($matchs) . " matchs créés avec succès pour la phase " . ($phasesLibelles[$phaseId] ?? "");
echo "</div>";

 header('Location: EliminationDirect.php?id_tournoi=' . $idTournoi.'&idCategorie=' . $categorieId);
?>
