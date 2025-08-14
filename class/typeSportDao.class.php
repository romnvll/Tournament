<?php

class TypeSportDAO {
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

    // Ajouter un type de sport
    public function ajouterTypeSport(string $nom): void {
        $stmt = $this->connexion->prepare("INSERT INTO TypeDeSport (nom) VALUES (:nom)");
        $stmt->bindParam(':nom', $nom);
        $stmt->execute();
    }

    // Modifier un type de sport
    public function modifierTypeSport(int $id, string $nom): bool {
        $stmt = $this->connexion->prepare("UPDATE TypeDeSport SET nom = :nom WHERE id = :id");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Supprimer un type de sport
    public function supprimerTypeSport(int $id): void {
        $stmt = $this->connexion->prepare("DELETE FROM TypeDeSport WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    // Récupérer un type de sport par son id
    public function getTypeSportById(int $id): ?array {
        $stmt = $this->connexion->prepare("SELECT * FROM TypeDeSport WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $typeSport = $stmt->fetch(PDO::FETCH_ASSOC);
        return $typeSport === false ? null : $typeSport;
    }

    // Récupérer tous les types de sport
    public function getTousLesTypesDeSport(): array {
        $stmt = $this->connexion->prepare("SELECT * FROM TypeDeSport ORDER BY nom ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
