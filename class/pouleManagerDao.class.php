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
        if ($this->pouleExists($nomPoule, $idTournoi)) {
            // La poule existe déjà, donc on ne procède pas à l'insertion
            return false;
        }
    
        // Insérer la nouvelle poule
        $query = "INSERT INTO Poules (nom, tournoi_id, categorie, is_classement) VALUES (:nom, :idTournoi, :categorie, :is_classement)";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':nom', $nomPoule);
        $stmt->bindValue(':idTournoi', $idTournoi);
        $stmt->bindValue(':categorie', $categorie);
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
        $query = "SELECT * FROM Poules WHERE tournoi_id = :idTournoi";
    } else {
        
        $query = "SELECT * FROM Poules WHERE tournoi_id = :idTournoi AND is_classement = '0'";
    }

    $query .= " ORDER BY nom";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    public function addEquipeToPoule($equipeId, $pouleNom, $tournoiId) {
         // Trouver l'ID de la poule en fonction de son nom
        $queryPoule = "SELECT id FROM Poules WHERE nom = :pouleNom and tournoi_id = :tournoiId";
        $stmtPoule = $this->connexion->prepare($queryPoule);
        $stmtPoule->bindValue(':pouleNom',  $pouleNom );
        $stmtPoule->bindValue(':tournoiId', $tournoiId);
        
        $stmtPoule->execute();
        $poule = $stmtPoule->fetch(PDO::FETCH_ASSOC);
       
        // Vérifier si la poule existe
        if (!$poule) {
            throw new Exception("Poule non trouvée avec le nom : " . $pouleNom);
        }
    
        $pouleId = $poule['id'];
    
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
        $query = "SELECT * FROM Poules WHERE tournoi_id = :idTournoi and is_classement = '1' order by nom";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi);
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
    
    public function creerPoules($tournoi_id,$nb_equipes_par_poule) {

        $stmt = $this->connexion->prepare("SELECT DISTINCT categorie FROM Equipes WHERE tournoi_id = ?");
        $stmt->execute(array($tournoi_id));
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
        // Créer les poules pour chaque catégorie
        $poules_par_categorie = array();
        foreach ($categories as $categorie) {
            // Préparer la requête pour récupérer les équipes de la catégorie spécifiée
            $stmt = $this->connexion->prepare("SELECT * FROM Equipes WHERE tournoi_id = ? AND categorie = ?");
            $stmt->execute(array($tournoi_id, $categorie));
            $equipes = $stmt->fetchAll();
    
            // Vérifier qu'il y a au moins 2 équipes dans la catégorie
            if (count($equipes) < 2) {
                continue;
            }
    
           
    
            // Créer les poules pour la catégorie
            $poules = array();
            $poule_id = 1;
            foreach (array_chunk($equipes, $nb_equipes_par_poule) as $poule) {
                // Affecter les équipes à la poule
                $poules[$poule_id] = array();
                foreach ($poule as $equipe) {
                    // Affecter l'équipe à la poule
                    $poules[$poule_id][] = $equipe;
                }
                $poule_id++;
            }
    
           
    
            // Ajouter les poules de la catégorie au tableau des poules par catégorie
            $poules_par_categorie[$categorie] = $poules;
        }
    
        return $poules_par_categorie;
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