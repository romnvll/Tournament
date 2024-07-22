<?php
class tournoiDao {

private $connexion;

private int $idTournoi;

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


public function ajouterTournoi(string $nom, int $nb_terrains, string $heure_debut, int $isClassement, ?int $idParent = null, $heure_fin = 0, int $pasHoraire = 0): void {
    $stmt = $this->connexion->prepare("INSERT INTO Tournois (nom, nb_terrains, heure_debut, pasHoraire, isClassement, IdParent, heure_fin) VALUES (:nom, :nb_terrains, :heure_debut, :pasHoraire, :isClassement, :idParent, :heure_fin)");
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':nb_terrains', $nb_terrains);
    $stmt->bindParam(':heure_debut', $heure_debut);
    $stmt->bindParam(':isClassement', $isClassement, PDO::PARAM_INT);
    $stmt->bindParam(':idParent', $idParent, PDO::PARAM_INT);
    $stmt->bindParam(':pasHoraire', $pasHoraire, PDO::PARAM_INT); // Change PasHoraire to pasHoraire
    $stmt->bindParam(':heure_fin', $heure_fin);
    $stmt->execute();
}




    public function supprimerTournoi(int $id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Tournois WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function afficherLesTournois() : array {
       
        $stmt = $this->connexion->prepare("
        SELECT
            t.*,
            COUNT(e.id) AS nombre_equipes
        FROM
            Tournois t
        LEFT JOIN
            Equipes e ON t.id = e.tournoi_id
        GROUP BY
            t.id
    ");
        $stmt->execute();
       $tounois=$stmt->fetchAll();
       return $tounois;
        
   }

   public function afficherLesTournoisDeClassement() : array {
       
    $stmt = $this->connexion->prepare("select * FROM Tournois where isClassement = 1 ");
    $stmt->execute();
   $tounois=$stmt->fetchAll();
   return $tounois;
    
}


public function afficherLesTournoisQuiNeSontPasClassement() : array {
       
    $stmt = $this->connexion->prepare("select * FROM Tournois where isClassement = 0 ");
    $stmt->execute();
   $tounois=$stmt->fetchAll();
   return $tounois;
    
}

public function getIdTournoiParIdParent($idParent) : array {
    $stmt = $this->connexion->prepare("SELECT id FROM Tournois WHERE IdParent = :idParent");
    $stmt->bindParam(':idParent', $idParent, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

   public function getTournoiById(int $id_tournoi): array {
    if ($id_tournoi === null) {
        return [];
    }
    $stmt = $this->connexion->prepare("SELECT * FROM Tournois WHERE id = :id");
    $stmt->bindValue(':id', $id_tournoi, PDO::PARAM_INT);
    $stmt->execute();
    $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);
    return $tournoi ? $tournoi : [];
}
//RRRRRRRRRRRRRRRRRRR

public function getCategoriesPourTournoi(int $idTournoi) {
    // Requête pour récupérer les catégories distinctes pour un tournoi spécifié
    $query = "SELECT DISTINCT p.categorie 
              FROM Poules p
              WHERE p.tournoi_id = :idTournoi and is_classement=0";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    // Récupérer les catégories et les renvoyer
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}




public function genererRencontresPhaseclassement(int $idTournoi, string $categorie) {
    $query = "SELECT DISTINCT p.id 
    FROM Poules p
    WHERE p.tournoi_id = :idTournoi AND p.categorie = :categorie";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->bindValue(':categorie', $categorie, PDO::PARAM_STR);
    $stmt->execute();
    $poules = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $equipesParPoule = [];
    $categoriesParPoule = [];

    foreach ($poules as $pouleId) {
        $query = "SELECT
        e.id,
        e.nom,
        e.categorie,
        e.IsPresent,
        e.tournoi_id,
        ep.poule_id,
        e.club_id,
        ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id AND r.score1 > r.score2) OR (r.equipe2_id = e.id AND r.score2 > r.score1)) * 3) +
        ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id OR r.equipe2_id = e.id) AND r.score1 = r.score2) * 2) +
        ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id AND r.score1 < r.score2) OR (r.equipe2_id = e.id AND r.score2 < r.score1))) AS TotalDesPoints,
        COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id), 0) +
        COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id), 0) AS nombreButsMarque,
        COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id), 0) +
        COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id), 0) AS nombreButsEncaisse,
        (COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id), 0) +
        COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id), 0)) -
        (COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id), 0) +
        COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id), 0)) AS DifferenceButs
    FROM
        Equipes e
    JOIN EquipePoule ep ON e.id = ep.equipe_id
    WHERE
        ep.poule_id = :pouleId AND
        e.isPresent = 1 AND
        e.tournoi_id = :idTournoi
    ORDER BY
        TotalDesPoints DESC,
        nombreButsMarque DESC,
        DifferenceButs DESC ";  // la requête complète pour récupérer les équipes par poule
        
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
        $stmt->execute();

        $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $equipesParPoule[$pouleId] = $equipes;

        if(!empty($equipes)) {
            $categoriesParPoule[$pouleId] = $equipes[0]['categorie'];
        }
    }

    // Pour détecter les catégories avec une poule unique
    $categoriesCounts = array_count_values($categoriesParPoule);
    $categoriesUniquePoule = array_keys($categoriesCounts, 1);
    
    $rencontres = [];

    foreach ($equipesParPoule as $pouleId => $equipes) {
        if (isset($categoriesParPoule[$pouleId]) && in_array($categoriesParPoule[$pouleId], $categoriesUniquePoule)) {
           
            for ($i = 0; $i < count($equipes); $i += 2) {
                if (isset($equipes[$i + 1])) {
                    $rencontres[] = [
                        'equipe1' => [
                            'id' => $equipes[$i]['id'],
                            'nom' => $equipes[$i]['nom'],
                            'categorie' => $equipes[$i]['categorie']
                        ],
                        'equipe2' => [
                            'id' => $equipes[$i + 1]['id'],
                            'nom' => $equipes[$i + 1]['nom'],
                            'categorie' => $equipes[$i + 1]['categorie']
                        ]
                    ];
                }
            }
        } else {
            // Votre traitement pour les autres poules
            foreach ($equipesParPoule as $pouleId2 => $equipes2) {
                if ($pouleId < $pouleId2) {
                    foreach ($equipes as $index => $equipe1) {
                        if (isset($equipes2[$index]) && $equipe1['categorie'] == $equipes2[$index]['categorie']) {
                            $rencontres[] = [
                                'equipe1' => [
                                    'id' => $equipe1['id'],
                                    'nom' => $equipe1['nom'],
                                    'categorie' => $equipe1['categorie']
                                ],
                                'equipe2' => [
                                    'id' => $equipes2[$index]['id'],
                                    'nom' => $equipes2[$index]['nom'],
                                    'categorie' => $equipes2[$index]['categorie']
                                ]
                            ];
                        }
                    }
                }
            }
        }
    }
    
    return $rencontres;
}



public function getClassementFinal(int $idTournoi) {
    // Obtenir les poules pour le tournoi donné
    $query = "SELECT DISTINCT ep.poule_id 
              FROM EquipePoule ep 
              JOIN Equipes e ON e.id = ep.equipe_id 
              WHERE e.tournoi_id = :idTournoi";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    $poules = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $classements = [];

    foreach ($poules as $pouleId) {
        // Obtenir le classement des équipes pour chaque poule basé sur les rencontres de la phase finale
        $query = "SELECT
        e.id,
        e.nom,
        e.categorie,
        (SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND ((r.equipe1_id = e.id AND r.score1 > r.score2) OR (r.equipe2_id = e.id AND r.score2 > r.score1))) AS rencontresGagnees,
        COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.isClassement = 0), 0) +
        COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.isClassement = 0), 0) AS nombreButsEncaisse,
        COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.isClassement = 0), 0) +
        COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.isClassement = 0), 0) AS nombreButsMarque,
        ((SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND ((r.equipe1_id = e.id AND r.score1 > r.score2) OR (r.equipe2_id = e.id AND r.score2 > r.score1))) * 3) +
        ((SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND (r.equipe1_id = e.id OR r.equipe2_id = e.id) AND r.score1 = r.score2) * 2) AS TotalDesPoints
    FROM
        Equipes e
    JOIN EquipePoule ep ON e.id = ep.equipe_id
    WHERE
        ep.poule_id = :pouleId AND
        e.isPresent = 1 AND
        e.tournoi_id = :idTournoi
    ORDER BY
        TotalDesPoints DESC, rencontresGagnees DESC, nombreButsMarque DESC, nombreButsEncaisse ASC";
        
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
        $stmt->execute();

        $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categorie = (isset($equipes[0])) ? $equipes[0]['categorie'] : "Non spécifié";

        $classements[] = [
            'categorie' => $categorie,
            'poule' => $pouleId,
            'equipes' => $equipes
        ];
    }

    return $classements;
}







//RRRRRRRRRRRR

public function GetPremiersDesPoules(int $idTournoi) {
    $query = "SELECT DISTINCT ep.poule_id 
              FROM EquipePoule ep 
              JOIN Equipes e ON e.id = ep.equipe_id 
              WHERE e.tournoi_id = :idTournoi";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    $poules = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $premiersParPoule = [];

    foreach ($poules as $pouleId) {
        $query = "SELECT
                    e.id,
                    e.nom,
                    e.categorie,
                    e.IsPresent,
                    e.tournoi_id,
                    ep.poule_id,
                    e.club_id,
                    ((SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND ((r.equipe1_id = e.id AND r.score1 > r.score2) OR (r.equipe2_id = e.id AND r.score2 > r.score1))) * 3) +
                    ((SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND (r.equipe1_id = e.id OR r.equipe2_id = e.id) AND r.score1 = r.score2) * 2) +
                    ((SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND (r.equipe1_id = e.id AND r.score1 < r.score2) OR (r.equipe2_id = e.id AND r.score2 < r.score1))) AS TotalDesPoints,
                    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.isClassement = 0 AND r.equipe1_id = e.id), 0) +
                    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.isClassement = 0 AND r.equipe2_id = e.id), 0) AS nombreButsMarque,
                    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.isClassement = 0 AND r.equipe1_id = e.id), 0) +
                    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.isClassement = 0 AND r.equipe2_id = e.id), 0) AS nombreButsEncaisse,
                    (COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.isClassement = 0 AND r.equipe1_id = e.id), 0) +
                    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.isClassement = 0 AND r.equipe2_id = e.id), 0)) -
                    (COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.isClassement = 0 AND r.equipe1_id = e.id), 0) +
                    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.isClassement = 0 AND r.equipe2_id = e.id), 0)) AS DifferenceButs
                FROM
                    Equipes e
                JOIN EquipePoule ep ON e.id = ep.equipe_id
                WHERE
                    ep.poule_id = :pouleId AND
                    e.isPresent = 1 AND
                    e.tournoi_id = :idTournoi
                ORDER BY
                    TotalDesPoints DESC,
                    nombreButsMarque DESC,
                    DifferenceButs DESC
                LIMIT 1";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
        $stmt->execute();
        $premierPoule = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($premierPoule) {
            $premiersParPoule[] = $premierPoule;
        }
    }

    return $premiersParPoule;
}


public function afficherPoulesDeClassement(int $idTournoi) {
    // Récupération de toutes les poules du tournoi
    $query = "SELECT DISTINCT ep.poule_id 
              FROM EquipePoule ep 
              JOIN Equipes e ON e.id = ep.equipe_id 
              WHERE e.tournoi_id = :idTournoi";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    $poules = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $nouvellesPoules = [];


    $categories = ['U13M', 'U13F', 'U17M', 'U17F', 'U15F', 'U15M', 'Loisir', 'MiniDebutant', 'MiniConfirme', 'MiniDebrouillard', 'Senior'];

    foreach ($categories as $categorie) {
        for ($position = 1; ; $position++) {
            $equipesParPosition = [];

            foreach ($poules as $pouleId) {
                $query = "SELECT
                e.id,
                e.nom,
                e.categorie,
                e.IsPresent,
                e.tournoi_id,
                ep.poule_id,
                e.club_id,
                
                ((SELECT COUNT(*) FROM Rencontres r WHERE ((r.equipe1_id = e.id AND r.score1 > r.score2) OR (r.equipe2_id = e.id AND r.score2 > r.score1)) AND r.IsClassement = 1) * 3) +
                ((SELECT COUNT(*) FROM Rencontres r WHERE ((r.equipe1_id = e.id OR r.equipe2_id = e.id) AND r.score1 = r.score2) AND r.IsClassement = 1) * 2) +
                ((SELECT COUNT(*) FROM Rencontres r WHERE ((r.equipe1_id = e.id AND r.score1 < r.score2) OR (r.equipe2_id = e.id AND r.score2 < r.score1)) AND r.IsClassement = 1)) AS TotalDesPoints,
                COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.IsClassement = 1), 0) +
                COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.IsClassement = 1), 0) AS nombreButsMarque,
                COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.IsClassement = 1), 0) +
                COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.IsClassement = 1), 0) AS nombreButsEncaisse,
                (COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.IsClassement = 1), 0) +
                COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.IsClassement = 1), 0)) -
                (COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.IsClassement = 1), 0) +
                COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.IsClassement = 1), 0)) AS DifferenceButs
            FROM
                Equipes e
            JOIN EquipePoule ep ON e.id = ep.equipe_id
            WHERE
                ep.poule_id = :pouleId AND
                e.isPresent = 1 AND
                e.tournoi_id = :idTournoi AND
                e.categorie = :categorie 
            ORDER BY
                TotalDesPoints DESC,
                nombreButsMarque DESC,
                DifferenceButs DESC
            LIMIT 1 OFFSET :positionMinusOne
            ";

                $stmt = $this->connexion->prepare($query);
                $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
                $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
                $stmt->bindValue(':categorie', $categorie, PDO::PARAM_STR);
                $stmt->bindValue(':positionMinusOne', $position - 1, PDO::PARAM_INT);
                $stmt->execute();
                $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($equipe) {
                    $equipesParPosition[] = $equipe;
                }
            }

            // Vérifier si nous avons plus d'une équipe pour cette position et cette catégorie
            if (count($equipesParPosition) <= 1) {
                break;
            }
            $suffixe = ($position == 1) ? "er" : "èmes";
            $nouvellesPoules["Poule-des-" . $position . $suffixe . "-" . $categorie] = $equipesParPosition;
        }
    }

    return $nouvellesPoules;
}


function getNbTerrainsById($id) {
    // Assurez-vous que $this->connexion est correctement défini, représentant la connexion à la base de données.

    // Éviter les attaques d'injection SQL en utilisant des requêtes préparées
    $query = "SELECT nb_terrains FROM Tournois WHERE id = :id";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(":id", $id);

    // Exécution de la requête
    $stmt->execute();

    // Associer le résultat de la requête à une variable
    $stmt->bindColumn('nb_terrains', $nb_terrains);
    $stmt->fetch();
    
    // Retourner la valeur de nb_terrains
    return $nb_terrains;
    
}


public function modifierTournoi(int $idTournoi, string $nom, int $nb_terrains, string $heure_debut, int $isClassement, ?int $idParent = null, $heure_fin = 0, int $pasHoraire = 0, int $isVisible = 0, int $heureIsVisible, int $isArchived, int $IsRankingView, int $gestionTable = 0, int $gestionArbitres): void {
    $stmt = $this->connexion->prepare("UPDATE Tournois SET 
            nom = :nom, 
            nb_terrains = :nb_terrains, 
            heure_debut = :heure_debut, 
            pasHoraire = :pasHoraire, 
            isClassement = :isClassement, 
            IdParent = :idParent, 
            heure_fin = :heure_fin,
            heureIsVisible = :heureIsVisible,
            isVisible = :isVisible,
            isArchived = :isArchived,
            IsRankingView = :IsRankingView,
            gestionTables = :gestionTables,
            gestionArbitres = :gestionArbitres
        WHERE id = :idTournoi");

    $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->bindParam(':nom', $nom);
    $stmt->bindParam(':nb_terrains', $nb_terrains);
    $stmt->bindParam(':heure_debut', $heure_debut);
    $stmt->bindParam(':isClassement', $isClassement, PDO::PARAM_INT);
    $stmt->bindParam(':idParent', $idParent, PDO::PARAM_INT);
    $stmt->bindParam(':pasHoraire', $pasHoraire, PDO::PARAM_INT);
    $stmt->bindParam(':heure_fin', $heure_fin);
    $stmt->bindParam(':isVisible', $isVisible, PDO::PARAM_INT);
    $stmt->bindParam(':heureIsVisible', $heureIsVisible, PDO::PARAM_INT);
    $stmt->bindParam(':isArchived', $isArchived, PDO::PARAM_INT);
    $stmt->bindParam(':IsRankingView', $IsRankingView, PDO::PARAM_INT);
    $stmt->bindParam(':gestionTables', $gestionTable, PDO::PARAM_INT);
    $stmt->bindParam(':gestionArbitres', $gestionArbitres, PDO::PARAM_INT);
    $stmt->execute();
   
}

public function pourcentageRencontresTermineesDuTournoi(int $idTournoi): int {
    $stmt = $this->connexion->prepare("
        SELECT
            (COUNT(CASE WHEN score1 IS NOT NULL AND score2 IS NOT NULL THEN 1 END) / COUNT(*)) * 100 AS pourcentage_termine
        FROM Rencontres
        WHERE tournoi_id = :idTournoi
    ");

    $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pourcentage = round($result['pourcentage_termine']);
    return intval($pourcentage);
}




public function statsTournoi(int $idTournoi) {
    $stmt = $this->connexion->prepare("SELECT 
        c.id AS ClubID,
        c.nom AS ClubNom,
        SUM(CASE WHEN e.id = r.equipe1_id THEN r.score1 WHEN e.id = r.equipe2_id THEN r.score2 END) AS ButsMarques,
        SUM(CASE WHEN (e.id = r.equipe1_id AND r.score1 > r.score2) OR (e.id = r.equipe2_id AND r.score2 > r.score1) THEN 1 ELSE 0 END) AS Victoires,
        SUM(CASE WHEN e.id = r.equipe1_id THEN r.score2 WHEN e.id = r.equipe2_id THEN r.score1 END) AS ButsEncaisses
    FROM 
        Clubs c
    JOIN 
        Equipes e ON c.id = e.club_id
    JOIN 
        Rencontres r ON r.equipe1_id = e.id OR r.equipe2_id = e.id
    WHERE 
        r.tournoi_id = :idTournoi
    GROUP BY 
        c.id, c.nom
    ORDER BY Victoires DESC");

    $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function effacertournoi($idTournoi){
    //Effacer les rencontres where tournoi_id = $idTournoi

    //obtenir le ou les id de la table Poule where tournoi_id= $tournoi_id

    // effacer les enregistrements de la table EquipePoule where poule_id = la liste des id obtenue precedement

    //Effacer Equipes where tournoi_id = $idTournoi

}







    




}
?>