<?php

class ClubDAO {
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

    public function ajouterClub(string $nom, string $contact, ?string $logo): void {
        $stmt = $this->connexion->prepare("INSERT INTO Clubs (nom, contact, logo) VALUES (:nom, :contact, :logo)");
    
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':contact', $contact);
    
        // Vérifier si $logo est null
        if ($logo === null) {
            $stmt->bindValue(':logo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':logo', $logo);
        }
    
        $stmt->execute();
    }
    

    public function supprimerClub(int $id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Clubs WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function afficherClubs(): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Clubs");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clubsParticipatingInTournoi(int $tournoiId): array {
        $stmt = $this->connexion->prepare("
            SELECT DISTINCT c.id, c.nom,c.logo
            FROM Clubs c
            JOIN Equipes e ON c.id = e.club_id
            WHERE e.tournoi_id = :tournoiId order by c.nom
        ");
        $stmt->bindParam(':tournoiId', $tournoiId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dans clubDao.class.php

    public function updateClub($id, $nom, $email = null, $password = null, $contact, $logo) {
        // Commencez la requête de mise à jour
        $query = "UPDATE Clubs SET nom = :nom, contact = :contact, logo = :logo";
    
        // Ajoutez les champs facultatifs s'ils sont fournis
        if (!is_null($email)) {
            $query .= ", email = :email";
        }
        if (!is_null($password)) {
            $query .= ", password = :password";
        }
    
        // Complétez la requête avec la condition WHERE
        $query .= " WHERE id = :id";
    
        // Préparez la requête
        $stmt = $this->connexion->prepare($query);
    
        // Lie les paramètres requis
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':contact', $contact);
        $stmt->bindParam(':logo', $logo);
    
        // Lie les paramètres facultatifs s'ils sont fournis
        if (!is_null($email)) {
            $stmt->bindParam(':email', $email);
        }
        if (!is_null($password)) {
            $stmt->bindParam(':password', $password);
        }
    
        // Exécutez la requête
        return $stmt->execute();
    }
    

// Dans clubDao.class.php

public function getClubById($id) {
    
    $stmt = $this->connexion->prepare("SELECT * FROM Clubs WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    

    
}

