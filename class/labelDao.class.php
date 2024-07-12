<?php
class labelDao {
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

    public function ajouterLabel(string $description, int $tournoi_id): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Labels (description, tournoi_id)
            VALUES (:description, :tournoi_id)");
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
    }

    public function modifierLabel(int $label_id, string $description = null, int $tournoi_id = null): void {
        $sql = "UPDATE Labels SET ";
        $params = [];
        if ($description !== null) {
            $sql .= "description = :description, ";
            $params[':description'] = $description;
        }
        if ($tournoi_id !== null) {
            $sql .= "tournoi_id = :tournoi_id, ";
            $params[':tournoi_id'] = $tournoi_id;
        }
        $sql = rtrim($sql, ", ");
        $sql .= " WHERE label_id = :label_id";
        $params[':label_id'] = $label_id;

        $stmt = $this->connexion->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();
    }

    public function supprimerLabel(int $label_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Labels WHERE label_id = :label_id");
        $stmt->bindParam(':label_id', $label_id);
        $stmt->execute();
    }

    public function afficherLabels(int $tournoi_id): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Labels WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

}
