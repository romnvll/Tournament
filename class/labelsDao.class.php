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
        WITH LabelledCreneaux AS (
            SELECT 
                p.creneau_id,
                c.nom AS creneau_nom,
                p.label_id,
                l.description AS label_nom,
                c.creneau_id - ROW_NUMBER() OVER (ORDER BY c.creneau_id) AS grp
            FROM 
                Planification p
            JOIN 
                Creneaux c ON p.creneau_id = c.creneau_id
            JOIN 
                Labels l ON p.label_id = l.label_id
            WHERE 
                p.tournoi_id = :tournoi_id
        ),
        GroupedLabels AS (
            SELECT 
                grp,
                MIN(creneau_id) AS first_creneau_id,
                MAX(creneau_id) AS last_creneau_id
            FROM 
                LabelledCreneaux
            GROUP BY 
                grp
        )
        SELECT 
            gl.first_creneau_id,
            gl.last_creneau_id,
            c1.nom AS first_creneau_nom,
            c2.nom AS last_creneau_nom,
            (SELECT label_nom FROM LabelledCreneaux lc WHERE lc.creneau_id = gl.first_creneau_id) AS first_label,
            (SELECT label_nom FROM LabelledCreneaux lc WHERE lc.creneau_id = gl.last_creneau_id) AS last_label
        FROM 
            GroupedLabels gl
        JOIN 
            Creneaux c1 ON c1.creneau_id = gl.first_creneau_id
        JOIN 
            Creneaux c2 ON c2.creneau_id = gl.last_creneau_id;
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

    // Méthode pour supprimer un label
    public function supprimerLabel(int $label_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Labels WHERE label_id = :label_id");
        $stmt->bindParam(':label_id', $label_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Méthode pour supprimer tous les labels d'un tournoi
    public function supprimerLabelsParTournoi(int $tournoi_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Labels WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
