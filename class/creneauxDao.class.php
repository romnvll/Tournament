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
