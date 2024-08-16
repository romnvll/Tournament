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
    public function modifierNomTerrain(int $terrain_id, string $nouveauNom): void {
        $stmt = $this->connexion->prepare("
            UPDATE Terrains 
            SET nom = :nouveauNom 
            WHERE terrain_id = :terrain_id
        ");
        $stmt->bindParam(':nouveauNom', $nouveauNom);
        $stmt->bindParam(':terrain_id', $terrain_id);
        $stmt->execute();
    }

    public function ajoutTerrain(int $idTournoi, string $nomTerrain): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Terrains (fk_idTournoi, nom)
            VALUES (:idTournoi, :nomTerrain)");
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->bindParam(':nomTerrain', $nomTerrain);
        $stmt->execute();
    }

    public function supprimerTerrainsParTournoi(int $idTournoi): void {
        $stmt = $this->connexion->prepare("DELETE FROM Terrains WHERE fk_idTournoi = :idTournoi");
        $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->execute();
    }
    

    public function suppressionTerrain(int $idTournoi, int $idTerrain): void {
        try {
            // Vérifier si le terrain est utilisé dans une planification
            $stmtCheckUsage = $this->connexion->prepare("
                SELECT COUNT(*) FROM Planification
                WHERE terrain_id = :idTerrain
                AND (rencontre_id IS NOT NULL OR arbitre_id IS NOT NULL OR label_id IS NOT NULL)
            ");
            $stmtCheckUsage->bindParam(':idTerrain', $idTerrain);
            $stmtCheckUsage->execute();
            $usageCount = $stmtCheckUsage->fetchColumn();
    
            // Si le terrain est utilisé, lancer une exception et empêcher la suppression
            if ($usageCount > 0) {
                throw new Exception("Le terrain ne peut pas être supprimé car il est utilisé dans une planification.");
            }
    
            // Début de la transaction
            $this->connexion->beginTransaction();
    
            // Supprimer les entrées dans la table Planification liées au terrain
            $stmtPlanification = $this->connexion->prepare("
                DELETE FROM Planification
                WHERE terrain_id = :idTerrain
            ");
            $stmtPlanification->bindParam(':idTerrain', $idTerrain);
            $stmtPlanification->execute();
    
            // Supprimer le terrain dans la table Terrains
            $stmtTerrain = $this->connexion->prepare("
                DELETE FROM Terrains
                WHERE fk_idTournoi = :idTournoi
                AND terrain_id = :idTerrain
            ");
            $stmtTerrain->bindParam(':idTournoi', $idTournoi);
            $stmtTerrain->bindParam(':idTerrain', $idTerrain);
            $stmtTerrain->execute();
    
            // Commit de la transaction
            $this->connexion->commit();
        } catch (Exception $e) {
            // En cas d'erreur, rollback de la transaction
            $this->connexion->rollBack();
            // Gérer l'exception selon vos besoins, par exemple en affichant un message d'erreur
            echo "Erreur : " . $e->getMessage();
        }
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


    public function AfficherTerrainParId(int $terrainId): array {
        $stmt = $this->connexion->prepare("
            SELECT * 
            FROM Terrains
            WHERE terrain_id = :terrainId");
        $stmt->bindParam(':terrainId', $terrainId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
