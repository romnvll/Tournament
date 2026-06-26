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


public function toggleAfficherCountdownCoach(int $tournoiId, bool $activer): bool {
    $stmt = $this->connexion->prepare("
        UPDATE Tournois SET afficherCountdownCoach = :val WHERE id = :id
    ");
    $stmt->execute([
        ':val' => $activer ? 1 : 0,
        ':id'  => $tournoiId
    ]);
    return $stmt->rowCount() > 0;
}


public function ajouterTournoi(string $nom, string $dateDebut, int $nb_terrains, string $heure_debut, int $isClassement, int $idUser, int $typeSportId, int $pasHoraire = 0): int {
    try {
        $this->connexion->beginTransaction(); // Début de la transaction

        $stmt = $this->connexion->prepare("
            INSERT INTO Tournois (nom, dateDebut, nb_terrains, heure_debut, pasHoraire, isClassement, utilisateur_id, type_sport_id) 
            VALUES (:nom, :dateDebut, :nb_terrains, :heure_debut, :pasHoraire, :isClassement, :idUser, :typeSportId)
        ");
        
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':dateDebut', $dateDebut);
        $stmt->bindParam(':nb_terrains', $nb_terrains);
        $stmt->bindParam(':heure_debut', $heure_debut);
        $stmt->bindParam(':pasHoraire', $pasHoraire, PDO::PARAM_INT);
        $stmt->bindParam(':isClassement', $isClassement, PDO::PARAM_INT);
        $stmt->bindParam(':idUser', $idUser, PDO::PARAM_INT);
        $stmt->bindParam(':typeSportId', $typeSportId, PDO::PARAM_INT);

        $stmt->execute();

        $id = (int) $this->connexion->lastInsertId(); // Récupération de l'ID

        $this->connexion->commit(); // Validation de la transaction
        
        return $id;
    } catch (Exception $e) {
        $this->connexion->rollBack(); // Annulation en cas d'erreur
        throw $e;
    }
}


/**
 * Met à jour le téléphone et le commentaire d'un tournoi
 * @param int $idTournoi ID du tournoi
 * @param string|null $telephone Numéro de téléphone
 * @param string|null $commentaire Commentaire libre
 * @return bool true si la mise à jour a réussi
 */
public function modifierTelephoneCommentaire(int $idTournoi, ?string $telephone, ?string $commentaire): bool {
    $stmt = $this->connexion->prepare("
        UPDATE Tournois 
        SET telephone = :telephone, commentaire = :commentaire 
        WHERE id = :id
    ");
    $stmt->execute([
        ':telephone' => $telephone,
        ':commentaire' => $commentaire,
        ':id' => $idTournoi
    ]);
    return $stmt->rowCount() > 0;
}

public function gestionTempChangement(int $idTournoi, bool $gestionTempsChangement): void {
        $stmt = $this->connexion->prepare("
            UPDATE Tournois 
            SET gestionTempsChangement = :gestionTempsChangement 
            WHERE id = :idTournoi
        ");
        $stmt->bindParam(':gestionTempsChangement', $gestionTempsChangement, PDO::PARAM_BOOL);
        $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->execute();
    }
    public function modifierTempsChangement(
    int $idTournoi,
    int $tempsChangementMinutes
): void {

    $stmt = $this->connexion->prepare("
        UPDATE Tournois
        SET tempsChangementMinutes = :temps
        WHERE id = :idTournoi
    ");

    $stmt->bindParam(':temps', $tempsChangementMinutes, PDO::PARAM_INT);
    $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);

    $stmt->execute();
}





    public function supprimerTournoi(int $id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Tournois WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

     public function getTournoisEnCours() {
    $sql = "
        SELECT 
            t.id, 
            t.nom, 
            t.dateDebut,
            u.nom AS nom_utilisateur,
            u.prenom AS prenom_utilisateur,
            u.email AS email_utilisateur
        FROM Tournois t
        INNER JOIN Utilisateurs u ON t.utilisateur_id = u.id
        WHERE t.isArchived = 0
        ORDER BY t.dateDebut DESC
    ";

    $stmt = $this->connexion->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function afficherLesTournois(int $utilisateur_id) : array {
        $stmt = $this->connexion->prepare("
            SELECT
                t.*,
                COUNT(e.id) AS nombre_equipes
            FROM
                Tournois t
            LEFT JOIN
                Equipes e ON t.id = e.tournoi_id
            WHERE
                t.utilisateur_id = :utilisateur_id
            GROUP BY
                t.id
        ");
        $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function afficherTousLesTournois() : array {
        $stmt = $this->connexion->prepare("
           SELECT
    t.*,
    tds.nom AS nom_type_sport,
    COUNT(e.id) AS nombre_equipes
FROM
    Tournois t
LEFT JOIN
    Equipes e ON t.id = e.tournoi_id
LEFT JOIN
    TypeDeSport tds ON t.type_sport_id = tds.id
GROUP BY
    t.id;

        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function droitTournoiClub(?int $tournoiId, int $utilisateur_id) : ?array {
    // Si aucun tournoiId n'est fourni, on retourne null directement
    if ($tournoiId === null) {
        return null;
    }

    $stmt = $this->connexion->prepare("
        SELECT 
            t.*, 
            COUNT(e.id) AS nombre_equipes
        FROM 
            Tournois t
        LEFT JOIN 
            Equipes e ON t.id = e.tournoi_id
        WHERE 
            t.id = :tournoiId 
            AND t.utilisateur_id = :utilisateur_id
        GROUP BY 
            t.id
    ");
    
    $stmt->bindParam(':tournoiId', $tournoiId, PDO::PARAM_INT);
    $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    
    $stmt->execute();
    $tournoi = $stmt->fetch();

    return $tournoi ?: null; // Retourne null si aucun tournoi trouvé
}

    
    

   public function afficherLesTournoisDeClassement() : array {
       
    $stmt = $this->connexion->prepare("select * FROM Tournois where isClassement = 1  ");
    
    $stmt->execute();
   $tounois=$stmt->fetchAll();
   return $tounois;
    
}


public function afficherLesTournoisQuiNeSontPasClassement(int $tournoiId) : array {
       
    $stmt = $this->connexion->prepare("select * FROM Tournois where isClassement = 0 and id = :tournoiId ");
    $stmt->bindParam(':tournoiId', $tournoiId, PDO::PARAM_INT);
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
    $stmt = $this->connexion->prepare("
        SELECT 
            t.*, 
            tds.nom AS nom_type_sport,
            u.nom AS nom_utilisateur,
            u.prenom AS prenom_utilisateur,
            u.email AS email_utilisateur,
            c.id AS club_id,
            c.nom AS nom_club,
            c.logo AS logo_club,
            c.type_sport_id AS type_sport_club
        FROM 
            Tournois t
        LEFT JOIN 
            TypeDeSport tds ON t.type_sport_id = tds.id
        LEFT JOIN
            Utilisateurs u ON t.utilisateur_id = u.id
        LEFT JOIN
            Clubs c ON u.club_id = c.id
        WHERE 
            t.id = :id
    ");

    $stmt->bindValue(':id', $id_tournoi, PDO::PARAM_INT);
    $stmt->execute();
    $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);
    return $tournoi ? $tournoi : [];
}
//RRRRRRRRRRRRRRRRRRR

public function getCategoriesPourTournoi(int $idTournoi) {
    // Requête pour récupérer les catégories distinctes pour un tournoi spécifié
    $query = "SELECT DISTINCT p.nom 
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

public function getTotalButsTournoi(int $idTournoi): int {
    $query = "
        SELECT
            COALESCE(SUM(r.score1), 0) + COALESCE(SUM(r.score2), 0) AS total_buts
        FROM
            Rencontres r
        JOIN Equipes e1 ON r.equipe1_id = e1.id
        JOIN Equipes e2 ON r.equipe2_id = e2.id
        WHERE
            e1.tournoi_id = :idTournoi AND e2.tournoi_id = :idTournoi
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$result['total_buts'];
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


public function getClassementParCategorie(int $idTournoi)
{
    // Récupération des catégories
    $query = "SELECT DISTINCT c.id_categorie, c.Nom_categorie
              FROM Categorie c 
              JOIN Equipes e ON e.categorie = c.id_categorie
              WHERE e.tournoi_id = :idTournoi";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $classements = [];

    foreach ($categories as $categorie) {

        $query = "
        SELECT
            e.id,
            e.nom,

            COUNT(r.id) AS matchsJoues,

            SUM(CASE 
                WHEN (e.id = r.equipe1_id AND r.score1 > r.score2)
                  OR (e.id = r.equipe2_id AND r.score2 > r.score1)
                THEN 1 ELSE 0 END) AS rencontresGagnees,

            SUM(CASE 
                WHEN r.score1 = r.score2 THEN 1 ELSE 0 END) AS matchsNuls,

            SUM(CASE 
                WHEN (e.id = r.equipe1_id AND r.score1 < r.score2)
                  OR (e.id = r.equipe2_id AND r.score2 < r.score1)
                THEN 1 ELSE 0 END) AS matchsPerdus,

            SUM(CASE 
                WHEN e.id = r.equipe1_id THEN r.score1
                WHEN e.id = r.equipe2_id THEN r.score2
                ELSE 0 END) AS nombreButsMarque,

            SUM(CASE 
                WHEN e.id = r.equipe1_id THEN r.score2
                WHEN e.id = r.equipe2_id THEN r.score1
                ELSE 0 END) AS nombreButsEncaisse,

            (
                -- 1 point par match joué
                COUNT(r.id)
                -- bonus victoire (+2)
                + SUM(CASE 
                    WHEN (e.id = r.equipe1_id AND r.score1 > r.score2)
                      OR (e.id = r.equipe2_id AND r.score2 > r.score1)
                    THEN 2 ELSE 0 END)
                -- bonus nul (+1)
                + SUM(CASE 
                    WHEN r.score1 = r.score2 THEN 1 ELSE 0 END)
            ) AS TotalDesPoints

        FROM Equipes e

        LEFT JOIN Rencontres r 
            ON (e.id = r.equipe1_id OR e.id = r.equipe2_id)
            AND r.type_rencontre_id = 1
            AND r.isTerminated = 1

        WHERE
            e.tournoi_id = :idTournoi
            AND e.categorie = :categorieId

        GROUP BY e.id, e.nom

        ORDER BY
            TotalDesPoints DESC,
            rencontresGagnees DESC,
            nombreButsMarque DESC,
            nombreButsEncaisse ASC
        ";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':categorieId', $categorie['id_categorie'], PDO::PARAM_INT);
        $stmt->execute();

        $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $classements[] = [
            'categorie_id' => $categorie['id_categorie'],
            'categorie_nom' => $categorie['Nom_categorie'],
            'equipes' => $equipes
        ];
    }

    return $classements;
}
  /**
 * Active ou désactive les effets sonores pour un utilisateur
 * @param int $userId ID de l'utilisateur
 * @param bool $activer true pour activer, false pour désactiver
 * @return bool true si la mise à jour a réussi
 */
public function toggleEffetsSonores(int $tournoiId, bool $activer): bool {
    $stmt = $this->connexion->prepare("UPDATE Tournois SET effetsSonores = :effetsSonores WHERE id = :id");
    $stmt->execute([
        ':effetsSonores' => $activer ? 1 : 0,
        ':id' => $tournoiId
    ]);
    return $stmt->rowCount() > 0;
}





public function modifierTournoi(
    int $idTournoi, 
    ?string $nom = null,      
    ?string $heure_debut = null, 
    ?int $isClassement = null, 
    ?int $pasHoraire = null, 
    ?int $isVisible = null, 
    ?int $heureIsVisible = null, 
    ?int $isArchived = null, 
    ?int $IsRankingView = null, 
    ?int $gestionTable = null, 
    ?int $gestionArbitres = null,
    ?int $refreshClientTime = null,
    ?int $gestionRepas = null,
    ?int $gestionPartenaires = null,
    ?int $gestionVoix = null,
    ?int $gestionInformations = null,
): void {
    $fields = [];
    $params = [':idTournoi' => $idTournoi];

    if ($nom !== null) {
        $fields[] = "nom = :nom";
        $params[':nom'] = $nom;
    }

    if ($heure_debut !== null) {
        $fields[] = "heure_debut = :heure_debut";
        $params[':heure_debut'] = $heure_debut;
    }
    if ($isClassement !== null) {
        $fields[] = "isClassement = :isClassement";
        $params[':isClassement'] = $isClassement;
    }
    if ($pasHoraire !== null) {
        $fields[] = "pasHoraire = :pasHoraire";
        $params[':pasHoraire'] = $pasHoraire;
    }
    if ($isVisible !== null) {
        $fields[] = "isVisible = :isVisible";
        $params[':isVisible'] = $isVisible;
    }
    if ($heureIsVisible !== null) {
        $fields[] = "heureIsVisible = :heureIsVisible";
        $params[':heureIsVisible'] = $heureIsVisible;
    }
    if ($isArchived !== null) {
        $fields[] = "isArchived = :isArchived";
        $params[':isArchived'] = $isArchived;
    }
    if ($IsRankingView !== null) {
        $fields[] = "IsRankingView = :IsRankingView";
        $params[':IsRankingView'] = $IsRankingView;
    }
    if ($gestionTable !== null) {
        $fields[] = "gestionTables = :gestionTables";
        $params[':gestionTables'] = $gestionTable;
    }
    if ($gestionArbitres !== null) {
        $fields[] = "gestionArbitres = :gestionArbitres";
        $params[':gestionArbitres'] = $gestionArbitres;
    }
     if ($gestionVoix !== null) {
        $fields[] = "gestionVoix = :gestionVoix";
        $params[':gestionVoix'] = $gestionVoix;
    }

    // Champs ajoutés : gestionRepas et gestionPartenaires
    if ($gestionRepas !== null) {
        $fields[] = "gestionRepas = :gestionRepas";
        $params[':gestionRepas'] = $gestionRepas;
    }
    if ($gestionPartenaires !== null) {
        $fields[] = "gestionPartenaires = :gestionPartenaires";
        $params[':gestionPartenaires'] = $gestionPartenaires;
    }

    if ($gestionInformations !== null) {
        $fields[] = "gestionInformations = :gestionInformations";
        $params[':gestionInformations'] = $gestionInformations;
    }

    // Gestion de refreshClientTime : si null, on le met à 30000
    if ($refreshClientTime === null) {
        $refreshClientTime = 30000;
    }
    $fields[] = "refreshClientTime = :refreshClientTime";
    $params[':refreshClientTime'] = $refreshClientTime;

    if (empty($fields)) {
        throw new Exception("Aucun champ à mettre à jour.");
    }

    $sql = "UPDATE Tournois SET " . implode(", ", $fields) . " WHERE id = :idTournoi";
    $stmt = $this->connexion->prepare($sql);

    foreach ($params as $param => $value) {
        if (is_int($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($param, $value, PDO::PARAM_STR);
        }
    }

    $stmt->execute();
}



public function pourcentageRencontresTermineesDuTournoi(int $idTournoi): int {
    $stmt = $this->connexion->prepare("
        SELECT
            (COUNT(CASE WHEN r.score1 IS NOT NULL AND r.score2 IS NOT NULL THEN 1 END) / COUNT(*)) * 100 AS pourcentage_termine
        FROM Rencontres r
        INNER JOIN Planification p ON r.id = p.rencontre_id
        WHERE r.tournoi_id = :idTournoi
    ");

    $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pourcentage = round($result['pourcentage_termine']);
    return intval($pourcentage);
}



public function rencontresPlanifieeDuTournoi(int $idTournoi): int {
   
    $stmt = $this->connexion->prepare("
        SELECT
            COUNT(*) AS rencontres_planifiees
        FROM Planification p
        INNER JOIN Rencontres r ON p.rencontre_id = r.id
        WHERE r.tournoi_id = :idTournoi
    ");

    $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return intval($result['rencontres_planifiees']);
}

public function nombreRencontreAPlanifier (int $idTournoi): int {
    $stmt = $this->connexion->prepare("
        SELECT
            COUNT(*) AS rencontres_a_planifier
        FROM Rencontres r
        WHERE r.tournoi_id = :idTournoi
    ");

    $stmt->bindParam(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return intval($result['rencontres_a_planifier']);
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