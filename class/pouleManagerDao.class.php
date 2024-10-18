<?php
class PouleManager {
    private $connexion;

    public function __construct() {
        require 'databaseInformations.php';

        try {
            $this->connexion = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Erreur de connexion à la base de données : " . $e->getMessage();
            exit;
        }
    }

    public function createPoule($nomPoule, $idTournoi, $categorie, $is_classement = 0) {
        // Utiliser la méthode pouleExists pour vérifier si la poule existe déjà
        //if ($this->pouleExists($nomPoule, $idTournoi)) {
            // La poule existe déjà, donc on ne procède pas à l'insertion
         //   return false;
        //}
    
        // Insérer la nouvelle poule
        $query = "INSERT INTO Poules (nom, tournoi_id, fk_idcategorie, is_classement) VALUES (:nom, :idTournoi, :fk_idcategorie, :is_classement)";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':nom', $nomPoule);
        $stmt->bindValue(':idTournoi', $idTournoi);
        $stmt->bindValue(':fk_idcategorie', $categorie);
        $stmt->bindValue(':is_classement', $is_classement, PDO::PARAM_INT);
        $stmt->execute();
    
        return true;
    }
    
    
    

    public function getPouleById($idPoule) {
        $query = "SELECT * FROM Poules WHERE id = :id";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':id', $idPoule);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function checkRencontresInPoule($idPoule) {
        $query = "SELECT COUNT(*) as count FROM Rencontres r
                  JOIN EquipePoule ep ON r.equipe1_id = ep.equipe_id OR r.equipe2_id = ep.equipe_id
                  WHERE ep.poule_id = :id and r.isClassement = 0";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':id', $idPoule);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] / 2 > 0;
    }
    


    public function supprimerLienEquipePoule($equipeId, $pouleId) {
        $query = "DELETE FROM EquipePoule WHERE equipe_id = :equipeId AND poule_id = :pouleId";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':equipeId', $equipeId, PDO::PARAM_INT);
        $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            // Gérer l'exception ou la logger
            return false;
        }
    }


    public function deletePoule($idPoule) {
        try {
            // Commencer une transaction
            $this->connexion->beginTransaction();

            // Supprimer les liens dans EquipePoule
            $queryEquipePoule = "DELETE FROM EquipePoule WHERE poule_id = :idPoule";
            $stmtEquipePoule = $this->connexion->prepare($queryEquipePoule);
            $stmtEquipePoule->bindValue(':idPoule', $idPoule, PDO::PARAM_INT);
            $stmtEquipePoule->execute();

            // Supprimer la poule
            $queryPoule = "DELETE FROM Poules WHERE id = :idPoule";
            $stmtPoule = $this->connexion->prepare($queryPoule);
            $stmtPoule->bindValue(':idPoule', $idPoule, PDO::PARAM_INT);
            $stmtPoule->execute();

            // Valider la transaction
            $this->connexion->commit();

            return true;
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $this->connexion->rollBack();
            throw $e;
        }
    }


    public function getPoulesByEquipeId($equipeId) {
    
    // Requête pour obtenir les poules associées à une équipe spécifique
    $query = "SELECT Poules.* FROM Poules 
              JOIN EquipePoule ON Poules.id = EquipePoule.poule_id 
              JOIN Equipes ON EquipePoule.equipe_id = Equipes.id 
              WHERE Equipes.id = :equipeId";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':equipeId', $equipeId);
    $stmt->execute();

    $poules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$poules) {
        throw new Exception("Aucune poule trouvée pour l'équipe avec l'ID : " . $equipeId);
    }

    return $poules;
}

public function supprimerPoulesParTournoi(int $idTournoi): void {
    // Supprimer les relations dans EquipePoule liées aux poules du tournoi
    $queryDeleteEquipePoule = "DELETE FROM EquipePoule 
                                WHERE poule_id IN (SELECT id FROM Poules WHERE tournoi_id = :idTournoi)";
    
    $stmtDeleteEquipePoule = $this->connexion->prepare($queryDeleteEquipePoule);
    $stmtDeleteEquipePoule->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmtDeleteEquipePoule->execute();

    // Supprimer les poules du tournoi
    $queryDeletePoules = "DELETE FROM Poules WHERE tournoi_id = :idTournoi";
    
    $stmtDeletePoules = $this->connexion->prepare($queryDeletePoules);
    $stmtDeletePoules->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmtDeletePoules->execute();
}



public function getAllPoulesByTournoi($idTournoi, $AndIsClassement = false) {
    

    if ($AndIsClassement === true) {
        $query = "SELECT p.*, COUNT(ep.equipe_id) AS nombre_equipes
        FROM Poules p
        LEFT JOIN EquipePoule ep ON p.id = ep.poule_id
        WHERE p.tournoi_id = :idTournoi
        GROUP BY p.id
        ORDER BY p.nom";
    } else {
        
        $query = "SELECT p.*, COUNT(ep.equipe_id) AS nombre_equipes
        FROM Poules p
        LEFT JOIN EquipePoule ep ON p.id = ep.poule_id
        WHERE p.tournoi_id = :idTournoi AND p.is_classement = '0'
        GROUP BY p.id
        ORDER BY p.nom";
    }

    //$query .= " ORDER BY nom";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    public function addEquipeToPoule($equipeId, $pouleId, $tournoiId) {
      
    
        // Vérifier si l'équipe est déjà dans la poule
        $queryCheck = "SELECT * FROM EquipePoule WHERE equipe_id = :equipeId AND poule_id = :pouleId";
        $stmtCheck = $this->connexion->prepare($queryCheck);
        $stmtCheck->bindValue(':equipeId', $equipeId);
        $stmtCheck->bindValue(':pouleId', $pouleId);
        $stmtCheck->execute();
    
        if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("L'équipe est déjà dans cette poule.");
        }
    
        // Insérer l'équipe dans la poule
        $queryInsert = "INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:equipeId, :pouleId)";
        $stmtInsert = $this->connexion->prepare($queryInsert);
        $stmtInsert->bindValue(':equipeId', $equipeId);
        $stmtInsert->bindValue(':pouleId', $pouleId);
    
        if ($stmtInsert->execute()) {
            return true; // Retourne vrai si l'insertion est réussie
        } else {
            return false; // Retourne faux en cas d'échec
        }
    }
    
    
    

    public function getAllPoulesFinalesByTournoi($idTournoi) {
    $query = "
        SELECT p.*, c.Nom_categorie
        FROM Poules p
        INNER JOIN Categorie c ON p.fk_idcategorie = c.id_categorie
        WHERE p.tournoi_id = :idTournoi
        AND p.is_classement = '1'
        ORDER BY c.Nom_categorie, p.nom
    ";
    $stmt = $this->connexion->prepare($query);
    
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function getEquipesInPoule($idPoule) {
        $query = "SELECT
                    e.id,
                    e.nom,
                    e.categorie,
                    e.IsPresent,
                    e.tournoi_id,
                    ep.poule_id,
                    e.club_id,
                    ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id AND r.score1 > r.score2 and r.isClassement = 1) OR (r.equipe2_id = e.id AND r.score2 > r.score1 and r.isClassement = 1)) * 3) +
                    ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id OR r.equipe2_id = e.id) and r.isClassement = 1 AND r.score1 = r.score2 and r.isClassement = 1) * 2) +
                    ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id AND r.score1 < r.score2 and r.isClassement = 1) OR (r.equipe2_id = e.id AND r.score2 < r.score1 and r.isClassement = 1))) AS TotalDesPoints,
                    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id and r.isClassement = 1), 0) +
                    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id and r.isClassement = 1), 0) AS nombreButsMarque,
                    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id and r.isClassement = 1), 0) +
                    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id and r.isClassement = 1), 0) AS nombreButsEncaisse,
                    (COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id and r.isClassement = 1), 0) +
                    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id and r.isClassement = 1), 0)) -
                    (COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id and r.isClassement = 1), 0) +
                    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id and r.isClassement = 1), 0)) AS DifferenceButs
                  FROM
                    Equipes e
                  JOIN EquipePoule ep ON e.id = ep.equipe_id
                  WHERE
                    ep.poule_id = :pouleId
                   
                  ORDER BY
                    TotalDesPoints DESC,
                    nombreButsMarque DESC,
                    DifferenceButs DESC";
    
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':pouleId', $idPoule);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    public function creerPoulesPourCategorie(int $idTournoi, int $idCategorie, int $nombreEquipesParPoule)
{
    // Récupérer le nom de la catégorie
    $stmtNomCategorie = $this->connexion->prepare("
        SELECT Nom_categorie
        FROM Categorie
        WHERE id_categorie = :idCategorie
    ");
    $stmtNomCategorie->bindValue(':idCategorie', $idCategorie);
    $stmtNomCategorie->execute();
    $nomCategorie = $stmtNomCategorie->fetchColumn();

    if (!$nomCategorie) {
        return; // Pas de catégorie trouvée
    }

    // Étape 1 : Récupérer les équipes de la catégorie spécifique
    $stmtEquipes = $this->connexion->prepare("
        SELECT id, nom
        FROM Equipes
        WHERE tournoi_id = :idTournoi AND categorie = :idCategorie
    ");
    $stmtEquipes->bindValue(':idTournoi', $idTournoi);
    $stmtEquipes->bindValue(':idCategorie', $idCategorie);
    $stmtEquipes->execute();
    $equipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

    if (empty($equipes)) {
        return; // Pas d'équipes dans cette catégorie pour ce tournoi
    }

    // Étape 2 : Regrouper les équipes en poules selon le nombre d'équipes par poule
    $poules = [];
    $currentPoule = [];
    foreach ($equipes as $index => $equipe) {
        $currentPoule[] = $equipe;
        if (count($currentPoule) === $nombreEquipesParPoule) {
            $poules[] = $currentPoule;
            $currentPoule = [];
        }
    }

    // Ajouter la dernière poule si elle n'est pas vide
    if (!empty($currentPoule)) {
        $poules[] = $currentPoule;
    }

    // Variable pour stocker les IDs des nouvelles poules créées
    $newPouleIds = [];

    // Étape 3 : Insérer ou mettre à jour les poules et les équipes associées
    foreach ($poules as $index => $poule) {
        $nomPoule = "$nomCategorie - Poule " . ($index + 1);

        // Vérifier si la poule existe déjà
        $stmtPoule = $this->connexion->prepare("
            SELECT id FROM Poules WHERE nom = :nomPoule AND tournoi_id = :idTournoi
        ");
        $stmtPoule->bindValue(':nomPoule', $nomPoule);
        $stmtPoule->bindValue(':idTournoi', $idTournoi);
        $stmtPoule->execute();
        $pouleId = $stmtPoule->fetchColumn();

        if ($pouleId) {
            // Supprimer les équipes de la poule existante avant de les réassigner
            $this->connexion->prepare("DELETE FROM EquipePoule WHERE poule_id = :pouleId")
                ->execute([':pouleId' => $pouleId]);

            foreach ($poule as $equipe) {
                // Supprimer l'équipe de toute autre poule à laquelle elle pourrait appartenir
                $this->connexion->prepare("DELETE FROM EquipePoule WHERE equipe_id = :equipeId")
                    ->execute([':equipeId' => $equipe['id']]);

                // Ajouter l'équipe à la poule actuelle
                $stmtEquipePoule = $this->connexion->prepare("
                    INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:equipeId, :pouleId)
                ");
                $stmtEquipePoule->bindValue(':equipeId', $equipe['id']);
                $stmtEquipePoule->bindValue(':pouleId', $pouleId);
                $stmtEquipePoule->execute();
            }
        } else {
            // Créer une nouvelle poule
            $stmtInsertPoule = $this->connexion->prepare("
                INSERT INTO Poules (nom, is_classement, fk_idcategorie, tournoi_id) 
                VALUES (:nom, 0, :idCategorie, :idTournoi)
            ");
            $stmtInsertPoule->bindValue(':nom', $nomPoule);
            $stmtInsertPoule->bindValue(':idCategorie', $idCategorie);
            $stmtInsertPoule->bindValue(':idTournoi', $idTournoi);
            $stmtInsertPoule->execute();
            $newPouleId = $this->connexion->lastInsertId();

            // Ajouter l'ID de la nouvelle poule créée à la liste
            $newPouleIds[] = $newPouleId;

            foreach ($poule as $equipe) {
                // Supprimer l'équipe de toute autre poule à laquelle elle pourrait appartenir
                $this->connexion->prepare("DELETE FROM EquipePoule WHERE equipe_id = :equipeId")
                    ->execute([':equipeId' => $equipe['id']]);

                // Ajouter l'équipe à la nouvelle poule
                $stmtEquipePoule = $this->connexion->prepare("
                    INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:equipeId, :pouleId)
                ");
                $stmtEquipePoule->bindValue(':equipeId', $equipe['id']);
                $stmtEquipePoule->bindValue(':pouleId', $newPouleId);
                $stmtEquipePoule->execute();
            }
        }
    }

    // Retourner les IDs des nouvelles poules créées
    return $newPouleIds;
}




    public function afficherPoulesPourCategorie(int $idTournoi, int $nombreEquipesParPoule, int $idCategorie): array {
        // Étape 1 : Récupérer toutes les équipes de la catégorie et du tournoi
        $stmt = $this->connexion->prepare("
            SELECT id, nom 
            FROM Equipes 
            WHERE tournoi_id = :idTournoi 
            AND categorie = :idCategorie
        ");
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':idCategorie', $idCategorie, PDO::PARAM_INT);
        $stmt->execute();
        $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Étape 2 : Regrouper les équipes par poules
        $totalEquipes = count($equipes);
        $nombrePoules = ceil($totalEquipes / $nombreEquipesParPoule);
        
        // Stocker les poules et leurs équipes
        $poules = [];
        
        for ($i = 0; $i < $nombrePoules; $i++) {
            $nomPoule = "Poule " . ($i + 1);
    
            // Vérifier si la poule existe déjà
            $stmtCheck = $this->connexion->prepare("
                SELECT COUNT(*) 
                FROM Poules 
                WHERE nom = :nomPoule 
                AND tournoi_id = :idTournoi
            ");
            $stmtCheck->bindValue(':nomPoule', $nomPoule, PDO::PARAM_STR);
            $stmtCheck->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
            $stmtCheck->execute();
            $exists = $stmtCheck->fetchColumn();
    
            // Ajouter la poule et ses équipes à la liste
            if ($exists == 0) {
                $poule = ['nom' => $nomPoule, 'equipes' => []];
    
                // Assigner les équipes à la poule
                for ($j = 0; $j < $nombreEquipesParPoule; $j++) {
                    $indexEquipe = $i * $nombreEquipesParPoule + $j;
                    if ($indexEquipe >= $totalEquipes) {
                        break;
                    }
                    $equipe = $equipes[$indexEquipe];
                    $poule['equipes'][] = $equipe;
                }
                
                $poules[] = $poule;
            }
        }
    
        // Retourner le tableau des poules
        return $poules;
    }
    
    
    
public function pouleExists(string $pouleNom, int $idTournoi): bool {
    
    $stmt = $this->connexion->prepare("SELECT COUNT(*) FROM Poules WHERE nom = :pouleNom AND tournoi_id = :idTournoi");
    $stmt->bindValue(':pouleNom', $pouleNom);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

public function compterEquipesParPoule($poule_id) {
    
    // Utilisez une requête SQL pour compter le nombre d'équipes dans la poule donnée
    $query = "SELECT COUNT(*) AS nombre_equipes FROM EquipePoule WHERE poule_id = :poule_id";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindParam(':poule_id', $poule_id, PDO::PARAM_INT);
    $stmt->execute();

    // Récupérez le résultat de la requête et retournez le nombre d'équipes
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int) $result['nombre_equipes'];
}

public function calculerNombreTours($nombre_equipes) {
    // Utilisez log2 pour calculer le nombre de tours
    return ceil(log($nombre_equipes, 2));
}

public function getInfoPoule($poule_id) {
    // Utilisez une requête SQL pour compter le nombre d'équipes dans la poule donnée
    $queryEquipes = "SELECT COUNT(*) AS nombre_equipes FROM EquipePoule WHERE poule_id = :poule_id";
    $stmtEquipes = $this->connexion->prepare($queryEquipes);
    $stmtEquipes->bindParam(':poule_id', $poule_id, PDO::PARAM_INT);
    $stmtEquipes->execute();

    // Récupérez le résultat de la requête pour le nombre d'équipes
    $resultEquipes = $stmtEquipes->fetch(PDO::FETCH_ASSOC);
    $nombre_equipes = (int) $resultEquipes['nombre_equipes'];

    // Utilisez une requête SQL pour compter le nombre de rencontres dans la poule donnée
    $queryRencontres = "SELECT COUNT(*) AS nombre_rencontres FROM Rencontres 
                        WHERE equipe1_id IN (SELECT equipe_id FROM EquipePoule WHERE poule_id = :poule_id) 
                        AND equipe2_id IN (SELECT equipe_id FROM EquipePoule WHERE poule_id = :poule_id)";
    $stmtRencontres = $this->connexion->prepare($queryRencontres);
    $stmtRencontres->bindParam(':poule_id', $poule_id, PDO::PARAM_INT);
    $stmtRencontres->execute();

    // Récupérez le résultat de la requête pour le nombre de rencontres
    $resultRencontres = $stmtRencontres->fetch(PDO::FETCH_ASSOC);
    $nombre_rencontres = (int) $resultRencontres['nombre_rencontres'];

    // Calculer le nombre de tours nécessaires
    $nombre_tours = $this->calculerNombreTours($nombre_equipes);

    // Déterminer le type de rencontre
    $type_rencontre = ($nombre_rencontres == $nombre_equipes * ($nombre_equipes - 1) / 2) ? "Aller simple" : "Aller-retour";

    // Calculer le nombre de rencontres par tour
    if ($type_rencontre === "Aller-retour") {
        $nombre_rencontres_par_tour = ceil($nombre_equipes / 2);
    } else {
        // Pour les matchs aller simple, le nombre de rencontres par tour est le nombre d'équipes divisé par 2
        $nombre_rencontres_par_tour = ceil($nombre_equipes / 2);
    }

    // Retournez un tableau associatif contenant le nombre d'équipes, le nombre de rencontres, le nombre de tours, le nombre de rencontres par tour et le type de rencontre
    return array(
        'nombre_equipes' => $nombre_equipes,
        'nombre_rencontres' => $nombre_rencontres,
        'nombre_tours' => $nombre_tours,
        'nombre_rencontres_par_tour' => $nombre_rencontres_par_tour,
        'type_rencontre' => $type_rencontre
    );
}










public function getIdFromNom(string $nom): int {
    $query = "SELECT id FROM Poules WHERE nom = :nom";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':nom', $nom);
    $stmt->execute();

    // Utilisation de fetchColumn() pour obtenir directement la valeur de l'ID en tant qu'entier
    $id = $stmt->fetchColumn();

    // Assurez-vous de renvoyer l'ID sous forme d'entier
    return (int)$id;
}

    // Ajoutez ici d'autres méthodes pour gérer les équipes, les rencontres, etc.
}


?>