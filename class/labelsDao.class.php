<?php
class LabelDao {
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
/**
 * Crée les labels pour les phases d'élimination directe d'une catégorie
 * @param string $nomCategorie Nom de la catégorie
 * @param int $nombreEquipes Nombre total d'équipes (16, 8, 4 ou 2)
 * @param int $tournoi_id ID du tournoi
 * @return array Tableau des labels créés avec leurs IDs
 */
public function creerLabelsEliminationDirecte(string $nomCategorie, int $nombreEquipes, int $tournoi_id, int $idCategorie): array {
    // Déterminer la phase de départ selon le nombre d'équipes
    $phases = [
        16 => 1, // 8ème de finale
        8 => 2,  // Quart de finale
        4 => 3,  // Demi-finale
        2 => 4,  // Finale
    ];
    
    $phaseDepart = $phases[$nombreEquipes] ?? 2;
    
    $phasesLibelles = [
        1 => "8ème de finale", 
        2 => "Quart de finale", 
        3 => "Demi-finale", 
        4 => "Finale"
    ];
    
    $couleurs = [
        1 => "#FF6B6B", // 8ème - Rouge
        2 => "#4ECDC4", // Quart - Turquoise
        3 => "#45B7D1", // Demi - Bleu
        4 => "#FFD93D"  // Finale - Or
    ];
    
    $labelsCreés = [];
    $equipesRestantes = $nombreEquipes;
    
    // Créer les labels de la phase de départ jusqu'à la finale
    for ($phase = $phaseDepart; $phase <= 4; $phase++) {
        // Calculer le nombre de rencontres pour cette phase
        $nombreRencontres = $equipesRestantes / 2;
        
        // Créer un label pour chaque rencontre
        for ($numRencontre = 1; $numRencontre <= $nombreRencontres; $numRencontre++) {
            $description = "EliminationDirect " . $nomCategorie . " " . $phasesLibelles[$phase] . " rencontre " . $numRencontre;
            $couleur = $couleurs[$phase];
            
            // Vérifier si le label existe déjà
            $stmtCheck = $this->connexion->prepare("
                SELECT label_id 
                FROM Labels 
                WHERE description = :description 
                AND tournoi_id = :tournoi_id
            ");
            $stmtCheck->bindParam(':description', $description);
            $stmtCheck->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $existant = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($existant) {
                // Le label existe déjà, on récupère son ID
                $labelsCrees[] = [
                    'id' => $existant['id'],
                    'description' => $description,
                    'phase' => $phase,
                    'num_rencontre' => $numRencontre,
                    'existe_deja' => true
                ];
            } else {
                // Créer le nouveau label
                $stmt = $this->connexion->prepare("
                    INSERT INTO Labels (description, couleur, tournoi_id,categorie_id) 
                    VALUES (:description, :couleur, :tournoi_id, :categorie_id)
                ");
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':couleur', $couleur);
                $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
                $stmt->bindParam(':categorie_id', $idCategorie, PDO::PARAM_INT);
                $stmt->execute();
                
                $labelsCrees[] = [
                    'id' => $this->connexion->lastInsertId(),
                    'description' => $description,
                    'phase' => $phase,
                    'num_rencontre' => $numRencontre,
                    'existe_deja' => false
                ];
            }
        }
        
        // Pour la phase suivante, diviser le nombre d'équipes par 2
        $equipesRestantes = $equipesRestantes / 2;
    }
    
    return $labelsCrees;
}




    // Méthode pour ajouter un label
    public function ajouterLabel(string $description, string $couleur, int $tournoi_id): void {
        $stmt = $this->connexion->prepare("
            INSERT INTO Labels (description, couleur, tournoi_id) 
            VALUES (:description, :couleur, :tournoi_id)
        ");
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':couleur', $couleur);
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Méthode pour récupérer un label par son ID
    public function getLabelById(int $label_id) {
        $stmt = $this->connexion->prepare("SELECT * FROM Labels WHERE label_id = :label_id");
        $stmt->bindParam(':label_id', $label_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function getLabelByClubs(int $club_id) {
        $stmt = $this->connexion->prepare("SELECT * FROM Labels WHERE club_id = :club_id");
        $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Méthode pour récupérer tous les labels d'un tournoi
    public function getLabelsByTournoiId(int $tournoi_id) {
        $stmt = $this->connexion->prepare("SELECT * FROM Labels WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    //methode qui permet d'afficher les labels


    public function getLabelsWithCreneauxByTournoiId(int $tournoi_id)
{
    $sql = "
      SELECT
    c.nom AS creneau_horaire,
    GROUP_CONCAT(DISTINCT l.description ORDER BY l.description SEPARATOR ' + ') AS labels
FROM Planification p
JOIN Creneaux c ON p.creneau_id = c.creneau_id
JOIN Labels l ON p.label_id = l.label_id
WHERE p.label_id IS NOT NULL AND p.tournoi_id = :tournoi_id
GROUP BY c.nom
ORDER BY c.nom



    ";

    // Préparer et exécuter la requête
    $stmt = $this->connexion->prepare($sql);
    $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $stmt->execute();

    // Récupérer les résultats
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    



    // Méthode pour mettre à jour un label
    public function updateLabel(int $label_id, string $description, string $couleur): void {
        $stmt = $this->connexion->prepare("
            UPDATE Labels 
            SET description = :description, couleur = :couleur 
            WHERE label_id = :label_id
        ");
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':couleur', $couleur);
        $stmt->bindParam(':label_id', $label_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function supprimerLabel(int $label_id): void {
        try {
            $stmt = $this->connexion->prepare("DELETE FROM Labels WHERE label_id = :label_id");
            $stmt->bindParam(':label_id', $label_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // Code SQLSTATE pour une violation de clé étrangère
                throw new Exception("Impossible de supprimer ce label car il est actuellement utilisé dans une ou plusieurs planifications.");
            }
            throw $e; // Relancer les autres exceptions
        }
    }
    

    // Méthode pour supprimer tous les labels d'un tournoi
    public function supprimerLabelsParTournoi(int $tournoi_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Labels WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
