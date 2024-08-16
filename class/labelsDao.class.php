<?php
class LabelDao {
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

    // Méthode pour ajouter un label
    public function ajouterLabel(string $description, string $couleur, int $tournoi_id): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Labels (description, couleur, tournoi_id) 
            VALUES (:description, :couleur, :tournoi_id)
        ");
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':couleur', $couleur);
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Méthode pour récupérer un label par son ID
    public function getLabelById(int $label_id) {
        $stmt = $this->connexion->prepare("SELECT * FROM Labels WHERE label_id = :label_id");
        $stmt->bindParam(':label_id', $label_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Méthode pour récupérer tous les labels d'un tournoi
    public function getLabelsByTournoiId(int $tournoi_id) {
        $stmt = $this->connexion->prepare("SELECT * FROM Labels WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Méthode pour mettre à jour un label
    public function updateLabel(int $label_id, string $description, string $couleur): void {
        $stmt = $this->connexion->prepare("
            UPDATE Labels 
            SET description = :description, couleur = :couleur 
            WHERE label_id = :label_id
        ");
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':couleur', $couleur);
        $stmt->bindParam(':label_id', $label_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Méthode pour supprimer un label
    public function supprimerLabel(int $label_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Labels WHERE label_id = :label_id");
        $stmt->bindParam(':label_id', $label_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Méthode pour supprimer tous les labels d'un tournoi
    public function supprimerLabelsParTournoi(int $tournoi_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Labels WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
