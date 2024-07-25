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
        $sql = "
            UPDATE Creneaux
            SET nom = DATE_FORMAT(DATE_ADD(STR_TO_DATE(nom, '%H:%i'), INTERVAL :minutes MINUTE), '%H:%i')
            WHERE tournoi_id = :tournoiId
        ";
        
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindParam(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->bindParam(':minutes', $minutes, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $stmt->rowCount(); // Retourne le nombre de lignes mises à jour
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
        $stmt = $this->connexion->prepare("DELETE FROM Creneaux WHERE creneau_id = :creneau_id");
        $stmt->bindParam(':creneau_id', $creneau_id);
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
