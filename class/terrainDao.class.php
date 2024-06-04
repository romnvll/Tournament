<?php
class TerrainDao {
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


    public function ajoutTerrain(int $idTournoi, string $nomTerrain): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Terrains (fk_idTournoi, nomTerrain)
            VALUES (:idTournoi, :nomTerrain)");
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->bindParam(':nomTerrain', $nomTerrain);
        $stmt->execute();
    }

    public function suppressionTerrain(int $idTournoi, int $idTerrain): void {
        $stmt = $this->connexion->prepare("
            DELETE FROM Terrains
            WHERE fk_idTournoi = :idTournoi
            AND idTerrain = :idTerrain");
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->bindParam(':idTerrain', $idTerrain);
        $stmt->execute();
    }

    public function compterTerrains(int $idTournoi): int {
        $stmt = $this->connexion->prepare("
            SELECT COUNT(*) AS nombre_terrains
            FROM Terrains
            WHERE fk_idTournoi = :idTournoi");
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    public function AfficherTerrains(int $idTournoi): array {
        $stmt = $this->connexion->prepare("
            SELECT * 
            FROM Terrains
            WHERE fk_idTournoi = :idTournoi");
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function AfficherIdTerrain(int $idTournoi, int $numTerrain): ?int {
        // Calculer l'offset avant de préparer la requête
        $offset = $numTerrain - 1; // Si $numTerrain est 1, l'offset doit être 0
    
        // Préparez la requête SQL pour sélectionner uniquement l'ID du terrain avec un offset
        $stmt = $this->connexion->prepare("
            SELECT id
            FROM Terrains
            WHERE fk_idTournoi = :idTournoi
            LIMIT 1 OFFSET :offset
        ");
    
        // Lie les paramètres à la requête
        $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT); // Utilisez bindValue pour les expressions statiques
        
        // Exécutez la requête
        $stmt->execute();
    
        // Récupérez le résultat
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Retournez l'ID du terrain ou null si aucune ligne n'a été trouvée
        return $result ? (int) $result['id'] : null;
    }
    



}
