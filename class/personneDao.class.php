<?php

class PersonneDao {
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

    // Ajouter une nouvelle personne
    public function ajouterPersonne($nom, $prenom, $mail, $tournoi_id) {
        $stmt = $this->connexion->prepare("
            INSERT INTO Personne ( Nom, Prenom, Mail, tournoi_id) 
            VALUES (:nom, :prenom, :mail, :tournoi_id)
        ");
        
        $stmt->bindValue(':nom', $nom);
        $stmt->bindValue(':prenom', $prenom);
        $stmt->bindValue(':mail', $mail);
        $stmt->bindValue(':tournoi_id', $tournoi_id);
        $stmt->execute();

        return $this->connexion->lastInsertId();
    }

    // Supprimer une personne par ID
    public function supprimerPersonne($id) {
        $stmt = $this->connexion->prepare("
            DELETE FROM Personne 
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }


    public function supprimerPersonneParTournoi($id_tournoi) {
        $stmt = $this->connexion->prepare("
            DELETE FROM Personne 
            WHERE tournoi_id = :tournoi_id
        ");
        $stmt->bindValue(':tournoi_id', $id_tournoi);
        $stmt->execute();
    }

    // Modifier une personne
    public function modifierPersonne($id,  $nom, $prenom, $mail, $tournoi_id) {
        $stmt = $this->connexion->prepare("
            UPDATE Personne 
            SET  Nom = :nom, Prenom = :prenom, Mail = :mail, tournoi_id = :tournoi_id 
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $id);
        
        $stmt->bindValue(':nom', $nom);
        $stmt->bindValue(':prenom', $prenom);
        $stmt->bindValue(':mail', $mail);
        $stmt->bindValue(':tournoi_id', $tournoi_id);
        $stmt->execute();
    }

    // Récupérer une personne par ID
    public function recupererPersonneParId($id) {
        $stmt = $this->connexion->prepare("
            SELECT * FROM Personne 
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer toutes les personnes
    public function recupererToutesLesPersonnes($idtournoi) {
        $stmt = $this->connexion->prepare("
            SELECT * FROM Personne
            WHERE tournoi_id = :tournoi_id
        ");
        $stmt->bindValue(':tournoi_id', $idtournoi);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
