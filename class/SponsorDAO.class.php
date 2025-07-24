<?php
class SponsorDAO {
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

    public function ajouterSponsor(string $nom, ?string $description, string $lien_web, ?string $logo, int $club_id): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Sponsors (nom, description, lien_web, logo, club_id)
            VALUES (:nom, :description, :lien_web, :logo, :club_id)
        ");

        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':lien_web', $lien_web);
        $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);

        // Description
        if ($description === null) {
            $stmt->bindValue(':description', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':description', $description);
        }

        // Logo
        if ($logo === null) {
            $stmt->bindValue(':logo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':logo', $logo);
        }

        $stmt->execute();
    }

    // Exemple de méthode pour récupérer les sponsors d'un club
    public function getSponsorsParClub(int $club_id): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Sponsors WHERE club_id = :club_id");
        $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


public function modifierSponsor(int $id, string $nom, ?string $description, string $lien_web, ?string $logo, int $club_id): void {
    $stmt = $this->connexion->prepare("
        UPDATE Sponsors
        SET nom = :nom,
            description = :description,
            lien_web = :lien_web,
            logo = :logo,
            club_id = :club_id
        WHERE id = :id
    ");

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':lien_web', $lien_web);
    $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);

    // Description
    if ($description === null) {
        $stmt->bindValue(':description', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindParam(':description', $description);
    }

    // Logo
    if ($logo === null) {
        $stmt->bindValue(':logo', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindParam(':logo', $logo);
    }

    $stmt->execute();
}


public function supprimerSponsor(int $id): void {
    $stmt = $this->connexion->prepare("DELETE FROM Sponsors WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}

public function getSponsorById(int $id): ?array {
    $stmt = $this->connexion->prepare("SELECT * FROM Sponsors WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}






}
?>
