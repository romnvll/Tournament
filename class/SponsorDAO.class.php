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

    public function ajouterSponsor(
    string $nom,
    ?string $description,
    string $lien_web,
    ?string $logo,
    int $club_id,
    ?string $telephone = null,
    ?string $adresse = null
): void {
    // Construction dynamique des colonnes et des placeholders
    $colonnes = ['nom', 'description', 'lien_web', 'logo', 'club_id'];
    $placeholders = [':nom', ':description', ':lien_web', ':logo', ':club_id'];

    if ($telephone !== null) {
        $colonnes[] = 'telephone';
        $placeholders[] = ':telephone';
    }

    if ($adresse !== null) {
        $colonnes[] = 'adresse';
        $placeholders[] = ':adresse';
    }

    $sql = "
        INSERT INTO Sponsors (" . implode(', ', $colonnes) . ")
        VALUES (" . implode(', ', $placeholders) . ")
    ";

    $stmt = $this->connexion->prepare($sql);

    // Champs obligatoires
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':lien_web', $lien_web);
    $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);

    // Champs optionnels avec gestion de NULL
    $description === null
        ? $stmt->bindValue(':description', null, PDO::PARAM_NULL)
        : $stmt->bindParam(':description', $description);

    $logo === null
        ? $stmt->bindValue(':logo', null, PDO::PARAM_NULL)
        : $stmt->bindParam(':logo', $logo);

    if ($telephone !== null) {
        $stmt->bindParam(':telephone', $telephone);
    }

    if ($adresse !== null) {
        $stmt->bindParam(':adresse', $adresse);
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

     public function getSponsorsActifParClub(int $club_id): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Sponsors WHERE club_id = :club_id and is_actif = '1'");
        $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


public function modifierSponsor(
    int $id,
    string $nom,
    ?string $description,
    string $lien_web,
    ?string $logo,
    int $club_id,
    ?string $telephone = null,
    ?string $adresse = null
): void {
    $sql = "
        UPDATE Sponsors
        SET nom = :nom,
            description = :description,
            lien_web = :lien_web,
            logo = :logo,
            club_id = :club_id";

    // Ajout dynamique des champs optionnels
    if ($telephone !== null) {
        $sql .= ", telephone = :telephone";
    }

    if ($adresse !== null) {
        $sql .= ", adresse = :adresse";
    }

    $sql .= " WHERE id = :id";

    $stmt = $this->connexion->prepare($sql);

    // Champs obligatoires
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':lien_web', $lien_web);
    $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);

    // Champs optionnels avec gestion de NULL
    $description === null
        ? $stmt->bindValue(':description', null, PDO::PARAM_NULL)
        : $stmt->bindParam(':description', $description);

    $logo === null
        ? $stmt->bindValue(':logo', null, PDO::PARAM_NULL)
        : $stmt->bindParam(':logo', $logo);

    if ($telephone !== null) {
        $stmt->bindParam(':telephone', $telephone);
    }

    if ($adresse !== null) {
        $stmt->bindParam(':adresse', $adresse);
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


public function modifierEtatActif(int $id, int $actif): void {
    $stmt = $this->connexion->prepare("UPDATE Sponsors SET is_actif = :actif WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':actif', $actif, PDO::PARAM_INT);
    $stmt->execute();
}

    public function __destruct() {
        $this->connexion = null;
    }





}
?>
