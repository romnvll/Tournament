<?php

class arbitreDao {
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

    public function ajouterArbitre(string $nom, int $tournoi_id, int $club_id): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Arbitres (nom, tournoi_id, club_id)
            VALUES (:nom, :tournoi_id, :club_id)");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->bindParam(':club_id', $club_id);
        $stmt->execute();
    }

    public function modifierArbitre(int $arbitre_id, string $nom = null, int $tournoi_id = null, int $club_id = null): void {
        $sql = "UPDATE Arbitres SET ";
        $params = [];
        if ($nom !== null) {
            $sql .= "nom = :nom, ";
            $params[':nom'] = $nom;
        }
        if ($tournoi_id !== null) {
            $sql .= "tournoi_id = :tournoi_id, ";
            $params[':tournoi_id'] = $tournoi_id;
        }
        if ($club_id !== null) {
            $sql .= "club_id = :club_id, ";
            $params[':club_id'] = $club_id;
        }
        $sql = rtrim($sql, ", ");
        $sql .= " WHERE arbitre_id = :arbitre_id";
        $params[':arbitre_id'] = $arbitre_id;

        $stmt = $this->connexion->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();
    }

    public function supprimerArbitre(int $arbitre_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Arbitres WHERE arbitre_id = :arbitre_id");
        $stmt->bindParam(':arbitre_id', $arbitre_id);
        $stmt->execute();
    }

    public function afficherArbitres(int $tournoi_id): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Arbitres WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

}
