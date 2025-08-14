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

    public function ajouterClub(string $nom, ?string $logo, int $typeSport, int $utilisateur): void {
        $stmt = $this->connexion->prepare("INSERT INTO Clubs (nom, logo, type_sport_id, utilisateur_id) VALUES (:nom, :logo, :type_sport_id, :utilisateur_id)");
    
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':type_sport_id', $typeSport);
        $stmt->bindParam(':utilisateur_id', $utilisateur);
    
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
/*
    public function afficherClubsDetailByMail(string $email): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Clubs where email=:email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
*/
  public function afficherClubs(): array {
    $stmt = $this->connexion->prepare("
        SELECT 
            c.*, 
            tds.nom AS nom_type_sport
        FROM 
            Clubs c
        LEFT JOIN 
            TypeDeSport tds ON c.type_sport_id = tds.id
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function afficherClubsParTypeDeSport(int $typeSportId): array {
    $stmt = $this->connexion->prepare("
        SELECT 
            c.*, 
            tds.nom AS nom_type_sport
        FROM 
            Clubs c
        LEFT JOIN 
            TypeDeSport tds ON c.type_sport_id = tds.id
        WHERE 
            c.type_sport_id = :typeSportId
    ");
    $stmt->bindParam(':typeSportId', $typeSportId, PDO::PARAM_INT);
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

    public function updateClub($id, $nom, $email = null, $logo, int $typeSport) {
    // Commencez la requête de mise à jour
    $query = "UPDATE Clubs SET nom = :nom, logo = :logo, type_sport_id = :typeSport";

    // Ajoutez les champs facultatifs s'ils sont fournis
    if (!is_null($email)) {
        $query .= ", email = :email";
    }

    // Complétez la requête avec la condition WHERE
    $query .= " WHERE id = :id";

    // Préparez la requête
    $stmt = $this->connexion->prepare($query);

    // Lie les paramètres requis
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nom', $nom);
    
    $stmt->bindParam(':logo', $logo);
    $stmt->bindParam(':typeSport', $typeSport, PDO::PARAM_INT);

    // Lie les paramètres facultatifs s'ils sont fournis
    if (!is_null($email)) {
        $stmt->bindParam(':email', $email);
    }

    // Exécutez la requête
    return $stmt->execute();
}


// Dans clubDao.class.php

public function getClubById($id) {
    $query = "
        SELECT 
            c.*, 
            tds.nom AS nom_type_sport
        FROM 
            Clubs c
        LEFT JOIN 
            TypeDeSport tds ON c.type_sport_id = tds.id
        WHERE 
            c.id = :id
    ";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



    

    
}

