<?php

class RencontreDAO

{
    private $connexion;
    

    public function __construct()
    {
        require 'databaseInformations.php';
        //require 'evenementsDao.class.php';

        try {
            $this->connexion = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Erreur de connexion à la base de données : " . $e->getMessage();
            exit;
        }
    }




   public function createRencontreByPoule($pouleId, $tournoi_id, $typeRencontreId = TYPE_RENCONTRE_POULE, $isMatchRetour = false)
{
    // Récupérer uniquement les équipes de cette poule dont le champ 'IsPresent' est vrai
    $equipesPresentes = $this->getEquipesPresentesByPoule($pouleId);

    // Vérifier s'il y a au moins deux équipes présentes pour créer des rencontres
    if (count($equipesPresentes) >= 2) {

        // Générer les rencontres avec l'algorithme du round-robin
        $rencontres = $this->generateRoundRobin($equipesPresentes, $isMatchRetour);

        // Insérer les rencontres dans la table Rencontres
        foreach ($rencontres as $rencontre) {

            // Vérifier si la rencontre existe déjà
            if ($this->isRencontreExist($rencontre['equipe1']['id'], $rencontre['equipe2']['id'], $rencontre['tour'])) {
                continue;
            }

            // Insérer la rencontre avec la référence à la poule
            $this->insertRencontre(
                $rencontre['equipe1']['id'],
                $rencontre['equipe2']['id'],
                $tournoi_id,
                $typeRencontreId,
                $rencontre['tour'],
                $pouleId
            );
        }
    }
}

    /**
 * Retourne l’ID de l’équipe gagnante d’une rencontre.
 *
 * @param int $rencontreId
 * @return int|null  ID de l'équipe gagnante, ou null si pas de gagnant
 */
public function getWinner($rencontreId)
{
    $sql = "SELECT equipe1_id, equipe2_id, score1, score2
            FROM Rencontres
            WHERE id = :id";

    $stmt = $this->connexion->prepare($sql);
    $stmt->execute([':id' => $rencontreId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        return null; // Match inexistant
    }

    // Si l’un des scores n’est pas encore rempli → pas de gagnant
    if ($match['score1'] === null || $match['score2'] === null) {
        return null;
    }

    // Détermination du gagnant
    if ($match['score1'] > $match['score2']) {
        return $match['equipe1_id'];
    } elseif ($match['score2'] > $match['score1']) {
        return $match['equipe2_id'];
    }

    // En cas d’égalité → tu peux changer cette logique
    return null;
}


    private function isRencontreExist($equipe1Id, $equipe2Id, $tour)
{
    // Vérifier si la rencontre existe
    $query = "SELECT COUNT(*) FROM Rencontres WHERE ((equipe1_id = :equipe1Id OR equipe2_id = :equipe1Id) AND (equipe1_id = :equipe2Id OR equipe2_id = :equipe2Id)) AND tour = :tour";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
    $stmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
    $stmt->bindValue(':tour', $tour, PDO::PARAM_INT);
    $stmt->execute();

    $count = $stmt->fetchColumn();

    // Si la rencontre existe plus d'une fois, retourner vrai (la rencontre ne doit pas être créée à nouveau)
    if ($count > 1) {
        return true;
    }

    // Sinon, retourner faux (la rencontre peut être créée)
    return false;
}



private function generateRoundRobin($equipes, $isMatchRetour = false)
{
    $n = count($equipes);
    $rencontres = [];
    $dummyTeamAdded = false;

    if ($n % 2 !== 0) {
        // Si le nombre d'équipes est impair, ajouter une équipe fictive
        $equipes[] = ['id' => null, 'name' => 'Dummy'];
        $n++;
        $dummyTeamAdded = true;
    }

    // Générer les rencontres aller
    for ($i = 0; $i < $n - 1; $i++) {

        for ($j = 0; $j < $n / 2; $j++) {
            $equipe1 = $equipes[$j];
            $equipe2 = $equipes[$n - 1 - $j];

            // Si une équipe fictive a été ajoutée, assurez-vous qu'elle n'est pas incluse dans les rencontres
            if (!($dummyTeamAdded && ($equipe1['id'] === null || $equipe2['id'] === null))) {
                $rencontres[] = [
                    'equipe1' => $equipe1,
                    'equipe2' => $equipe2,
                    'tour' => $i + 1
                ];
            }
        }

        // Rotation spécifique des équipes pour la prochaine ronde
        $lastTeam = array_pop($equipes);
        array_splice($equipes, 1, 0, [$lastTeam]);
    }

    // Générer les rencontres retour
    if ($isMatchRetour) {
        for ($i = 0; $i < $n - 1; $i++) {

            for ($j = 0; $j < $n / 2; $j++) {
                $equipe1 = $equipes[$n - 1 - $j];
                $equipe2 = $equipes[$j];

                // Si une équipe fictive a été ajoutée, assurez-vous qu'elle n'est pas incluse dans les rencontres
                if (!($dummyTeamAdded && ($equipe1['id'] === null || $equipe2['id'] === null))) {
                    $rencontres[] = [
                        'equipe1' => $equipe1,
                        'equipe2' => $equipe2,
                        'tour' => $n + $i
                    ];
                }
            }

            // Rotation spécifique des équipes pour la prochaine ronde
            $lastTeam = array_pop($equipes);
            array_splice($equipes, 1, 0, [$lastTeam]);
        }
    }
    
    return $rencontres;
}



//Test phase finale

/**
 * Récupère toutes les rencontres de phase finale pour une catégorie donnée
 */
public function getRencontresPhasesFinales($tournoi_id, $categorie_id = null)
{
    if ($categorie_id) {
        $query = "
        SELECT 
    r.id,
    r.equipe1_id,
    r.equipe2_id,
    r.score1,
    r.score2,
    r.isTerminated,
    r.phase_finale_id,

    e1.nom as equipe1_nom,
    e1.club_id as equipe1_club_id,
    e2.nom as equipe2_nom,
    e2.club_id as equipe2_club_id,
    c1.nom as equipe1_club_nom,
    c2.nom as equipe2_club_nom,
    c1.logo as equipe1_club_logo,
    c2.logo as equipe2_club_logo,

    pf.libelle as phase_libelle,
    pf.ordre as phase_ordre,

    p.planification_id,
    p.arbitre_id,
    a.nom AS arbitre_nom,

    t.terrain_id,
    t.nom as terrain_nom,
    cr.creneau_id,
    cr.nom as heure_rencontre,
    cr.ordre as ordre_creneau,

    CASE 
        WHEN r.isTerminated = 1 THEN 'Terminée'
        WHEN p.planification_id IS NOT NULL THEN 'Planifiée'
        ELSE 'Non planifiée'
    END as statut_rencontre

FROM Rencontres r
LEFT JOIN Equipes e1 ON r.equipe1_id = e1.id
LEFT JOIN Equipes e2 ON r.equipe2_id = e2.id
LEFT JOIN Clubs c1 ON e1.club_id = c1.id
LEFT JOIN Clubs c2 ON e2.club_id = c2.id

INNER JOIN phases_finales pf ON r.phase_finale_id = pf.id
LEFT JOIN Planification p ON r.id = p.rencontre_id
LEFT JOIN Arbitres a ON p.arbitre_id = a.arbitre_id
LEFT JOIN Terrains t ON p.terrain_id = t.terrain_id
LEFT JOIN Creneaux cr ON p.creneau_id = cr.creneau_id

WHERE r.tournoi_id = :tournoi_id
  AND (e1.categorie = :categorie_id OR r.equipe1_id IS NULL)
  AND r.phase_finale_id IS NOT NULL

ORDER BY pf.ordre ASC, cr.ordre ASC, r.id ASC;

        
        ";
    } else {
        // Sans catégorie, récupérer toutes les rencontres vides
        $query = "SELECT 
                    r.id,
                    r.equipe1_id,
                    r.equipe2_id,
                    r.score1,
                    r.score2,
                    r.isTerminated,
                    r.phase_finale_id,
                    e1.nom as equipe1_nom,
                    e2.nom as equipe2_nom,
                    pf.libelle as phase_libelle,
                    pf.ordre as phase_ordre
                  FROM Rencontres r
                  LEFT JOIN Equipes e1 ON r.equipe1_id = e1.id
                  LEFT JOIN Equipes e2 ON r.equipe2_id = e2.id
                  INNER JOIN phases_finales pf ON r.phase_finale_id = pf.id
                  WHERE r.tournoi_id = :tournoi_id
                    AND r.phase_finale_id IS NOT NULL
                  ORDER BY pf.ordre ASC, r.id ASC";
    }
    
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    if ($categorie_id) {
        $stmt->bindValue(':categorie_id', $categorie_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Supprime une rencontre de phase finale (avec ses dépendances)
 */
public function supprimerRencontrePhaseFinale($rencontre_id)
{
    try {
        // Démarrer une transaction
        $this->connexion->beginTransaction();
        
        // Supprimer d'abord les entrées dans Planification
        $query1 = "DELETE FROM Planification WHERE rencontre_id = :rencontre_id";
        $stmt1 = $this->connexion->prepare($query1);
        $stmt1->bindValue(':rencontre_id', $rencontre_id, PDO::PARAM_INT);
        $stmt1->execute();
        
        // Ensuite supprimer la rencontre
        $query2 = "DELETE FROM Rencontres 
                   WHERE id = :rencontre_id 
                     AND phase_finale_id IS NOT NULL";
        $stmt2 = $this->connexion->prepare($query2);
        $stmt2->bindValue(':rencontre_id', $rencontre_id, PDO::PARAM_INT);
        $stmt2->execute();
        
        // Valider la transaction
        $this->connexion->commit();
        
        return true;
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $this->connexion->rollBack();
        return false;
    }
}

/**
 * Supprime toutes les rencontres d'une phase finale donnée (avec leurs dépendances)
 */
public function supprimerRencontresParPhaseFinale($phase_finale_id, $tournoi_id)
{
    try {
        // Démarrer une transaction
        $this->connexion->beginTransaction();
        
        // Supprimer d'abord les entrées dans Planification
        $query1 = "DELETE p FROM Planification p
                   INNER JOIN Rencontres r ON r.id = p.rencontre_id
                   WHERE r.phase_finale_id = :phase_finale_id 
                     AND r.tournoi_id = :tournoi_id";
        $stmt1 = $this->connexion->prepare($query1);
        $stmt1->bindValue(':phase_finale_id', $phase_finale_id, PDO::PARAM_INT);
        $stmt1->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt1->execute();
        
        // Ensuite supprimer les rencontres
        $query2 = "DELETE FROM Rencontres 
                   WHERE phase_finale_id = :phase_finale_id 
                     AND tournoi_id = :tournoi_id";
        $stmt2 = $this->connexion->prepare($query2);
        $stmt2->bindValue(':phase_finale_id', $phase_finale_id, PDO::PARAM_INT);
        $stmt2->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt2->execute();
        
        // Valider la transaction
        $this->connexion->commit();
        
        return true;
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $this->connexion->rollBack();
        return false;
    }
}


/**
 * Récupère le nom de la catégorie
 */
public function getNomCategorie($categorie_id)
{
    
    $query = "SELECT Nom_categorie FROM Categorie WHERE id_categorie = :categorie_id";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':categorie_id', $categorie_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['Nom_categorie'] : "Catégorie $categorie_id";
}

/**
 * Organise les rencontres par phase
 */
private function organiserParPhase($rencontres)
{
    $phases = [];
    
    foreach ($rencontres as $rencontre) {
        $phaseOrdre = $rencontre['phase_ordre'];
        
        if (!isset($phases[$phaseOrdre])) {
            $phases[$phaseOrdre] = [
                'libelle' => $rencontre['phase_libelle'],
                'rencontres' => []
            ];
        }
        
        $phases[$phaseOrdre]['rencontres'][] = $rencontre;
    }
    
    ksort($phases);
    return $phases;
}

/**
 * Crée toutes les rencontres vides pour toutes les phases finales
 */
public function creerRencontresVidesPhaseFinale($tournoi_id, $nombreEquipesDepart)
{
    // Déterminer les phases nécessaires selon le nombre d'équipes
    $phasesNecessaires = [];
    
    if ($nombreEquipesDepart >= 16) {
        $phasesNecessaires[] = ['nombre_matchs' => 8, 'ordre' => 1, 'libelle' => '8ème de finale'];
    }
    if ($nombreEquipesDepart >= 8) {
        $phasesNecessaires[] = ['nombre_matchs' => 4, 'ordre' => 2, 'libelle' => 'Quart de finale'];
    }
    if ($nombreEquipesDepart >= 4) {
        $phasesNecessaires[] = ['nombre_matchs' => 2, 'ordre' => 3, 'libelle' => 'Demi-finale'];
    }
    if ($nombreEquipesDepart >= 2) {
        $phasesNecessaires[] = ['nombre_matchs' => 1, 'ordre' => 4, 'libelle' => 'Finale'];
    }
    
    $rencontresCrees = [];
    
    foreach ($phasesNecessaires as $phaseInfo) {
        // Récupérer l'ID de la phase depuis la base
        $query = "SELECT id FROM phases_finales 
                  WHERE tournoi_id = :tournoi_id 
                    AND ordre = :ordre 
                  LIMIT 1";
        
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->bindValue(':ordre', $phaseInfo['ordre'], PDO::PARAM_INT);
        $stmt->execute();
        
        $phase = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$phase) {
            continue; // Phase non trouvée, passer à la suivante
        }
        
        // Créer les rencontres vides pour cette phase
        for ($i = 0; $i < $phaseInfo['nombre_matchs']; $i++) {
            $query = "INSERT INTO Rencontres 
                      (equipe1_id, equipe2_id, tournoi_id, phase_finale_id, isClassement) 
                      VALUES 
                      (NULL, NULL, :tournoi_id, :phase_id, 0)";
            
            $stmt = $this->connexion->prepare($query);
            $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
            $stmt->bindValue(':phase_id', $phase['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $rencontresCrees[] = [
                'phase' => $phaseInfo['libelle'],
                'match_id' => $this->connexion->lastInsertId()
            ];
        }
    }
    
    return [
        'success' => true,
        'message' => count($rencontresCrees) . ' rencontres vides créées',
        'rencontres' => $rencontresCrees
    ];
}
/**
 * Affecte une équipe à une rencontre vide
 */
public function affecterEquipeARencontre($rencontre_id, $equipe_id, $position = 1)
{
    // Vérifier que la rencontre existe
    $query = "SELECT equipe1_id, equipe2_id FROM Rencontres WHERE id = :rencontre_id";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':rencontre_id', $rencontre_id, PDO::PARAM_INT);
    $stmt->execute();
    $rencontre = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rencontre) {
        return ['success' => false, 'message' => 'Rencontre non trouvée'];
    }
    
    // Déterminer quelle position est disponible
    if ($position == 1 || $rencontre['equipe1_id'] === null) {
        $champ = 'equipe1_id';
    } elseif ($position == 2 || $rencontre['equipe2_id'] === null) {
        $champ = 'equipe2_id';
    } else {
        return ['success' => false, 'message' => 'Les deux positions sont déjà occupées'];
    }
    
    // Affecter l'équipe
    $query = "UPDATE Rencontres SET $champ = :equipe_id WHERE id = :rencontre_id";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':equipe_id', $equipe_id, PDO::PARAM_INT);
    $stmt->bindValue(':rencontre_id', $rencontre_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Équipe affectée avec succès'];
}

/**
 * Détermine le vainqueur d'une rencontre
 */
private function getVainqueur($rencontre)
{
    if (!$rencontre['isTerminated'] || $rencontre['score1'] === null || $rencontre['score2'] === null) {
        return null;
    }
    
    if ($rencontre['score1'] > $rencontre['score2']) {
        return [
            'id' => $rencontre['equipe1_id'],
            'nom' => $rencontre['equipe1_nom']
        ];
    } elseif ($rencontre['score2'] > $rencontre['score1']) {
        return [
            'id' => $rencontre['equipe2_id'],
            'nom' => $rencontre['equipe2_nom']
        ];
    }
    
    return null; // Match nul
}
/**
 * Récupère la phase suivante
 */
private function getPhaseSuivante($phase_ordre_actuel, $tournoi_id)
{
    $query = "SELECT id, libelle, ordre 
              FROM phases_finales 
              WHERE tournoi_id = :tournoi_id 
                AND ordre = :ordre_suivant
              LIMIT 1";
    
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $stmt->bindValue(':ordre_suivant', $phase_ordre_actuel + 1, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Crée les matchs de la phase suivante avec les vainqueurs
 */
public function creerMatchsPhaseSuivante($tournoi_id, $categorie_id, $phase_actuelle_id)
{
    // Récupérer toutes les rencontres de la phase actuelle
    $query = "SELECT 
                r.id,
                r.equipe1_id,
                r.equipe2_id,
                r.score1,
                r.score2,
                r.isTerminated,
                r.phase_finale_id,
                e1.nom as equipe1_nom,
                e2.nom as equipe2_nom,
                pf.ordre as phase_ordre
              FROM Rencontres r
              INNER JOIN Equipes e1 ON r.equipe1_id = e1.id
              LEFT JOIN Equipes e2 ON r.equipe2_id = e2.id
              INNER JOIN phases_finales pf ON r.phase_finale_id = pf.id
              WHERE r.tournoi_id = :tournoi_id
                AND e1.categorie = :categorie_id
                AND r.phase_finale_id = :phase_actuelle_id
              ORDER BY r.id ASC";
    
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $stmt->bindValue(':categorie_id', $categorie_id, PDO::PARAM_INT);
    $stmt->bindValue(':phase_actuelle_id', $phase_actuelle_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $rencontres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier que tous les matchs sont terminés
    foreach ($rencontres as $rencontre) {
        if (!$rencontre['isTerminated']) {
            return [
                'success' => false,
                'message' => 'Tous les matchs de cette phase doivent être terminés avant de créer la phase suivante.'
            ];
        }
    }
    
    // Récupérer les vainqueurs
    $vainqueurs = [];
    foreach ($rencontres as $rencontre) {
        $vainqueur = $this->getVainqueur($rencontre);
        if ($vainqueur) {
            $vainqueurs[] = $vainqueur;
        }
    }
    
    if (count($vainqueurs) < 2) {
        return [
            'success' => false,
            'message' => 'Pas assez de vainqueurs pour créer la phase suivante.'
        ];
    }
    
    // Récupérer la phase suivante
    $phaseSuivante = $this->getPhaseSuivante($rencontres[0]['phase_ordre'], $tournoi_id);
    
    if (!$phaseSuivante) {
        return [
            'success' => false,
            'message' => 'Aucune phase suivante trouvée (c\'est peut-être déjà la finale).'
        ];
    }
    
    // Créer les matchs de la phase suivante
    $nbMatchsCrees = 0;
    for ($i = 0; $i < count($vainqueurs); $i += 2) {
        if (isset($vainqueurs[$i]) && isset($vainqueurs[$i + 1])) {
            $resultat = $this->insertRencontrePhaseFinale(
                $vainqueurs[$i]['id'],
                $vainqueurs[$i + 1]['id'],
                $tournoi_id,
                $phaseSuivante['id']
            );
            
            if ($resultat) {
                $nbMatchsCrees++;
            }
        }
    }
    
    return [
        'success' => true,
        'message' => "$nbMatchsCrees match(s) créé(s) pour la phase : " . $phaseSuivante['libelle'],
        'phase' => $phaseSuivante['libelle'],
        'nb_matchs' => $nbMatchsCrees
    ];
}

/**
 * Génère automatiquement toutes les phases suivantes jusqu'à la finale
 */
public function genererToutesPhasesSuivantes($tournoi_id, $categorie_id)
{
    $resultats = [];
    $continuer = true;
    
    // Récupérer la première phase avec des matchs
    $query = "SELECT DISTINCT pf.id, pf.ordre
              FROM Rencontres r
              INNER JOIN phases_finales pf ON r.phase_finale_id = pf.id
              INNER JOIN Equipes e ON r.equipe1_id = e.id
              WHERE r.tournoi_id = :tournoi_id
                AND e.categorie = :categorie_id
              ORDER BY pf.ordre ASC
              LIMIT 1";
    
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $stmt->bindValue(':categorie_id', $categorie_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $phaseActuelle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$phaseActuelle) {
        return [
            'success' => false,
            'message' => 'Aucune phase finale trouvée pour cette catégorie.'
        ];
    }
    
    $maxIterations = 10; // Sécurité pour éviter une boucle infinie
    $iterations = 0;
    
    while ($continuer && $iterations < $maxIterations) {
        $resultat = $this->creerMatchsPhaseSuivante($tournoi_id, $categorie_id, $phaseActuelle['id']);
        
        if ($resultat['success']) {
            $resultats[] = $resultat;
            
            // Passer à la phase suivante
            $phaseSuivante = $this->getPhaseSuivante($phaseActuelle['ordre'], $tournoi_id);
            if ($phaseSuivante) {
                $phaseActuelle = $phaseSuivante;
            } else {
                $continuer = false;
            }
        } else {
            $resultats[] = $resultat;
            $continuer = false;
        }
        
        $iterations++;
    }
    
    return [
        'success' => true,
        'resultats' => $resultats
    ];
}

/**
 * Affiche l'arbre du tournoi pour une catégorie donnée
 */
public function afficherArbreTournoi($tournoi_id, $categorie_id)
{
    $rencontres = $this->getRencontresPhasesFinales($tournoi_id, $categorie_id);
    $nomCategorie = $this->getNomCategorie($categorie_id);
    
    if (empty($rencontres)) {
        return [
            'erreur' => 'Aucune rencontre de phase finale pour cette catégorie.',
            'nomCategorie' => $nomCategorie
        ];
    }
    
    $phases = $this->organiserParPhase($rencontres);
    $message = null;
    $messageType = null;
    
    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generer_phase_suivante') {
        $resultat = $this->genererToutesPhasesSuivantes($_POST['tournoi_id'], $_POST['categorie_id']);
        
        if ($resultat['success'] && !empty($resultat['resultats'])) {
            $message = $resultat['resultats'];
            $messageType = 'info';
            
            // Recharger les données
            $rencontres = $this->getRencontresPhasesFinales($tournoi_id, $categorie_id);
            $phases = $this->organiserParPhase($rencontres);
        } else {
            $message = $resultat['message'] ?? 'Erreur inconnue';
            $messageType = 'warning';
        }
    }
    
    return [
        'nomCategorie' => $nomCategorie,
        'tournoi_id' => $tournoi_id,
        'categorie_id' => $categorie_id,
        'phases' => $phases,
        'message' => $message,
        'messageType' => $messageType
    ];
}

//fin test phase finale



    public function getEquipesPresentesByPoule($pouleId)
    {
        // Effectuez une requête SQL pour récupérer les équipes donnée en utilisant une jointure
        $query = "SELECT e.id, e.nom 
                  FROM Equipes e 
                  INNER JOIN EquipePoule ep ON e.id = ep.equipe_id 
                  WHERE ep.poule_id = :pouleId";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertRencontrePhaseFinale($equipe1Id, $equipe2Id, $tournoi_id, $phaseId)
    
{
    // Vérifie si la rencontre existe déjà pour cette phase et ce tournoi
    $checkQuery = "SELECT * FROM Rencontres 
                   WHERE equipe1_id = :equipe1Id 
                     AND equipe2_id = :equipe2Id 
                     AND tournoi_id = :tournoi_id 
                     AND phase_finale_id = :phaseId";
    
    $checkStmt = $this->connexion->prepare($checkQuery);
    $checkStmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
    $checkStmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
    $checkStmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $checkStmt->bindValue(':phaseId', $phaseId, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        return false; // La rencontre existe déjà
    }

    // Insertion de la nouvelle rencontre de phase finale
    $query = "INSERT INTO Rencontres 
              (equipe1_id, equipe2_id, tournoi_id, phase_finale_id, isClassement) 
              VALUES 
              (:equipe1Id, :equipe2Id, :tournoi_id, :phaseId, 0)";
    
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
    $stmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
    $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $stmt->bindValue(':phaseId', $phaseId, PDO::PARAM_INT);
    $stmt->execute();

    return true;
}

    public function insertRencontre($equipe1Id, $equipe2Id, $tournoi_id, $typeRencontreId, $tour = null, $pouleId = null)
{
    // Vérifie si la rencontre existe déjà dans la même poule et le même tournoi
    $checkQuery = "SELECT * FROM Rencontres 
                   WHERE equipe1_id = :equipe1Id 
                     AND equipe2_id = :equipe2Id 
                     AND tournoi_id = :tournoi_id 
                     AND type_rencontre_id = :typeRencontreId
                     AND (poule_id = :pouleId OR (:pouleId IS NULL AND poule_id IS NULL))";
    
    $checkStmt = $this->connexion->prepare($checkQuery);
    $checkStmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
    $checkStmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
    $checkStmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $checkStmt->bindValue(':typeRencontreId', $typeRencontreId, PDO::PARAM_INT);
    $checkStmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        return false; // La rencontre existe déjà
    }

    // Insertion de la nouvelle rencontre
    $query = "INSERT INTO Rencontres 
              (equipe1_id, equipe2_id, tournoi_id, type_rencontre_id, tour, poule_id) 
              VALUES 
              (:equipe1Id, :equipe2Id, :tournoi_id, :typeRencontreId, :tour, :pouleId)";
    
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
    $stmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
    $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $stmt->bindValue(':typeRencontreId', $typeRencontreId, PDO::PARAM_INT);
    $stmt->bindValue(':tour', $tour, PDO::PARAM_INT);
    $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
    $stmt->execute();

    return true;
}

public function afficherRencontresParType($tournoi_id, $type_rencontre_id)
{
    $query = "SELECT r.*, 
                     e1.nom AS equipe1_nom, 
                     e2.nom AS equipe2_nom
              FROM Rencontres r
              LEFT JOIN Equipes e1 ON r.equipe1_id = e1.id
              LEFT JOIN Equipes e2 ON r.equipe2_id = e2.id
              WHERE r.tournoi_id = :tournoi_id 
                AND r.type_rencontre_id = :type_rencontre_id
              ORDER BY r.id DESC";
    
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $stmt->bindValue(':type_rencontre_id', $type_rencontre_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function supprimerRencontre($rencontre_id)
{
    $query = "DELETE FROM Rencontres WHERE id = :id";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':id', $rencontre_id, PDO::PARAM_INT);
    return $stmt->execute();
}

   public function supprimerRencontresParPoule(int $idPoule): bool
{
    try {
        // Récupérer le type de poule (is_classement)
        $stmtTypePoule = $this->connexion->prepare("SELECT is_classement FROM Poules WHERE id = :idPoule");
        $stmtTypePoule->bindParam(':idPoule', $idPoule);
        $stmtTypePoule->execute();
        $isClassement = $stmtTypePoule->fetch(PDO::FETCH_COLUMN);

        // Trouver toutes les équipes de la poule
        $stmtEquipesPoule = $this->connexion->prepare("SELECT equipe_id FROM EquipePoule WHERE poule_id = :idPoule");
        $stmtEquipesPoule->bindParam(':idPoule', $idPoule);
        $stmtEquipesPoule->execute();
        $equipes = $stmtEquipesPoule->fetchAll(PDO::FETCH_COLUMN);
       
        if (empty($equipes)) {
            return true; // Pas d'équipes, considéré comme succès
        }

        // Construire les placeholders pour la requête IN
        $placeholders = implode(',', array_fill(0, count($equipes), '?'));
        
        // Récupérer les rencontre_id des rencontres à supprimer
        if ($isClassement == 1) {
            $sql = "SELECT id FROM Rencontres 
                    WHERE (equipe1_id IN ($placeholders) OR equipe2_id IN ($placeholders)) 
                    AND isClassement = 1";
        } else {
            $sql = "SELECT id FROM Rencontres 
                    WHERE equipe1_id IN ($placeholders) OR equipe2_id IN ($placeholders)";
        }
        
        $stmtRencontreIds = $this->connexion->prepare($sql);
        $stmtRencontreIds->execute(array_merge($equipes, $equipes));
        $rencontreIds = $stmtRencontreIds->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($rencontreIds)) {
            return true; // Pas de rencontres à supprimer, considéré comme succès
        }

        // Supprimer les rencontre_id de la table Planification
        $placeholdersRencontre = implode(',', array_fill(0, count($rencontreIds), '?'));
       
        $stmtDeletePlanification = $this->connexion->prepare("DELETE FROM Planification WHERE rencontre_id IN ($placeholdersRencontre)");
        $stmtDeletePlanification->execute($rencontreIds);

        // Supprimer les rencontres de la table Rencontres
        $stmtRencontres = $this->connexion->prepare("DELETE FROM Rencontres WHERE id IN ($placeholdersRencontre)");
        $stmtRencontres->execute($rencontreIds);
        
        return true; // Succès
        
    } catch (PDOException $e) {
        // Log l'erreur si nécessaire
        error_log("Erreur suppression rencontres: " . $e->getMessage());
        return false; // Échec
    }
}

    public function supprimerRencontresParTournoi(int $idtournoi): void
    {
        $query = "DELETE FROM Rencontres WHERE tournoi_id = :idtournoi";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idtournoi', $idtournoi, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function equipeAsRencontreAmicale(int $equipeId): bool
    {
        $query = "SELECT COUNT(*) FROM Rencontres WHERE (equipe1_id = :equipeId OR equipe2_id = :equipeId) AND type_rencontre_id = 4";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':equipeId', $equipeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function phasesFinalesExistent($tournoi_id, $ordre)
{
    $stmt = $this->connexion->prepare("
        SELECT COUNT(*) FROM phases_finales 
        WHERE tournoi_id = :tournoi_id AND ordre = :ordre
    ");
    $stmt->execute([':tournoi_id' => $tournoi_id, ':ordre' => $ordre]);
    return $stmt->fetchColumn() > 0;
}

public function insertPhaseFinale($tournoi_id, $libelle, $ordre,)
{
    $stmt = $this->connexion->prepare("
        INSERT INTO phases_finales (tournoi_id, libelle, ordre) 
        VALUES (:tournoi_id, :libelle, :ordre, :id_categorie)
    ");
    $stmt->execute([
        ':tournoi_id' => $tournoi_id,
        ':libelle'    => $libelle,
        ':ordre'      => $ordre,
        
    ]);
}


public function sauvegarderQualifiesParPoule(int $idTournoi, int $categorieId, int $qualifiesNombre): void
{
    $stmt = $this->connexion->prepare("
        INSERT INTO elimination_config (tournoi_id, categorie_id, qualifies_par_poule)
        VALUES (:tournoi_id, :categorie_id, :qualifies)
        ON DUPLICATE KEY UPDATE qualifies_par_poule = VALUES(qualifies_par_poule)
    ");
    $stmt->execute([
        ':tournoi_id'   => $idTournoi,
        ':categorie_id' => $categorieId,
        ':qualifies'    => $qualifiesNombre,
    ]);
}

public function getInfoQualifiesParPoule(int $idTournoi, int $categorieId): ?int
{
    $stmt = $this->connexion->prepare("
        SELECT qualifies_par_poule
        FROM elimination_config
        WHERE tournoi_id = :tournoi_id
        AND categorie_id = :categorie_id
    ");
    $stmt->execute([
        ':tournoi_id'   => $idTournoi,
        ':categorie_id' => $categorieId,
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? (int)$result['qualifies_par_poule'] : null;
}

public function getPhaseFinaleId($tournoi_id, $ordre)
{
    $stmt = $this->connexion->prepare("
        SELECT id FROM phases_finales 
        WHERE tournoi_id = :tournoi_id AND ordre = :ordre
        LIMIT 1
    ");
    $stmt->execute([':tournoi_id' => $tournoi_id, ':ordre' => $ordre]);
    return $stmt->fetchColumn();
}


    public function getProchainesRencontres($tournoiId)
{
    $query = "
        SELECT 
            r.id AS rencontre_id,
            r.equipe1_id,
            r.equipe2_id,
            e1.nom AS equipe1_nom,
            e2.nom AS equipe2_nom,
            r.score1,
            r.score2,
            r.isTerminated,
            c.nom AS heure,
            t.nom AS terrain_nom,
            a.nom AS arbitre_nom
        FROM Rencontres r
        JOIN Planification p ON p.rencontre_id = r.id
        LEFT JOIN Creneaux c ON p.creneau_id = c.creneau_id
        LEFT JOIN Terrains t ON p.terrain_id = t.terrain_id
        LEFT JOIN Arbitres a ON p.arbitre_id = a.arbitre_id
        LEFT JOIN Equipes e1 ON r.equipe1_id = e1.id
        LEFT JOIN Equipes e2 ON r.equipe2_id = e2.id
        WHERE r.isTerminated IN (0, 2)
          AND r.tournoi_id = :tournoiId
        ORDER BY c.ordre ASC, r.id ASC
        LIMIT 15
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
    $stmt->execute();

    $rencontres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $rencontres;
}



    function rencontresCategorieDejaPlanifiees($categorieId, $tournoiId) {
        // Étape 1: Récupérer les identifiants des équipes de la catégorie concernée.
        $query = "SELECT id FROM Equipes WHERE categorie = :categorieId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':categorieId', $categorieId, PDO::PARAM_INT);
        $stmt->execute();
        $equipes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
        if (empty($equipes)) {
            return false; // Aucune équipe dans cette catégorie
        }
    
        // Étape 2: Vérifier si l'une de ces équipes a une rencontre planifiée pour le tournoi spécifié
        $query = "
            SELECT p.*, r.equipe1_id, r.equipe2_id
            FROM Planification p
            JOIN Rencontres r ON p.rencontre_id = r.id
            WHERE (r.equipe1_id IN (" . implode(',', array_map('intval', $equipes)) . ")
               OR r.equipe2_id IN (" . implode(',', array_map('intval', $equipes)) . "))
               AND r.tournoi_id = :tournoiId
        ";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        $planifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return !empty($planifications);
    }
    



   public function getRencontreByPoule($pouleid, $typeRencontreId = TYPE_RENCONTRE_POULE, $from = 'index')
{
    $orderBy = ($from === 'tour') ? "r.tour, c.creneau_id, r.id" : "c.creneau_id, r.id";
    $additionalCondition = ($from === 'tour') ? '' : 'AND t.nom IS NOT NULL';

    $query = "
        SELECT 
            r.id AS rencontre_id,
            r.tour AS tour,
            p.terrain_id,
            t.nom AS terrain_nom,
            p.creneau_id,
            c.nom AS creneau_nom,
            
            equipe1.isPresent AS equipe1_isPresent,
            equipe1.id AS equipe1_id,
            equipe1.nomCoach AS equipe1_coach_nom,
            equipe1.nom AS equipe1_nom,
            club1.id AS club1_id,
            club1.nom AS club1_nom,
            club1.logo AS club1_logo,
            
            equipe2.id AS equipe2_id,
            equipe2.isPresent AS equipe2_isPresent,
            equipe2.nom AS equipe2_nom,
            equipe2.nomCoach AS equipe2_coach_nom,
            club2.id AS club2_id,
            club2.nom AS club2_nom,
            club2.logo AS club2_logo,

            cat1.Nom_categorie AS equipe1_categorie_nom,
            cat1.id_categorie AS equipe1_categorie_id,
            cat1.Couleur AS equipe1_categorie_couleur,
            cat2.Nom_categorie AS equipe2_categorie_nom,
            cat2.Couleur AS equipe2_categorie_couleur,
            cat2.id_categorie AS equipe2_categorie_id,          
            
            r.score1,
            r.score2,
            r.isTerminated,
            
            a.nom AS arbitre_nom,
            clubArbitre.nom AS arbitre_club_nom,
            
            CASE 
                WHEN EXISTS (
                    SELECT 1 
                    FROM Labels l 
                    WHERE l.categorie_id = equipe1.categorie 
                    AND l.tournoi_id = r.tournoi_id
                    AND l.description LIKE 'EliminationDirect%'
                ) THEN 1 
                ELSE 0 
            END AS equipe1_has_phase_finale,
            
            CASE 
                WHEN EXISTS (
                    SELECT 1 
                    FROM Labels l 
                    WHERE l.categorie_id = equipe2.categorie 
                    AND l.tournoi_id = r.tournoi_id
                    AND l.description LIKE 'EliminationDirect%'
                ) THEN 1 
                ELSE 0 
            END AS equipe2_has_phase_finale

        FROM 
            Rencontres r
        JOIN 
            Equipes equipe1 ON r.equipe1_id = equipe1.id
        JOIN 
            EquipePoule ep1 ON equipe1.id = ep1.equipe_id AND ep1.poule_id = :pouleid
        JOIN 
            Clubs club1 ON equipe1.club_id = club1.id
        LEFT JOIN 
            Categorie cat1 ON equipe1.categorie = cat1.id_categorie
        JOIN 
            Equipes equipe2 ON r.equipe2_id = equipe2.id
        JOIN 
            EquipePoule ep2 ON equipe2.id = ep2.equipe_id AND ep2.poule_id = :pouleid
        JOIN 
            Clubs club2 ON equipe2.club_id = club2.id
        LEFT JOIN 
            Planification p ON r.id = p.rencontre_id
        LEFT JOIN 
            Creneaux c ON p.creneau_id = c.creneau_id
        LEFT JOIN 
            Terrains t ON p.terrain_id = t.terrain_id
        LEFT JOIN 
            Arbitres a ON p.arbitre_id = a.arbitre_id
        LEFT JOIN 
            Clubs clubArbitre ON a.club_id = clubArbitre.id
        LEFT JOIN 
            Categorie cat2 ON equipe2.categorie = cat2.id_categorie
        WHERE 
            r.type_rencontre_id = :typeRencontreId
            $additionalCondition
        ORDER BY 
            $orderBy;
    ";
    
    $stmt = $this->connexion->prepare($query);
    $stmt->bindParam(':pouleid', $pouleid, PDO::PARAM_INT);
    $stmt->bindParam(':typeRencontreId', $typeRencontreId, PDO::PARAM_INT);
    
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function updateStatusByCreneau(int $idCreneau, int $status): void
{
    $query = "
        UPDATE Rencontres r
        JOIN Planification p ON r.id = p.rencontre_id
        SET r.isTerminated = :status
        WHERE p.creneau_id = :idCreneau
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':status', $status, PDO::PARAM_INT);
    $stmt->bindValue(':idCreneau', $idCreneau, PDO::PARAM_INT);
    $stmt->execute();
}

    


    public function updateStatus(int $idrencontre, int $status)
    {
        $query = "UPDATE Rencontres SET isTerminated = :status WHERE id = :idrencontre";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':status', $status, PDO::PARAM_INT);
        $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getRencontre(int $idrencontre)
{
    $query = "SELECT score1, score2, isTerminated FROM Rencontres WHERE id = :idrencontre";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    


    public function resetRencontre(int $idrencontre)
    {
        $query = "UPDATE Rencontres set terrain = null where id=:idrencontre";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
        $stmt->execute();

        $query = "UPDATE Rencontres set heure = null where id=:idrencontre";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
        $stmt->execute();
    }


public function updateTour(int $idrencontre, int $tour)
{
    $query = "UPDATE Rencontres SET tour = :tour WHERE id = :idrencontre";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
    $stmt->bindValue(':tour', $tour, PDO::PARAM_INT);
    $stmt->execute();
}






 /**
         * Modifie les informations d'une rencontre dans la base de données.
         *
         * @param int|null    $idrencontre ID de la rencontre à modifier.
         * @param int|null    $equipeScore1 Score de l'équipe 1 (null efface le score ou "9999" pour conserver le score).
         * @param int|null    $equipeScore2 Score de l'équipe 2 (null efface le score ou "9999" pour conserver le score).
         * @param int|null    $terrain     ID du terrain (null pour exclure).
         * @param string|null $heure       Heure de la rencontre (null pour exclure).
         * @param string|null $arbitre     Nom de l'arbitre (null ou "dontTouch" pour exclure).
         * @param int|null    $tournoi_id  ID du tournoi (null pour exclure).
         *
         * @return void
         */

         public function modifierRencontre(
            ?int $idrencontre,
            ?int $equipeScore1 = null,
            ?int $equipeScore2 = null,
            
           
            ?int $tournoi_id = null
        ) 
       
        {
           
            $query = "UPDATE Rencontres SET";
            $params = [];
        
            if ($equipeScore1 !== null) {
                if ($equipeScore1 !== 9999) {
                    $query .= " score1 = :score1,";
                    $params[':score1'] = $equipeScore1;
                }
            } else {
                $query .= " score1 = NULL,";
            }
        
            if ($equipeScore2 !== null) {
                if ($equipeScore2 !== 9999) {
                    $query .= " score2 = :score2,";
                    $params[':score2'] = $equipeScore2;
                }
            } else {
                $query .= " score2 = NULL,";
            }         
        
           
        
            if ($tournoi_id !== null) {
                $query .= " tournoi_id = :tournoi_id,";
                $params[':tournoi_id'] = $tournoi_id;
            }
        
            // Supprime la virgule finale
            $query = rtrim($query, ',');
            $query .= " WHERE id = :idrencontre";
            
            $stmt = $this->connexion->prepare($query);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            
            $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
           
            $stmt->execute();
            
        }
        

/**
 * Récupère les scores de l'équipe 1 et de l'équipe 2 pour une rencontre donnée.
 *
 * @param int $idrencontre ID de la rencontre dont les scores doivent être récupérés.
 * @return array Associatif contenant 'score1' et 'score2', ou null si aucune donnée n'est trouvée.
 */
public function getScore(int $idrencontre): ?array
{
    // Préparer la requête pour récupérer les scores
    $query = "SELECT score1, score2 FROM Rencontres WHERE id = :idrencontre";
    $stmt = $this->connexion->prepare($query);

    // Lier l'ID de la rencontre à la requête
    $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
    
    // Exécuter la requête
    $stmt->execute();
    
    // Récupérer les résultats
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si aucune ligne n'est trouvée, renvoyer null
    if ($result === false) {
        return null;
    }

    // Renvoyer les scores sous forme de tableau associatif
    return [
        'score1' => $result['score1'],
        'score2' => $result['score2']
    ];
}

public function rencontresExistByCategorieAndTournoi(string $categorie, int $idtournoi): bool
{
    $query = "SELECT COUNT(*) FROM Rencontres r
              JOIN Equipes e1 ON r.equipe1_id = e1.id
              JOIN Equipes e2 ON r.equipe2_id = e2.id
              WHERE e1.categorie = :categorie AND e2.categorie = :categorie AND r.tournoi_id = :idtournoi";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':categorie', $categorie, PDO::PARAM_STR);
    $stmt->bindValue(':idtournoi', $idtournoi, PDO::PARAM_INT);
    $stmt->execute();

    $count = $stmt->fetchColumn();

    return $count > 0;
}



       public function GetResultatDesPoules(int $idPoule, int $typeRencontreId = TYPE_RENCONTRE_POULE)
{
    $query = "
        SELECT
            e.id,
            e.nom,
            e.categorie,
            e.tournoi_id,
            ep.poule_id,
            e.club_id,
            c.nom AS club_nom,
            c.logo AS club_logo,
            ((SELECT COUNT(*) FROM Rencontres r WHERE r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL AND ((r.equipe1_id = e.id AND r.score1 > r.score2) OR (r.equipe2_id = e.id AND r.score2 > r.score1))) * 3) +
            ((SELECT COUNT(*) FROM Rencontres r WHERE r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL AND ((r.equipe1_id = e.id OR r.equipe2_id = e.id) AND r.score1 = r.score2)) * 2) +
            ((SELECT COUNT(*) FROM Rencontres r WHERE r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL AND ((r.equipe1_id = e.id AND r.score1 < r.score2) OR (r.equipe2_id = e.id AND r.score2 < r.score1)))) AS TotalDesPoints,
            COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL), 0) +
            COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL), 0) AS nombreButsMarque,
            COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL), 0) +
            COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL), 0) AS nombreButsEncaisse,
            (COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL), 0) +
            COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL), 0)) -
            (COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL), 0) +
            COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.type_rencontre_id = :typeRencontreId AND r.phase_finale_id IS NULL), 0)) AS DifferenceButs
        FROM
            Equipes e
        JOIN
            EquipePoule ep ON e.id = ep.equipe_id
        JOIN
            Clubs c ON e.club_id = c.id
        WHERE
            ep.poule_id = :pouleId
        ORDER BY
            TotalDesPoints DESC,
            nombreButsMarque DESC,
            DifferenceButs DESC;
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':pouleId', $idPoule, PDO::PARAM_INT);
    $stmt->bindValue(':typeRencontreId', $typeRencontreId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
        

    ////
    public function GetEquipesClasseesParPoule(int $idTournoi)
    {
        // Obtenez la liste des poules pour ce tournoi
        $query = "SELECT DISTINCT ep.poule_id 
                      FROM EquipePoule ep 
                      JOIN Equipes e ON e.id = ep.equipe_id 
                      WHERE e.tournoi_id = :idTournoi";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->execute();
        $poules = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $equipesParPoule = [];

        foreach ($poules as $pouleId) {
            // Récupérez les équipes classées pour cette poule
            $query = "SELECT
                            e.id,
                            e.nom,
                            e.categorie,
                            
                            e.tournoi_id,
                            ep.poule_id,
                            e.club_id,
                            ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id AND r.score1 > r.score2) OR (r.equipe2_id = e.id AND r.score2 > r.score1)) * 3) +
                            ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id OR r.equipe2_id = e.id) AND r.score1 = r.score2) * 2) +
                            ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id AND r.score1 < r.score2) OR (r.equipe2_id = e.id AND r.score2 < r.score1))) AS TotalDesPoints,
                            COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id), 0) +
                            COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id), 0) AS nombreButsMarque,
                            COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id), 0) +
                            COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id), 0) AS nombreButsEncaisse,
                            (COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id), 0) +
                            COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id), 0)) -
                            (COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id), 0) +
                            COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id), 0)) AS DifferenceButs
                        FROM
                            Equipes e
                        JOIN EquipePoule ep ON e.id = ep.equipe_id
                        WHERE
                            ep.poule_id = :pouleId AND
                            
                            e.tournoi_id = :idTournoi
                        ORDER BY
                            TotalDesPoints DESC,
                            nombreButsMarque DESC,
                            DifferenceButs DESC";

            $stmt = $this->connexion->prepare($query);
            $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
            $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
            $stmt->execute();

            $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $equipesParPoule[$pouleId] = $equipes;
        }

        return $equipesParPoule;
    }

    

    public function CreerRencontresDeClassement(int $idTournoi)
    {
        $equipesParPoule = $this->GetEquipesClasseesParPoule($idTournoi);

        // Segmenter les poules par catégorie d'âge et sexe
        $poulesParCategorie = [];
        foreach ($equipesParPoule as $pouleName => $equipes) {
            preg_match("/(U\d+[MF])-\d+$/", $pouleName, $matches);

            if (isset($matches[1])) {
                $categorie = $matches[1];
                if (!isset($poulesParCategorie[$categorie])) {
                    $poulesParCategorie[$categorie] = [];
                }
                $poulesParCategorie[$categorie][] = $equipes;
            }
        }

        $rencontres = [];

        foreach ($poulesParCategorie as $categorie => $poules) {
            $maxRank = count(reset($poules));

            // Pour chaque classement
            for ($rank = 0; $rank < $maxRank; $rank++) {
                $equipesForRank = [];

                // Collecter toutes les équipes pour le classement actuel de toutes les poules
                foreach ($poules as $poule) {
                    if (isset($poule[$rank])) {
                        $equipesForRank[] = $poule[$rank];
                    }
                }

                // Créer des rencontres pour ce classement
                for ($i = 0; $i < count($equipesForRank) - 1; $i += 2) {
                    for ($j = $i + 1; $j < count($equipesForRank); $j++) {
                        $rencontres[] = [
                            'equipe1_id' => $equipesForRank[$i]['id'],
                            'equipe2_id' => $equipesForRank[$j]['id'],
                            'tournoi_id' => $idTournoi
                        ];
                        break; // Nous avons seulement besoin de la première rencontre pour ce i
                    }
                }
            }
        }


        foreach ($rencontres as $match) {
            // Vérifier si la rencontre existe déjà
            $checkQuery = "SELECT id FROM Rencontres WHERE 
                           (equipe1_id = :equipe1_id AND equipe2_id = :equipe2_id) OR 
                           (equipe1_id = :equipe2_id AND equipe2_id = :equipe1_id) AND 
                           tournoi_id = :tournoi_id";
            $stmt = $this->connexion->prepare($checkQuery);
            $stmt->execute([
                'equipe1_id' => $match['equipe1_id'],
                'equipe2_id' => $match['equipe2_id'],
                'tournoi_id' => $match['tournoi_id']
            ]);

            if ($stmt->fetchColumn() == 0) {  // Si la rencontre n'existe pas, insérez-la
                $insertQuery = "INSERT INTO Rencontres (equipe1_id, equipe2_id, tournoi_id) VALUES (:equipe1_id, :equipe2_id, :tournoi_id)";
                $stmt = $this->connexion->prepare($insertQuery);
                $stmt->execute($match);
            }
        }
    }





    ////


    function afficherRencontreByTournoiByClub(int $idTournoi, int $club)
    {
        $query = "
            SELECT 
                r.id AS rencontre_id,
                r.tour AS tour,
                p.terrain_id,
                t.nom AS terrain_nom,
                p.creneau_id,
                c.nom AS creneau_nom,
                
                equipe1.id AS equipe1_id,
                equipe1.nom AS equipe1_nom,
                club1.id AS club1_id,
                club1.nom AS club1_nom,
                
                club1.logo AS club1_logo,
                cat1.id_categorie AS equipe1_categorie_id,
                cat1.Nom_categorie AS equipe1_categorie_nom,
                cat1.Couleur AS equipe1_categorie_couleur,
                
                equipe2.id AS equipe2_id,
                equipe2.nom AS equipe2_nom,
                club2.id AS club2_id,
                club2.nom AS club2_nom,
                
                club2.logo AS club2_logo,
                cat2.id_categorie AS equipe2_categorie_id,
                cat2.Nom_categorie AS equipe2_categorie_nom,
                cat2.Couleur AS equipe2_categorie_couleur,
                
                r.score1,
                r.score2,
                r.isTerminated,
    
              
    
                a.nom AS arbitre_nom,
                clubArbitre.nom AS arbitre_club_nom
    
            FROM 
                Rencontres r
            JOIN 
                Equipes equipe1 ON r.equipe1_id = equipe1.id
            JOIN 
                Clubs club1 ON equipe1.club_id = club1.id
            JOIN 
                Categorie cat1 ON equipe1.categorie = cat1.id_categorie
            JOIN 
                Equipes equipe2 ON r.equipe2_id = equipe2.id
            JOIN 
                Clubs club2 ON equipe2.club_id = club2.id
            JOIN 
                Categorie cat2 ON equipe2.categorie = cat2.id_categorie
            LEFT JOIN 
                Planification p ON r.id = p.rencontre_id
            LEFT JOIN 
                Creneaux c ON p.creneau_id = c.creneau_id
            LEFT JOIN 
                Terrains t ON p.terrain_id = t.terrain_id
            
            LEFT JOIN 
                Arbitres a ON p.arbitre_id = a.arbitre_id
            LEFT JOIN 
                Clubs clubArbitre ON a.club_id = clubArbitre.id
            WHERE 
                (equipe1.club_id = :club OR equipe2.club_id = :club)
                AND (equipe1.tournoi_id = :idTournoi OR equipe2.tournoi_id = :idTournoi)
                AND p.creneau_id IS NOT NULL
            ORDER BY 
                p.creneau_id, r.tour;
        ";
    
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':club', $club, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function afficherRencontresParTerrainEtTournoi(int $terrainId, int $idTournoi)
{
    $query = "
        SELECT 
            r.id AS rencontre_id,
            r.tour AS tour,
            p.terrain_id,
            t.nom AS terrain_nom,
            p.creneau_id,
            c.nom AS creneau_nom,
            
            equipe1.id AS equipe1_id,
            equipe1.nom AS equipe1_nom,
            equipe1.nomCoach AS equipe1_coach_nom,
            club1.id AS club1_id,
            club1.nom AS club1_nom,
            
            
            club1.logo AS club1_logo,
            cat1.id_categorie AS equipe1_categorie_id,
            cat1.Nom_categorie AS equipe1_categorie_nom,
            cat1.Couleur AS equipe1_categorie_couleur,
            
            equipe2.id AS equipe2_id,
            equipe2.nom AS equipe2_nom,
            equipe2.nomCoach AS equipe2_coach_nom,
            club2.id AS club2_id,
            club2.nom AS club2_nom,
            
            
            club2.logo AS club2_logo,
            cat2.id_categorie AS equipe2_categorie_id,
            cat2.Nom_categorie AS equipe2_categorie_nom,
            cat2.Couleur AS equipe2_categorie_couleur,
            
            r.score1,
            r.score2,
            r.isTerminated,
    
            
                
            a.nom AS arbitre_nom,
            clubArbitre.nom AS arbitre_club_nom
    
        FROM 
            Rencontres r
        JOIN 
            Equipes equipe1 ON r.equipe1_id = equipe1.id
        JOIN 
            Clubs club1 ON equipe1.club_id = club1.id
        JOIN 
            Categorie cat1 ON equipe1.categorie = cat1.id_categorie
        JOIN 
            Equipes equipe2 ON r.equipe2_id = equipe2.id
        JOIN 
            Clubs club2 ON equipe2.club_id = club2.id
        JOIN 
            Categorie cat2 ON equipe2.categorie = cat2.id_categorie
        LEFT JOIN 
            Planification p ON r.id = p.rencontre_id
        LEFT JOIN 
            Creneaux c ON p.creneau_id = c.creneau_id
        LEFT JOIN 
            Terrains t ON p.terrain_id = t.terrain_id
        
        LEFT JOIN 
            Arbitres a ON p.arbitre_id = a.arbitre_id
        LEFT JOIN 
            Clubs clubArbitre ON a.club_id = clubArbitre.id
        WHERE 
            p.terrain_id = :terrainId
            AND equipe1.tournoi_id = :idTournoi
        ORDER BY 
            p.creneau_id, r.tour;
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':terrainId', $terrainId, PDO::PARAM_INT);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    

    function afficherRencontreByTournoiByEquipe(int $idTournoi, int $id_equipe)
    {
        $query = "
            SELECT 
                r.id AS rencontre_id,
                r.tour AS tour,
                p.terrain_id,
                t.nom AS terrain_nom,
                p.creneau_id,
                c.nom AS creneau_nom,
                
                equipe1.id AS equipe1_id,
                equipe1.nom AS equipe1_nom,
                equipe1.nomCoach AS equipe1_coach_nom,
                club1.id AS club1_id,
                club1.nom AS club1_nom,
                
                club1.logo AS club1_logo,
                cat1.id_categorie AS equipe1_categorie_id,
                cat1.Nom_categorie AS equipe1_categorie_nom,
                cat1.Couleur AS equipe1_categorie_couleur,
                
                equipe2.id AS equipe2_id,
                equipe2.nom AS equipe2_nom,
                equipe2.nomCoach AS equipe2_coach_nom,
                club2.id AS club2_id,
                club2.nom AS club2_nom,
                
                club2.logo AS club2_logo,
                cat2.id_categorie AS equipe2_categorie_id,
                cat2.Nom_categorie AS equipe2_categorie_nom,
                cat2.Couleur AS equipe2_categorie_couleur,
                
                r.score1,
                r.score2,
                r.type_rencontre_id,
                r.isTerminated,
    
                 
                a.nom AS arbitre_nom,
                clubArbitre.nom AS arbitre_club_nom
    
            FROM 
                Rencontres r
            JOIN 
                Equipes equipe1 ON r.equipe1_id = equipe1.id
            JOIN 
                Clubs club1 ON equipe1.club_id = club1.id
            JOIN 
                Categorie cat1 ON equipe1.categorie = cat1.id_categorie
            JOIN 
                Equipes equipe2 ON r.equipe2_id = equipe2.id
            JOIN 
                Clubs club2 ON equipe2.club_id = club2.id
            JOIN 
                Categorie cat2 ON equipe2.categorie = cat2.id_categorie
            LEFT JOIN 
                Planification p ON r.id = p.rencontre_id
            LEFT JOIN 
                Creneaux c ON p.creneau_id = c.creneau_id
            LEFT JOIN 
                Terrains t ON p.terrain_id = t.terrain_id
            
            LEFT JOIN 
                Arbitres a ON p.arbitre_id = a.arbitre_id
            LEFT JOIN 
                Clubs clubArbitre ON a.club_id = clubArbitre.id
            WHERE 
                (equipe1.id = :id_equipe OR equipe2.id = :id_equipe)
                AND (equipe1.tournoi_id = :idTournoi OR equipe2.tournoi_id = :idTournoi)
                AND p.creneau_id IS NOT NULL
            ORDER BY 
                p.creneau_id, r.tour;
        ";
    
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':id_equipe', $id_equipe, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    function rencontreExisteDeja($equipe1Id, $equipe2Id)
    {

        $query = "SELECT COUNT(*) FROM Rencontres WHERE (equipe1_id = :equipe1Id AND equipe2_id = :equipe2Id) OR (equipe1_id = :equipe2Id AND equipe2_id = :equipe1Id)";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
        $stmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return ($count > 0);

        // Pour l'exemple, on renvoie toujours faux pour éviter la duplication.
        //return false;
    }


    function rencontrePouleDejaPlanifiee($pouleId) {
        // Étape 1: Récupérer les identifiants des équipes de la poule concernée.
        $query = "SELECT equipe_id FROM EquipePoule WHERE poule_id = :pouleId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
        $stmt->execute();
        $equipes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
        if (empty($equipes)) {
            return false; // Aucune équipe dans cette poule
        }
    
        // Étape 2: Vérifier si l'une de ces équipes figure dans une rencontre planifiée
        $query = "
            SELECT COUNT(*) 
            FROM Planification p
            JOIN Rencontres r ON p.rencontre_id = r.id
            WHERE r.equipe1_id IN (" . implode(',', array_map('intval', $equipes)) . ")
               OR r.equipe2_id IN (" . implode(',', array_map('intval', $equipes)) . ")
        ";
        $stmt = $this->connexion->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchColumn();
    
        return ($count > 0);
    }
    

    function updateEtatRencontre($etat, $tournoi_id, $heure)
    {
        // Vérifier que $etat est valide (0, 1 ou 2)
        if ($etat < 0 || $etat > 3) {
            // Retourner une erreur ou gérer le cas invalide selon vos besoins
            return false;
        }

        $query = "UPDATE Rencontres SET etat = :etat WHERE tournoi_id = :tournoiId and heure = :heure";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':etat', $etat, PDO::PARAM_INT);
        $stmt->bindValue(':heure', $heure, PDO::PARAM_STR);
        $stmt->bindValue(':tournoiId', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();

        // Vérifier si une ligne a été affectée (mise à jour réussie)
        return $stmt->rowCount() > 0;
    }

    public function getEtatRencontre($rencontreId)
    {
        $query = "SELECT etat FROM Rencontres WHERE id = :rencontreId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':rencontreId', $rencontreId, PDO::PARAM_INT);
        $stmt->execute();

        // Récupérer la valeur de l'état de la rencontre
        $etat = $stmt->fetchColumn();

        // Vérifier si une valeur a été récupérée
        if ($etat !== false) {
            return $etat; // Retourner l'état de la rencontre
        } else {
            return null; // Retourner null si aucune valeur n'est trouvée
        }
    }

    public function getRencontreDetails($rencontreId)
    {
        // Récupérer les informations de base de la rencontre
        $query = "
            SELECT
    Rencontres.id AS rencontre_id,
    Rencontres.score1 AS score1,
    Rencontres.score2 AS score2,
    Rencontres.tour AS tour,
    Equipes1.nom AS equipe1_nom,
    Equipes1.categorie AS equipe1_categorie,
    Equipes2.nom AS equipe2_nom,
    Equipes2.categorie AS equipe2_categorie
FROM
    Rencontres
INNER JOIN
    Equipes AS Equipes1 ON Rencontres.equipe1_id = Equipes1.id
INNER JOIN
    Equipes AS Equipes2 ON Rencontres.equipe2_id = Equipes2.id
WHERE
    Rencontres.id = :rencontreId

        ";
    
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':rencontreId', $rencontreId, PDO::PARAM_INT);
        $stmt->execute();
    
        // Récupérer les détails de la rencontre
        $rencontreDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Vérifier si des détails ont été récupérés
        if ($rencontreDetails !== false) {
            return $rencontreDetails; // Retourner les détails de la rencontre
        } else {
            return null; // Retourner null si aucun détail n'est trouvé
        }
    }
    

    public function getRencontreByTournoi($tournoiId)
    {
        $query = "SELECT 
            r.id AS rencontre_id,
            r.isClassement AS isClassement,
            r.terrain AS num_terrain,
            r.tour AS tour,
            r.heure AS heure_rencontre,
            equipe1.id AS equipe1_id,
            equipe1.nom AS equipe1_nom,
            equipe1.categorie AS equipe1_categorie,
            (SELECT poule1.nom FROM EquipePoule ep1 JOIN Poules poule1 ON ep1.poule_id = poule1.id WHERE ep1.equipe_id = equipe1.id LIMIT 1) AS equipe1_poule_nom,
            equipe2.id AS equipe2_id,
            equipe2.nom AS equipe2_nom,
            equipe2.categorie AS equipe2_categorie,
            (SELECT poule2.nom FROM EquipePoule ep2 JOIN Poules poule2 ON ep2.poule_id = poule2.id WHERE ep2.equipe_id = equipe2.id LIMIT 1) AS equipe2_poule_nom,
            r.Arbitre AS arbitre,
            r.score1,
            r.score2
        FROM Rencontres r
        JOIN Equipes equipe1 ON r.equipe1_id = equipe1.id
        JOIN Equipes equipe2 ON r.equipe2_id = equipe2.id
        WHERE r.tournoi_id = :tournoiId
        ORDER BY CASE WHEN r.heure IS NULL THEN 1 ELSE 0 END, r.heure, equipe1.categorie
        
        ";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        $rencontres[] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Combiner les résultats pour chaque catégorie en un seul tableau
        $resultats = array_merge(...$rencontres);

        return $resultats;
    }




    // Méthode pour obtenir les équipes par catégorie et tournoi
    public function getEquipesByCategorieAndTournoi($categorie, $tournoiId)
    {
        $query = "SELECT * FROM Equipes WHERE categorie = :categorie AND tournoi_id = :tournoiId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':categorie', $categorie);
        $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getAllRencontresByTournoiId($tournoiId)
    {
        $query = "SELECT r.*, 
                      e1.nom AS equipe1_nom, e2.nom AS equipe2_nom,
                      e1.categorie AS equipe1_categorie, e2.categorie AS equipe2_categorie,
                      c1.logo AS equipe1_logo, c2.logo AS equipe2_logo, c1.id AS clubId1, c2.id AS clubId2,
                      e1.id AS equipeId1, e2.id AS equipeId2
                      FROM Rencontres r
                      LEFT JOIN Equipes e1 ON r.equipe1_id = e1.id
                      LEFT JOIN Equipes e2 ON r.equipe2_id = e2.id
                      LEFT JOIN Clubs c1 ON e1.club_id = c1.id
                      LEFT JOIN Clubs c2 ON e2.club_id = c2.id
                      WHERE r.tournoi_id = :tournoiId
                      ORDER BY r.heure";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();

        $rencontres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rencontres;
    }
}
