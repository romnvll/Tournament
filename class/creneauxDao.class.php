<?php
class creneauxDao {
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

    public function ajouterCreneau(string $nom, int $tournoi_id): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Creneaux (nom, tournoi_id)
            VALUES (:nom, :tournoi_id)");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
    }

    public function mettreAJourHoraireDebut($tournoi_id, $nouvelle_heure_debut, $intervalle) {
        try {
            // Commencer une transaction
            $this->connexion->beginTransaction();

            // Récupérer tous les créneaux pour le tournoi spécifié
            $stmt = $this->connexion->prepare("SELECT * FROM Creneaux WHERE tournoi_id = :tournoi_id ORDER BY nom ASC");
            $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
            $stmt->execute();
            $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($creneaux)) {
                throw new Exception("Aucun créneau trouvé pour ce tournoi.");
            }

            // Mettre à jour le premier créneau avec la nouvelle heure de début
            $premier_creneau_id = $creneaux[0]['creneau_id'];
            $stmt = $this->connexion->prepare("UPDATE Creneaux SET nom = :nouvelle_heure_debut WHERE creneau_id = :creneau_id");
            $stmt->bindParam(':nouvelle_heure_debut', $nouvelle_heure_debut);
            $stmt->bindParam(':creneau_id', $premier_creneau_id, PDO::PARAM_INT);
            $stmt->execute();

            // Mettre à jour les autres créneaux en les décalant selon l'intervalle spécifié
            $current_time = new DateTime($nouvelle_heure_debut);
            for ($i = 1; $i < count($creneaux); $i++) {
                $current_time->add(new DateInterval('PT' . $intervalle . 'M'));
                $nouvelle_heure = $current_time->format('H:i');

                $stmt = $this->connexion->prepare("UPDATE Creneaux SET nom = :nouvelle_heure WHERE creneau_id = :creneau_id");
                $stmt->bindParam(':nouvelle_heure', $nouvelle_heure);
                $stmt->bindParam(':creneau_id', $creneaux[$i]['creneau_id'], PDO::PARAM_INT);
                $stmt->execute();
            }

            // Commit la transaction
            $this->connexion->commit();
        } catch (Exception $e) {
            // Rollback la transaction en cas d'erreur
            $this->connexion->rollBack();
            throw $e;
        }
    }

    

    public function mettreAJourCreneauxAvecMinutesAjoutees($tournoiId, $minutes) {
        // Initialiser la variable @time avec le premier créneau
        $sql = "
            SET @time = (SELECT nom FROM Creneaux WHERE tournoi_id = :tournoiId ORDER BY nom LIMIT 1);
            SET @rownum := 0;  -- Initialisation de la variable de ligne
        ";
    
        // Préparer la requête
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindParam(':tournoiId', $tournoiId, PDO::PARAM_INT);
    
        // Exécuter les commandes pour initialiser les variables
        $stmt->execute();
    
        // Requête pour mettre à jour les créneaux en ajoutant les minutes
        $sqlUpdate = "
            UPDATE Creneaux
            SET nom = (
                SELECT DATE_FORMAT(DATE_ADD(STR_TO_DATE(@time, '%H:%i'), INTERVAL (@rownum := @rownum + 1) * :minutes MINUTE), '%H:%i')
                FROM (SELECT @rownum := 0) AS init
                WHERE tournoi_id = :tournoiId
                ORDER BY nom
            )
            WHERE tournoi_id = :tournoiId
            ORDER BY nom;
        ";
    
        // Préparer la requête de mise à jour
        $stmtUpdate = $this->connexion->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':minutes', $minutes, PDO::PARAM_INT);
    
        // Exécuter la mise à jour
        if ($stmtUpdate->execute()) {
            return $stmtUpdate->rowCount(); // Retourne le nombre de lignes mises à jour
        } else {
            return false; // En cas d'échec
        }
    }
    
    
    
    
    


    public function modifierCreneau(int $creneau_id, string $nom = null, int $tournoi_id = null): void {
        $sql = "UPDATE Creneaux SET ";
        $params = [];
        if ($nom !== null) {
            $sql .= "nom = :nom, ";
            $params[':nom'] = $nom;
        }
        if ($tournoi_id !== null) {
            $sql .= "tournoi_id = :tournoi_id, ";
            $params[':tournoi_id'] = $tournoi_id;
        }
        $sql = rtrim($sql, ", ");
        $sql .= " WHERE creneau_id = :creneau_id";
        $params[':creneau_id'] = $creneau_id;

        $stmt = $this->connexion->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();
    }

    public function supprimerCreneau(int $creneau_id): void {
        // Commencer une transaction pour garantir l'intégrité
        $this->connexion->beginTransaction();
    
        try {
            // Supprimer d'abord les lignes dans Planification si les conditions sont remplies
            $stmtPlanification = $this->connexion->prepare("
                DELETE FROM Planification
                WHERE creneau_id = :creneau_id
                AND rencontre_id IS NULL
                AND label_id IS NULL
            ");
            $stmtPlanification->bindParam(':creneau_id', $creneau_id);
            $stmtPlanification->execute();
    
            // Vérifier si des lignes dans Planification sont encore liées au creneau_id
            $stmtCheck = $this->connexion->prepare("
                SELECT COUNT(*) FROM Planification WHERE creneau_id = :creneau_id
            ");
            $stmtCheck->bindParam(':creneau_id', $creneau_id);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();
    
            // Si aucune ligne restante, supprimer le créneau
            if ($count == 0) {
                $stmtCreneaux = $this->connexion->prepare("
                    DELETE FROM Creneaux WHERE creneau_id = :creneau_id
                ");
                $stmtCreneaux->bindParam(':creneau_id', $creneau_id);
                $stmtCreneaux->execute();
            }
    
            // Valider la transaction
            $this->connexion->commit();
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->connexion->rollBack();
            throw $e; // Relancer l'exception pour traitement
        }
    }
    

    public function supprimerCreneauxParTournoi(int $tournoi_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Creneaux WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    


    public function afficherCreneaux(int $tournoi_id): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Creneaux WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function existeCreneauPourTournoi($tournoi_id) {
        $sql = "SELECT COUNT(*) FROM Creneaux WHERE tournoi_id = :tournoi_id";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
        
        // Retourner true si le nombre de lignes est supérieur à 0, sinon false
        return $stmt->fetchColumn() > 0;
    }

    public function getLastCreneau($tournoi_id) {
        $sql = "SELECT nom FROM Creneaux WHERE 
        tournoi_id = :tournoi_id ORDER BY creneau_id DESC LIMIT 1";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();

       return  $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    }
    
}
