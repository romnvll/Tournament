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

    public function ajouterEvenement(int $fk_Idcreneau = null, int $fk_Idrencontre = null, int $fk_Idterrain = null, int $fk_idTournoi = null): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Evenements (fk_Idcreneau, fk_Idrencontre, fk_Idterrain, fk_idTournoi)
            VALUES (:fk_Idcreneau, :fk_Idrencontre, :fk_Idterrain, :fk_idTournoi)");
            $stmt->bindParam(':fk_idTournoi', $fk_idTournoi);
        if ($fk_Idcreneau !== null) {
            $stmt->bindParam(':fk_Idcreneau', $fk_Idcreneau);
        } else {
            $stmt->bindValue(':fk_Idcreneau', null, PDO::PARAM_NULL);
        }
    
        if ($fk_Idrencontre !== null) {
            $stmt->bindParam(':fk_Idrencontre', $fk_Idrencontre);
        } else {
            $stmt->bindValue(':fk_Idrencontre', null, PDO::PARAM_NULL);
        }
    
        if ($fk_Idterrain !== null) {
            $stmt->bindParam(':fk_Idterrain', $fk_Idterrain);
        } else {
            $stmt->bindValue(':fk_Idterrain', null, PDO::PARAM_NULL);
        }
    
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

    public function obtenirEvenement(int $idTournoi): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Evenements WHERE fk_idTournoi = :id");
        $stmt->bindParam(':id', $idTournoi);
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


    public function listerLesCreneaux(int $idTournoi): array {
        $stmt = $this->connexion->prepare("
            SELECT *
            FROM Creneaux
            WHERE fk_idTournoi = :idTournoi");
            $stmt->bindParam(':idTournoi', $idTournoi);
            $stmt->execute();
        
            return $stmt->fetchAll(PDO::FETCH_ASSOC);   
        

    }

    public function recupererIdCreneau(int $idTournoi, int $numero): ?int {
        // Préparez la requête SQL pour sélectionner l'ID du créneau
        $stmt = $this->connexion->prepare("
            SELECT id
            FROM Creneaux
            WHERE fk_idTournoi = :idTournoi
            AND numero = :numero
            LIMIT 1");
    
        // Lie les paramètres à la requête
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->bindParam(':numero', $numero);
        
        // Exécutez la requête
        $stmt->execute();
    
        // Récupérez le résultat
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Vérifiez si un résultat a été trouvé et retournez l'ID, sinon retournez null
        return $result ? $result['id'] : null;
    }
    

    public function ajoutCreneau(int $idTournoi): void {
        // Récupération du dernier numéro de la table Creneaux pour un tournoi donné
        $stmt = $this->connexion->prepare("
            SELECT MAX(numero) AS dernier_numero
            FROM Creneaux
            WHERE fk_idTournoi = :idTournoi");
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->execute();
        $dernierNumero = $stmt->fetchColumn();
    
        // Si aucun créneau n'existe pour le tournoi, le numéro sera 1
        $numero = $dernierNumero ? $dernierNumero + 1 : 1;
    
        // Ajout du nouveau créneau dans la table
        $stmt = $this->connexion->prepare("
            INSERT INTO Creneaux (fk_idTournoi, numero)
            VALUES (:idTournoi, :numero)");
        $stmt->bindParam(':idTournoi', $idTournoi);
        $stmt->bindParam(':numero', $numero);
        // ... Ajoutez ici les autres champs de la table Creneaux
        $stmt->execute();
    }
    


    
}



?>