<?php

class CategorieDao {
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
    public function obtenirCategorie(int $id): array {
        $stmt = $this->connexion->prepare("
            SELECT * FROM Categorie WHERE id_categorie = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenirToutesLesCategories(): array {
        $stmt = $this->connexion->prepare("
            SELECT * FROM Categorie ORDER BY Nom_categorie ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function mettreAJourCategorie(int $id, string $nom, string $couleur, int $fk_id_club): void {
        $stmt = $this->connexion->prepare("
            UPDATE Categorie
            SET Nom_categorie = :nom, Couleur = :couleur, fk_id_club = :fk_id_club
            WHERE id_categorie = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':couleur', $couleur);
        $stmt->bindParam(':fk_id_club', $fk_id_club);
        $stmt->execute();
    }

    public function supprimerCategorie(int $id): void {
        $stmt = $this->connexion->prepare("
            DELETE FROM Categorie WHERE id_categorie = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
}
?>
