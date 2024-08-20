<?php
class EquipeDAO {
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
    
    public function ajouterEquipe(string $nom, int $categorie, int $tournoi_id, ?int $poule_id, int $club_id): void {
        // Insertion de l'équipe
        $stmt = $this->connexion->prepare("INSERT INTO Equipes (nom, categorie, tournoi_id, club_id) VALUES (:nom, :categorie, :tournoi_id, :club_id)");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':categorie', $categorie);
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->bindParam(':club_id', $club_id);
        $stmt->execute();
    
        // Récupérer l'ID de l'équipe insérée
        $equipe_id = $this->connexion->lastInsertId();
    
        // Si un poule_id est fourni, ajoutez l'équipe à la poule dans la table de liaison
        if ($poule_id !== null) {
            $stmtPoule = $this->connexion->prepare("INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:equipe_id, :poule_id)");
            $stmtPoule->bindParam(':equipe_id', $equipe_id);
            $stmtPoule->bindParam(':poule_id', $poule_id);
            $stmtPoule->execute();
        }
    }
    

    public function modifierEquipe(int $id, string $nom, int $categorie): void {
        $stmt = $this->connexion->prepare("UPDATE Equipes SET nom = :nom, categorie = :categorie WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':categorie', $categorie);
        $stmt->execute();
    }

    
    




    public function modifierEquipeIdPoule(int $idPoule, int $idequipe): void {
        echo $idPoule;
        // Vérifier d'abord si une association pour cette équipe existe déjà
        $stmtCheck = $this->connexion->prepare("SELECT * FROM EquipePoule WHERE equipe_id = :idequipe");
        $stmtCheck->bindParam(':idequipe', $idequipe);
        $stmtCheck->execute();
    
        if ($stmtCheck->fetch()) {
            try {
            // Si une association existe déjà, mise à jour de l'association
            $stmtUpdate = $this->connexion->prepare("UPDATE EquipePoule SET poule_id = :pouleId WHERE equipe_id = :idequipe");
            $stmtUpdate->bindParam(':pouleId', $idPoule);
            $stmtUpdate->bindParam(':idequipe', $idequipe);
            $stmtUpdate->execute();
            }
            catch(PDOException $e) {
                if ($e->getCode() == '23000') {
                    $errorCode = $e->errorInfo[1];
                    if ($errorCode === 1062) {
                       // session_start();
                       
                       echo "Impossible de déplacer cette équipe de poule, car elle est présente dans une poule de phase finale.";
                        //echo "Impossible de déplacer cette équipe de poule, car elle est présente dans une poule de phase finale.";
                       
                        exit();

                    } else {
                        // Gérer d'autres erreurs si nécessaire
                        echo "Une erreur s'est produite : " . $e->getMessage();
                    }
                } else {
                    // Autres erreurs PDO
                    echo "Une erreur PDO s'est produite : " . $e->getMessage();
                }
            }
        } else {
            // Si aucune association n'existe pour cette équipe, insérez une nouvelle association
            $stmtInsert = $this->connexion->prepare("INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:idequipe, :pouleId)");
            $stmtInsert->bindParam(':pouleId', $idPoule);
            $stmtInsert->bindParam(':idequipe', $idequipe);
            $stmtInsert->execute();
        }
    }

    public function ajouterEquipePouleSiNonExistant(int $idPoule, int $idequipe): void {
        // Vérifier d'abord si une association spécifique pour cette équipe et cette poule existe déjà
        $stmtCheck = $this->connexion->prepare("SELECT * FROM EquipePoule WHERE equipe_id = :idequipe AND poule_id = :pouleId");
        $stmtCheck->bindParam(':idequipe', $idequipe);
        $stmtCheck->bindParam(':pouleId', $idPoule);
        $stmtCheck->execute();
        
        if (!$stmtCheck->fetch()) {
            // Si aucune association spécifique n'existe pour cette équipe et cette poule, insérez une nouvelle association
            $stmtInsert = $this->connexion->prepare("INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:idequipe, :pouleId)");
            $stmtInsert->bindParam(':pouleId', $idPoule);
            $stmtInsert->bindParam(':idequipe', $idequipe);
            $stmtInsert->execute();
        }
    }
    
    
    
    public function supprimerEquipeParId(int $idEquipe): void {
       
         // Supprimer d'abord les correspondances de l'équipe à une poule
    $stmtEquipePoule = $this->connexion->prepare("DELETE FROM EquipePoule WHERE equipe_id = :idEquipe");
    $stmtEquipePoule->bindParam(':idEquipe', $idEquipe);
    $stmtEquipePoule->execute();

    // Ensuite, supprimer les rencontres où l'équipe est impliquée
    $stmtRencontres = $this->connexion->prepare("DELETE FROM Rencontres WHERE equipe1_id = :idEquipe OR equipe2_id = :idEquipe");
    $stmtRencontres->bindParam(':idEquipe', $idEquipe);
    $stmtRencontres->execute();

    // Enfin, supprimer l'équipe elle-même
    $stmtEquipe = $this->connexion->prepare("DELETE FROM Equipes WHERE id = :idEquipe");
    $stmtEquipe->bindParam(':idEquipe', $idEquipe);
    $stmtEquipe->execute();
}
    public function confirmerEquipe(int $id, string $etat): void {

        if ($etat == "presente") {
        $stmt = $this->connexion->prepare("UPDATE Equipes SET isPresent = true WHERE id = :id");
        }
        if ($etat == "absente") {
            $stmt = $this->connexion->prepare("UPDATE Equipes SET isPresent = false WHERE id = :id"); 
        }
        $stmt->bindParam(':id', $id);
        
        $stmt->execute();
    }



    public function getAllEquipesByCategorie(String $categorie): array {
        
        $stmt = $this->connexion->prepare("select * from Equipes where categorie = :categorie ");
        
        $stmt->bindValue(':categorie', $categorie);
        $stmt->execute();
        $equipes=$stmt->fetchAll();
        
        return $equipes;

    }

    public function getAllEquipesByPouleId(int $pouleId): array {
        $stmt = $this->connexion->prepare(
            "SELECT e.*, c.Nom_categorie 
             FROM Equipes e
             JOIN EquipePoule ep ON e.id = ep.equipe_id
             JOIN Poules p ON ep.poule_id = p.id
             JOIN Categorie c ON p.fk_idcategorie = c.id_categorie
             WHERE ep.poule_id = :pouleId"
        );
    
        $stmt->bindParam(':pouleId', $pouleId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    public function supprimerEquipesParTournoi(int $idTournoi): void {
        $query = "DELETE FROM Equipes WHERE tournoi_id = :idTournoi";
        
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->execute();
    }
    

    public function getAllCategorieByIdTournoi(int $idTournoi) : array {

        $stmt = $this->connexion->prepare("
            SELECT DISTINCT c.id_categorie, c.Nom_categorie, COUNT(e.id) AS nombre_equipes
            FROM Equipes e
            JOIN Categorie c ON e.categorie = c.id_categorie
            WHERE e.tournoi_id = :idTournoi
            GROUP BY c.id_categorie, c.Nom_categorie
        ");
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return $result;
    }
    

    public function countEquipesPresentesInPoule($pouleId) {
        // Utilisation de la table de liaison EquipePoule pour obtenir le compte
        $query = "SELECT COUNT(*) AS nombre_equipes 
                  FROM EquipePoule 
                  JOIN Equipes e ON EquipePoule.equipe_id = e.id
                  WHERE EquipePoule.poule_id = :poule_id AND e.IsPresent = 1";
    
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':poule_id', $pouleId, PDO::PARAM_INT);
        $stmt->execute();
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result !== false) {
            return (int)$result['nombre_equipes'];
        }
    
        return 0;
    }
    
    public function getAllEquipeByIdTournoi(int $idTournoi): array {
        $query = "
            SELECT e.*, 
            c.nom AS nom_club,
            c.logo AS logo,
            cat.Nom_categorie AS nom_categorie
            FROM Equipes e
            INNER JOIN Clubs c ON e.club_id = c.id
            INNER JOIN Categorie cat ON e.categorie = cat.id_categorie
            WHERE e.tournoi_id = :tournoi_id
            ORDER BY e.categorie
        ";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':tournoi_id', $idTournoi, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    

    public function getAllEquipeByIdTournoiAndClub (int $idTournoi, int $clubId) {
        $query = "SELECT e.*, c.logo, cat.Nom_categorie, cat.Couleur
FROM Equipes e
INNER JOIN Clubs c ON e.club_id = c.id
INNER JOIN Categorie cat ON e.categorie = cat.id_categorie
WHERE e.tournoi_id = :tournoi_id AND e.club_id = :club_id
ORDER BY e.nom;";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':tournoi_id', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':club_id', $clubId, PDO::PARAM_INT);
        $stmt->execute();
    
       return  $stmt->fetchAll();

    }

    

       


}
