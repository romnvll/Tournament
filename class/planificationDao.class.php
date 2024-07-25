<?php
class planificationDao {
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

    public function ajouterOuModifierPlanification(int $terrain_id, int $creneau_id, ?int $rencontre_id, int $tournoi_id, ?int $arbitre_id = null, ?int $label_id = null): void {
        // Vérifier si la planification avec ce couple terrain_id et creneau_id existe déjà
        $checkStmt = $this->connexion->prepare("SELECT COUNT(*) FROM Planification WHERE terrain_id = :terrain_id AND creneau_id = :creneau_id");
        $checkStmt->bindParam(':terrain_id', $terrain_id);
        $checkStmt->bindParam(':creneau_id', $creneau_id);
        $checkStmt->execute();
        $exists = $checkStmt->fetchColumn() > 0;
    
        if ($exists) {
            // Construction de la requête SQL dynamique pour la mise à jour
            $query = "UPDATE Planification SET tournoi_id = :tournoi_id";
            if ($rencontre_id !== null) {
                $query .= ", rencontre_id = :rencontre_id";
            }
            if ($arbitre_id !== null) {
                $query .= ", arbitre_id = :arbitre_id";
            }
            if ($label_id !== null) {
                $query .= ", label_id = :label_id";
            }
            $query .= " WHERE terrain_id = :terrain_id AND creneau_id = :creneau_id";
    
            $updateStmt = $this->connexion->prepare($query);
            $updateStmt->bindParam(':terrain_id', $terrain_id);
            $updateStmt->bindParam(':creneau_id', $creneau_id);
            $updateStmt->bindParam(':tournoi_id', $tournoi_id);
            if ($rencontre_id !== null) {
                $updateStmt->bindParam(':rencontre_id', $rencontre_id);
            }
            if ($arbitre_id !== null) {
                $updateStmt->bindParam(':arbitre_id', $arbitre_id);
            }
            if ($label_id !== null) {
                $updateStmt->bindParam(':label_id', $label_id);
            }
    
            $updateStmt->execute();
        } else {
            // Insérer une nouvelle planification
            $insertStmt = $this->connexion->prepare("
                INSERT INTO Planification (terrain_id, creneau_id, rencontre_id, tournoi_id, arbitre_id, label_id)
                VALUES (:terrain_id, :creneau_id, :rencontre_id, :tournoi_id, :arbitre_id, :label_id)
            ");
            $insertStmt->bindParam(':terrain_id', $terrain_id);
            $insertStmt->bindParam(':creneau_id', $creneau_id);
            $insertStmt->bindParam(':tournoi_id', $tournoi_id);
            if ($rencontre_id !== null) {
                $insertStmt->bindParam(':rencontre_id', $rencontre_id);
            } else {
                $insertStmt->bindValue(':rencontre_id', null, PDO::PARAM_NULL);
            }
            if ($arbitre_id !== null) {
                $insertStmt->bindParam(':arbitre_id', $arbitre_id);
            } else {
                $insertStmt->bindValue(':arbitre_id', null, PDO::PARAM_NULL);
            }
            if ($label_id !== null) {
                $insertStmt->bindParam(':label_id', $label_id);
            } else {
                $insertStmt->bindValue(':label_id', null, PDO::PARAM_NULL);
            }
    
            $insertStmt->execute();
        }
    
        echo "ok";
    }
    
    
    
    

    public function modifierPlanification(int $planification_id, int $terrain_id = null, int $creneau_id = null, int $rencontre_id = null, int $tournoi_id = null, int $arbitre_id = null, int $label_id = null): void {
        $sql = "UPDATE Planification SET ";
        $params = [];
        if ($terrain_id !== null) {
            $sql .= "terrain_id = :terrain_id, ";
            $params[':terrain_id'] = $terrain_id;
        }
        if ($creneau_id !== null) {
            $sql .= "creneau_id = :creneau_id, ";
            $params[':creneau_id'] = $creneau_id;
        }
        if ($rencontre_id !== null) {
            $sql .= "rencontre_id = :rencontre_id, ";
            $params[':rencontre_id'] = $rencontre_id;
        }
        if ($tournoi_id !== null) {
            $sql .= "tournoi_id = :tournoi_id, ";
            $params[':tournoi_id'] = $tournoi_id;
        }
        if ($arbitre_id !== null) {
            $sql .= "arbitre_id = :arbitre_id, ";
            $params[':arbitre_id'] = $arbitre_id;
        }
        if ($label_id !== null) {
            $sql .= "label_id = :label_id, ";
            $params[':label_id'] = $label_id;
        }
        $sql = rtrim($sql, ", ");
        $sql .= " WHERE planification_id = :planification_id";
        $params[':planification_id'] = $planification_id;

        $stmt = $this->connexion->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();
    }

    public function supprimerPlanification(int $planification_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Planification WHERE planification_id = :planification_id");
        $stmt->bindParam(':planification_id', $planification_id);
        $stmt->execute();
    }

    public function resetTerrainAndCreneau(int $tournoi_id, int $planification_id): void {
        $stmt = $this->connexion->prepare("
            UPDATE Planification 
            SET terrain_id = NULL, creneau_id = NULL ,  label_id = NULL
            WHERE tournoi_id = :tournoi_id AND planification_id = :planification_id
        ");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->bindParam(':planification_id', $planification_id);
        $stmt->execute();
    }
    
    

    public function afficherPlanifications(int $tournoi_id): array {
        $stmt = $this->connexion->prepare("
            SELECT 
                p.planification_id,
                p.terrain_id,
                t.nom AS terrain_nom,
                p.creneau_id,
                c.nom AS creneau_nom,
                p.rencontre_id,
                r.isClassement,
                r.equipe1_id,
                e1.nom AS equipe1_nom,
                e1.categorie AS equipe1_categorie,
                e1.IsPresent AS equipe1_isPresent,
                e1.tournoi_id AS equipe1_tournoi_id,
                e1.club_id AS equipe1_club_id,
                cat1.id_categorie AS equipe1_categorie_id,
                cat1.Nom_categorie AS equipe1_categorie_nom,
                cat1.Couleur AS equipe1_categorie_couleur,
                cat1.fk_id_club AS equipe1_categorie_fk_id_club,
                r.equipe2_id,
                e2.nom AS equipe2_nom,
                e2.categorie AS equipe2_categorie,
                e2.IsPresent AS equipe2_isPresent,
                e2.tournoi_id AS equipe2_tournoi_id,
                e2.club_id AS equipe2_club_id,
                cat2.id_categorie AS equipe2_categorie_id,
                cat2.Nom_categorie AS equipe2_categorie_nom,
                cat2.Couleur AS equipe2_categorie_couleur,
                cat2.fk_id_club AS equipe2_categorie_fk_id_club,
                r.score1,
                r.score2,
                r.tour,
                r.heure,
                r.terrain AS rencontre_terrain,
                r.Arbitre AS rencontre_arbitre,
                r.tournoi_id AS rencontre_tournoi_id,
                p.tournoi_id,
                p.arbitre_id,
                a.nom AS arbitre_nom,
                p.label_id,
                l.description AS label_description
            FROM 
                Planification p
            LEFT JOIN 
                Terrains t ON p.terrain_id = t.terrain_id
            LEFT JOIN 
                Creneaux c ON p.creneau_id = c.creneau_id
            LEFT JOIN 
                Rencontres r ON p.rencontre_id = r.id
            LEFT JOIN 
                Equipes e1 ON r.equipe1_id = e1.id
            LEFT JOIN 
                Categorie cat1 ON e1.categorie = cat1.id_categorie
            LEFT JOIN 
                Equipes e2 ON r.equipe2_id = e2.id
            LEFT JOIN 
                Categorie cat2 ON e2.categorie = cat2.id_categorie
            LEFT JOIN 
                Arbitres a ON p.arbitre_id = a.arbitre_id
            LEFT JOIN 
                Labels l ON p.label_id = l.label_id
            WHERE 
                p.tournoi_id = :tournoi_id
                AND p.terrain_id IS NOT NULL
                AND p.creneau_id IS NOT NULL
        ");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function afficherRencontresSansPlanification(int $tournoi_id): array {
        $stmt = $this->connexion->prepare("
           SELECT 
    r.id,
    r.isClassement,
    r.equipe1_id,
    r.id AS idRencontre,
    e1.nom AS equipe1_nom,
    e1.categorie AS equipe1_categorie,
    c1.Nom_categorie AS equipe1_nom_categorie,
    e1.IsPresent AS equipe1_isPresent,
    e1.tournoi_id AS equipe1_tournoi_id,
    e1.club_id AS equipe1_club_id,
    r.equipe2_id,
    e2.nom AS equipe2_nom,
    e2.categorie AS equipe2_categorie,
    c2.Nom_categorie AS equipe2_nom_categorie,
    e2.IsPresent AS equipe2_isPresent,
    e2.tournoi_id AS equipe2_tournoi_id,
    e2.club_id AS equipe2_club_id,
    r.score1,
    r.score2,
    r.tour,
    r.heure,
    r.terrain AS rencontre_terrain,
    r.Arbitre AS rencontre_arbitre,
    r.tournoi_id AS rencontre_tournoi_id
FROM 
    Rencontres r
LEFT JOIN 
    Planification p ON r.id = p.rencontre_id
LEFT JOIN 
    Equipes e1 ON r.equipe1_id = e1.id
LEFT JOIN 
    Categorie c1 ON e1.categorie = c1.id_categorie
LEFT JOIN 
    Equipes e2 ON r.equipe2_id = e2.id
LEFT JOIN 
    Categorie c2 ON e2.categorie = c2.id_categorie
WHERE 
    r.tournoi_id = :tournoi_id
    AND p.rencontre_id IS NULL

        ");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    

public function listerLabelsParTournoi(int $tournoi_id): array {
    $stmt = $this->connexion->prepare("
        SELECT 
            l.label_id,
            l.description,
            l.tournoi_id
        FROM 
            Labels l
        WHERE 
            l.tournoi_id = :tournoi_id
    ");
    $stmt->bindParam(':tournoi_id', $tournoi_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function retireArbitre(int $tournoi_id, int $planification_id): void {
    try {
        $stmt = $this->connexion->prepare("
            UPDATE Planification 
            SET arbitre_id = NULL  
            WHERE tournoi_id = :tournoi_id AND planification_id = :planification_id
        ");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->bindParam(':planification_id', $planification_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "Arbitre retiré avec succès.";
        } else {
            echo "Aucune planification trouvée pour les critères spécifiés.";
        }
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}

public function getPlanificationsTerrainAvecDetails($terrainId, $tournoiId) {
    $sql = "
        SELECT 
            p.*, 
            t.nom AS terrain_nom, 
            l.description AS label_description, 
            a.nom AS arbitre_nom,
            r.isClassement, r.equipe1_id, r.equipe2_id, r.score1, r.score2, r.tour, r.heure, r.terrain,
            e1.nom AS equipe1_nom, e2.nom AS equipe2_nom,
            c.nom AS creneau_nom,
            c1.nom AS club1_nom, c1.email AS club1_email, c1.contact AS club1_contact, c1.logo AS club1_logo,
            c2.nom AS club2_nom, c2.email AS club2_email, c2.contact AS club2_contact, c2.logo AS club2_logo,
            CASE 
                WHEN STR_TO_DATE(c.nom, '%H:%i') < STR_TO_DATE('06:00', '%H:%i') 
                THEN DATE_ADD(STR_TO_DATE(c.nom, '%H:%i'), INTERVAL 1 DAY) 
                ELSE STR_TO_DATE(c.nom, '%H:%i') 
            END AS sorted_creneau
        FROM 
            Planification p
        LEFT JOIN 
            Terrains t ON p.terrain_id = t.terrain_id
        LEFT JOIN 
            Labels l ON p.label_id = l.label_id
        LEFT JOIN 
            Arbitres a ON p.arbitre_id = a.arbitre_id
        LEFT JOIN 
            Rencontres r ON p.rencontre_id = r.id
        LEFT JOIN 
            Equipes e1 ON r.equipe1_id = e1.id
        LEFT JOIN 
            Equipes e2 ON r.equipe2_id = e2.id
        LEFT JOIN 
            Creneaux c ON p.creneau_id = c.creneau_id
        LEFT JOIN 
            Clubs c1 ON e1.club_id = c1.id
        LEFT JOIN 
            Clubs c2 ON e2.club_id = c2.id
        WHERE 
            p.terrain_id = :terrainId AND p.tournoi_id = :tournoiId
        ORDER BY 
            sorted_creneau
    ";

    $stmt = $this->connexion->prepare($sql);
    $stmt->bindParam(':terrainId', $terrainId, PDO::PARAM_INT);
    $stmt->bindParam(':tournoiId', $tournoiId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}





public function retireRencontre(int $tournoi_id, int $planification_id): void {
    try {
        $stmt = $this->connexion->prepare("
            UPDATE Planification 
            SET rencontre_id = NULL  
            WHERE tournoi_id = :tournoi_id AND planification_id = :planification_id
        ");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->bindParam(':planification_id', $planification_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "rencontre retiré avec succès.";
        } else {
            echo "Aucune planification trouvée pour les critères spécifiés.";
        }
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}

    
    
    



    
}



?>