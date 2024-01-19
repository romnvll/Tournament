<?php
require 'class/rencontreDao.class.php';

// 1. Récupérer les rencontres de la base de données et trier par heure de rencontre
$pouleid = 4; // Remplacez cela par l'ID de la poule que vous souhaitez récupérer

$rencontreDao = new RencontreDAO();
$rencontres = $rencontreDao->getRencontreByPoule($pouleid); // Récupérer les rencontres sous forme de tableau

// 2. Déterminer le nombre de terrains disponibles
$nombreTerrains = 6; // Remplacez cela par le nombre de terrains disponibles

// 3. Appeler la fonction organiserRencontresSurTerrains

    function organiserRencontresSurTerrains($rencontres, $nombreTerrains) {
        // Triez les rencontres par heure de rencontre (si elles ne sont pas déjà triées)
        usort($rencontres, function($a, $b) {
            return strtotime($a['heure_rencontre']) - strtotime($b['heure_rencontre']);
        });
    
        // Initialisez l'état d'occupation des terrains avec NULL pour indiquer qu'ils sont libres au début
        $terrainsOccupes = array_fill(1, $nombreTerrains, NULL);
    
        // Structure pour stocker les rencontres organisées par terrain
        $rencontresOrganisees = array_fill(1, $nombreTerrains, []);
    
        foreach ($rencontres as $rencontre) {
            // Trouvez un terrain disponible pour la rencontre
            $terrainDisponible = array_search(NULL, $terrainsOccupes);
    
            if ($terrainDisponible !== false) {
                // Vérifiez si les équipes impliquées dans la rencontre jouent déjà sur d'autres terrains
                $equipe1JoueDeja = in_array($rencontre['equipe1_id'], $terrainsOccupes);
                $equipe2JoueDeja = in_array($rencontre['equipe2_id'], $terrainsOccupes);
    
                if (!$equipe1JoueDeja && !$equipe2JoueDeja) {
                    // Attribuez la rencontre au terrain disponible
                    $terrainsOccupes[$terrainDisponible] = $rencontre['equipe1_id'];
                    $rencontresOrganisees[$terrainDisponible][] = $rencontre;
                }
            }
        }
    
        return $rencontresOrganisees;
    }
    
    // Le reste du code pour organiser les rencontres, comme défini dans la fonction précédente


$rencontresOrganisees = organiserRencontresSurTerrains($rencontres, $nombreTerrains);

// 4. Afficher les rencontres organisées par terrain
foreach ($rencontresOrganisees as $terrain => $rencontresSurTerrain) {
    echo "Terrain $terrain :\n";
    foreach ($rencontresSurTerrain as $rencontre) {
        echo "Rencontre ID : " . $rencontre['rencontre_id'] . " - Heure : " . $rencontre['heure_rencontre'] . "\n";
        // Afficher d'autres détails de la rencontre si nécessaire
    }
    echo "\n";
}
?>
