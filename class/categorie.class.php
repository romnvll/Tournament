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

    public function obtenirCategoriesDuTournoi(int $idTournoi): array {
    $stmt = $this->connexion->prepare("
        SELECT DISTINCT c.*
        FROM Equipes e
        INNER JOIN Categorie c ON e.categorie = c.id_categorie
        WHERE e.tournoi_id = :idTournoi AND e.IsPresent = 1
    ");
    $stmt->bindParam(':idTournoi', $idTournoi);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    

  public function obtenirToutesLesCategories(int $idclub, string $orderBy = 'Nom_categorie ASC'): array {
    $stmt = $this->connexion->prepare("
        SELECT * FROM Categorie
        WHERE fk_id_club = :idclub
        ORDER BY {$orderBy}
    ");
    $stmt->bindValue(':idclub', $idclub, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    /**
     * changement de la couleur d'une catégorie.
     * @param int $id
     * @param int $fk_id_club
     */

    public function changerCouleurCategorie(int $id, string $couleur, int $fk_id_club): void {
        $stmt = $this->connexion->prepare("
            UPDATE Categorie
            SET Couleur = :couleur
            WHERE id_categorie = :id AND fk_id_club = :fk_id_club
        ");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':couleur', $couleur);
        $stmt->bindParam(':fk_id_club', $fk_id_club);
        $stmt->execute();
    }
    /**
     * Met à jour une catégorie existante.
     */

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

    /**
 * Insère une nouvelle catégorie et renvoie l’ID créé.
 */
public function creerCategorie(string $nom, string $couleur, int $fk_id_club): int
{
    $stmt = $this->connexion->prepare("
        INSERT INTO Categorie (Nom_categorie, Couleur, fk_id_club)
        VALUES (:nom, :couleur, :fk_id_club)
    ");
    $stmt->bindParam(':nom',        $nom);
    $stmt->bindParam(':couleur',    $couleur);
    $stmt->bindParam(':fk_id_club', $fk_id_club, PDO::PARAM_INT);
    $stmt->execute();

    // Renvoie l'ID auto-incrementé pour d’éventuels traitements
    return (int) $this->connexion->lastInsertId();
}


   public function supprimerCategorie(int $id, int $fk_id_club): void
{
    $stmt = $this->connexion->prepare("
        DELETE FROM Categorie
        WHERE id_categorie = :id AND fk_id_club = :fk_id_club
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':fk_id_club', $fk_id_club, PDO::PARAM_INT);
    $stmt->execute();
}

}
?>
