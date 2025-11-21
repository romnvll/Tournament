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
        WHERE e.tournoi_id = :idTournoi 
    ");
    $stmt->bindParam(':idTournoi', $idTournoi);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    

  public function obtenirToutesLesCategories(int $idUser, string $orderBy = 'Nom_categorie ASC'): array {
    // Liste blanche des colonnes autorisées pour le tri
    $allowedOrder = ['Nom_categorie ASC', 'Nom_categorie DESC', 'id_categorie ASC', 'id_categorie DESC'];
    
    // Vérification sécurisée du champ de tri
    if (!in_array($orderBy, $allowedOrder, true)) {
        $orderBy = 'Nom_categorie ASC'; // fallback par défaut
    }

    $stmt = $this->connexion->prepare("
        SELECT * FROM Categorie
        WHERE utilisateur_id = :idUser
        ORDER BY {$orderBy}
    ");
    $stmt->bindValue(':idUser', $idUser, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function existePlanificationPourCategorie(int $idCategorie): bool
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM Planification p
        INNER JOIN Rencontres r ON p.rencontre_id = r.id
        INNER JOIN Equipes e1 ON r.equipe1_id = e1.id
        LEFT JOIN Equipes e2 ON r.equipe2_id = e2.id
        WHERE e1.categorie = :idCategorie OR e2.categorie = :idCategorie
    ";

    $stmt = $this->connexion->prepare($sql);
    $stmt->bindValue(':idCategorie', $idCategorie, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['nb'] > 0;
}




    /**
     * changement de la couleur d'une catégorie.
     * @param int $id
     * @param int $fk_id_club
     */

    public function changerCouleurCategorie(int $id, string $couleur, int $utilisateurId): void {
        $stmt = $this->connexion->prepare("
            UPDATE Categorie
            SET Couleur = :couleur
            WHERE id_categorie = :id AND utilisateur_id = :utilisateurId
        ");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':couleur', $couleur);
        $stmt->bindParam(':utilisateurId', $utilisateurId);
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
public function creerCategorie(string $nom, string $couleur, int $fk_id_user): int
{
    $stmt = $this->connexion->prepare("
        INSERT INTO Categorie (Nom_categorie, Couleur, utilisateur_id)
        VALUES (:nom, :couleur, :utilisateur_id)
    ");
    $stmt->bindParam(':nom',        $nom);
    $stmt->bindParam(':couleur',    $couleur);
    $stmt->bindParam(':utilisateur_id', $fk_id_user, PDO::PARAM_INT);
    $stmt->execute();

    // Renvoie l'ID auto-incrementé pour d’éventuels traitements
    return (int) $this->connexion->lastInsertId();
}


   public function supprimerCategorie(int $id, int $utilisateur_id): void
{
    $stmt = $this->connexion->prepare("
        DELETE FROM Categorie
        WHERE id_categorie = :id AND utilisateur_id = :utilisateur_id
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();
}


}
?>
