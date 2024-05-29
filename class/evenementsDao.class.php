<?php
class EvenementDAO {
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

    public function ajouterEvenement(int $fk_Idcreneau, int $fk_Idrencontre, int $fk_Idterrain): void {
        $stmt = $this->connexion->prepare("INSERT INTO Evenements (fk_Idcreneau, fk_Idrencontre, fk_Idterrain) VALUES (:fk_Idcreneau, :fk_Idrencontre, :fk_Idterrain)");
        $stmt->bindParam(':fk_Idcreneau', $fk_Idcreneau);
        $stmt->bindParam(':fk_Idrencontre', $fk_Idrencontre);
        $stmt->bindParam(':fk_Idterrain', $fk_Idterrain);
        $stmt->execute();
    }

    public function supprimerEvenement(int $id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Evenements WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function modifierEvenement(int $id, int $fk_Idcreneau, int $fk_Idrencontre, int $fk_Idterrain): void {
        $stmt = $this->connexion->prepare("UPDATE Evenements SET fk_Idcreneau = :fk_Idcreneau, fk_Idrencontre = :fk_Idrencontre, fk_Idterrain = :fk_Idterrain WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':fk_Idcreneau', $fk_Idcreneau);
        $stmt->bindParam(':fk_Idrencontre', $fk_Idrencontre);
        $stmt->bindParam(':fk_Idterrain', $fk_Idterrain);
        $stmt->execute();
    }

    public function obtenirEvenement(int $id): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Evenements WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenirTousLesEvenements(int $idTournoi): array {
        $stmt = $this->connexion->prepare("
            SELECT *
            FROM Evenements
            WHERE fk_idTournoi = :idTournoi
        ");
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}



?>