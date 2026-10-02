<?php
  const TYPE_RENCONTRE_POULE        = 1;
const TYPE_RENCONTRE_PHASE_FINALE = 2;
const TYPE_RENCONTRE_CLASSEMENT   = 3;
const TYPE_RENCONTRE_AMICAL       = 4;


class PouleManager {
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
 * Compte le nombre de poules initiales (is_classement = 0) d'une catégorie
 */
public function compterPoulesInitiales(int $tournoiId, int $categorieId): int {
    $stmt = $this->connexion->prepare("
        SELECT COUNT(*) AS nb_poules
        FROM Poules
        WHERE tournoi_id = :tournoiId
          AND fk_idcategorie = :categorieId
          AND is_classement = 0
    ");
    $stmt->execute([
        ':tournoiId' => $tournoiId,
        ':categorieId' => $categorieId
    ]);
    return (int) $stmt->fetchColumn();
}


    /**
 * Compte le nombre total d'équipes dans les poules initiales d'une catégorie
 */
public function compterTotalEquipesByCategorie(int $tournoiId, int $categorieId): int {
    $stmt = $this->connexion->prepare("
        SELECT COUNT(DISTINCT ep.equipe_id) AS total_equipes
        FROM EquipePoule ep
        JOIN Poules p ON ep.poule_id = p.id
        WHERE p.tournoi_id = :tournoiId 
          AND p.fk_idcategorie = :categorieId 
          AND p.is_classement = 0
    ");
    $stmt->execute([
        ':tournoiId' => $tournoiId,
        ':categorieId' => $categorieId
    ]);
    return (int) $stmt->fetchColumn();
}

    public function createPoule($nomPoule, $idTournoi, $categorie, $is_classement = 0) {
        // Utiliser la méthode pouleExists pour vérifier si la poule existe déjà
        //if ($this->pouleExists($nomPoule, $idTournoi)) {
            // La poule existe déjà, donc on ne procède pas à l'insertion
         //   return false;
        //}
    
        // Insérer la nouvelle poule
        $query = "INSERT INTO Poules (nom, tournoi_id, fk_idcategorie, is_classement) VALUES (:nom, :idTournoi, :fk_idcategorie, :is_classement)";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':nom', $nomPoule);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':fk_idcategorie', $categorie, PDO::PARAM_INT);
        $stmt->bindValue(':is_classement', $is_classement, PDO::PARAM_INT);
        $stmt->execute();
    
        return true;
    }
    
    
    

    public function getPouleById(int $idPoule) {
        $query = "SELECT * FROM Poules WHERE id = :id";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':id', $idPoule, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


  public function checkRencontresInPoule(int $idPoule, int $typeRencontreId = TYPE_RENCONTRE_POULE) {
    $query = "SELECT COUNT(*) as count FROM Rencontres r
              JOIN EquipePoule ep ON r.equipe1_id = ep.equipe_id OR r.equipe2_id = ep.equipe_id
              WHERE ep.poule_id = :id AND r.type_rencontre_id = :typeRencontreId";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':id', $idPoule, PDO::PARAM_INT);
    $stmt->bindValue(':typeRencontreId', $typeRencontreId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] / 2 > 0;
}

public function getCategorieNom(int $categorieId): string {
    $stmt = $this->connexion->prepare("
        SELECT Nom_categorie FROM Categorie 
        WHERE id_categorie = :categorieId
    ");
    $stmt->bindValue(':categorieId', $categorieId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() ?: 'Catégorie inconnue';
}


/**
 * Génère automatiquement les poules "Haute" / "Basse".
 * Si createPouleOnly = 1 : crée uniquement les poules.
 * Si createPouleOnly = 0 : crée les poules + affecte les équipes.
 */
public function genererPoulesHauteBasseAutomatique(
    int $tournoiId,
    int $categorieId,
    int $createPouleOnly = 0
): array {

    $stmt = $this->connexion->prepare("
        SELECT id FROM Poules 
        WHERE tournoi_id = :tournoiId 
          AND fk_idcategorie = :categorieId 
          AND is_classement = 0
    ");

    $stmt->execute([
        ':tournoiId' => $tournoiId,
        ':categorieId' => $categorieId
    ]);

    $poulesIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($poulesIds)) {
        return [];
    }

    // Étape 1 : Récupérer TOUS les classements et les fusionner
    $equipesHauteGlobale = [];
    $equipesBassaGlobale = [];

    foreach ($poulesIds as $pouleSourceId) {
        $classement = $this->getClassementPoule($pouleSourceId);
        $nbEquipes  = count($classement);

        if ($nbEquipes === 0) {
            continue;
        }

        $nbHaute = (int) ceil($nbEquipes / 2);

        $equipesHaute = array_slice($classement, 0, $nbHaute);
        $equipesBasse = array_slice($classement, $nbHaute);

        // Fusionner dans les listes globales
        $equipesHauteGlobale = array_merge($equipesHauteGlobale, $equipesHaute);
        $equipesBassaGlobale = array_merge($equipesBassaGlobale, $equipesBasse);
    }

    if (empty($equipesHauteGlobale) && empty($equipesBassaGlobale)) {
        return [];
    }

    // Étape 2 : Créer UNE SEULE poule Haute et UNE SEULE poule Basse
    $poulesCreees = [];

    foreach ([
        'Haute' => $equipesHauteGlobale,
        'Basse' => $equipesBassaGlobale,
    ] as $suffixe => $equipes) {

        if (empty($equipes)) {
            continue;
        }

        $nomCategorie = $this->getCategorieNom($categorieId); // À implémenter si nécessaire
        $nomPouleClassement = "{$nomCategorie} {$suffixe}";

        $pouleId = $this->creerOuRecupererPouleClassement(
            $tournoiId,
            $categorieId,
            $nomPouleClassement
        );

        if ($createPouleOnly == 0) {
            foreach ($equipes as $equipe) {
                try {
                    $this->addEquipeToPoule(
                        $equipe['id'],
                        $pouleId,
                        $tournoiId
                    );
                } catch (Exception $e) {
                    // déjà dans la poule
                }
            }
        }

        $poulesCreees[$nomPouleClassement] = [
            'pouleId' => $pouleId,
            'nom'     => $nomPouleClassement,
            'equipes' => ($createPouleOnly == 0)
                ? array_column($equipes, 'id')
                : []
        ];
    }

    return $poulesCreees;
}
    


    public function supprimerLienEquipePoule($equipeId, $pouleId) {
        $query = "DELETE FROM EquipePoule WHERE equipe_id = :equipeId AND poule_id = :pouleId";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':equipeId', $equipeId, PDO::PARAM_INT);
        $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            // Gérer l'exception ou la logger
            return false;
        }
    }

/**
 * Récupère le classement d'une poule (phase initiale, type 1)
 */
public function getClassementPoule(int $pouleId): array {
    $query = "
        SELECT * FROM (
            SELECT 
                e.id,
                e.nom,
                e.categorie,
                SUM(CASE
                    WHEN r.equipe1_id = e.id AND r.score1 > r.score2 THEN 3
                    WHEN r.equipe2_id = e.id AND r.score2 > r.score1 THEN 3
                    WHEN r.score1 IS NOT NULL AND r.score1 = r.score2 THEN 2
                    ELSE 1
                END) AS points,
                COALESCE(SUM(CASE WHEN r.equipe1_id = e.id THEN r.score1
                                  WHEN r.equipe2_id = e.id THEN r.score2 END), 0) AS buts_pour,
                COALESCE(SUM(CASE WHEN r.equipe1_id = e.id THEN r.score2
                                  WHEN r.equipe2_id = e.id THEN r.score1 END), 0) AS buts_contre
            FROM Equipes e
            JOIN EquipePoule ep ON ep.equipe_id = e.id
            LEFT JOIN Rencontres r ON (r.equipe1_id = e.id OR r.equipe2_id = e.id)
                                   AND r.type_rencontre_id = :typePoule
                                   AND r.score1 IS NOT NULL
            WHERE ep.poule_id = :pouleId
            GROUP BY e.id, e.nom, e.categorie
        ) AS classement
        ORDER BY points DESC, (buts_pour - buts_contre) DESC, buts_pour DESC
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
    $stmt->bindValue(':typePoule', TYPE_RENCONTRE_POULE, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Crée ou récupère une poule de classement existante
 */
public function creerOuRecupererPouleClassement(int $tournoiId, int $categorieId, string $nom): int {
    $stmt = $this->connexion->prepare("
        SELECT id FROM Poules 
        WHERE nom = :nom 
          AND tournoi_id = :tournoiId 
          AND fk_idcategorie = :categorieId 
          AND is_classement = 1
    ");
    $stmt->execute([':nom' => $nom, ':tournoiId' => $tournoiId, ':categorieId' => $categorieId]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        return (int) $existing;
    }

    $stmt = $this->connexion->prepare("
        INSERT INTO Poules (nom, tournoi_id, fk_idcategorie, is_classement)
        VALUES (:nom, :tournoiId, :categorieId, 1)
    ");
    $stmt->execute([':nom' => $nom, ':tournoiId' => $tournoiId, ':categorieId' => $categorieId]);
    return (int) $this->connexion->lastInsertId();
}

/**
 * Génère le nom de la poule selon le rang et le nombre total de poules.
 * Exemple : rang=1, nbPoules=3 → "1-2-3ème place"
 *           rang=2, nbPoules=3 → "4-5-6ème place"
 */
private function nomPouleClassement(int $rang, int $nbPoules): string {
    // Les places disputées dans cette poule de classement
    $debut = ($rang - 1) * $nbPoules + 1;
    $fin   = $rang * $nbPoules;

    if ($debut === $fin) {
        return "{$debut}ème place";
    }

    // Construire "1-2-3ème place"
    $places = implode('-', range($debut, $fin));
    return "{$places}ème place";
}

/**
 * Retourne le classement général final pour une catégorie.
 * Les équipes sont groupées par poule de classement (1-2ème place, 3-4ème place...),
 * et triées par points à l'intérieur de chaque poule.
 */
public function getClassementFinal(int $tournoiId, int $categorieId): array {
    $query = "
        SELECT
            e.id,
            e.nom,
            cl.id AS club_id,
            cl.logo AS club_logo,
            p.id AS poule_id,
            p.nom AS poule_nom,
            SUM(CASE
                WHEN r.equipe1_id = e.id AND r.score1 > r.score2 THEN 3
                WHEN r.equipe2_id = e.id AND r.score2 > r.score1 THEN 3
                WHEN r.score1 IS NOT NULL AND r.score1 = r.score2 THEN 2
                WHEN r.score1 IS NULL and r.score2 IS NULL THEN 0
                ELSE 1
            END) AS TotalDesPoints,
            COALESCE(SUM(CASE 
                WHEN r.equipe1_id = e.id THEN r.score1
                WHEN r.equipe2_id = e.id THEN r.score2 
            END), 0) AS nombreButsMarque,
            COALESCE(SUM(CASE 
                WHEN r.equipe1_id = e.id THEN r.score2
                WHEN r.equipe2_id = e.id THEN r.score1 
            END), 0) AS nombreButsEncaisse,
            COALESCE(SUM(CASE 
                WHEN r.equipe1_id = e.id THEN r.score1 - r.score2
                WHEN r.equipe2_id = e.id THEN r.score2 - r.score1 
            END), 0) AS DifferenceButs
        FROM Equipes e
        JOIN EquipePoule ep ON ep.equipe_id = e.id
        JOIN Poules p ON p.id = ep.poule_id
        JOIN Clubs cl ON cl.id = e.club_id
        LEFT JOIN Rencontres r ON (r.equipe1_id = e.id OR r.equipe2_id = e.id)
                               AND r.type_rencontre_id = :typeClassement
                               AND r.score1 IS NOT NULL
        WHERE p.tournoi_id = :tournoiId
          AND p.fk_idcategorie = :categorieId
          AND p.is_classement = 1
        GROUP BY e.id, e.nom, cl.id, cl.logo, p.id, p.nom
        ORDER BY
         p.id ASC, 
           --CAST(SUBSTRING_INDEX(p.nom, '-', 1) AS UNSIGNED) ASC,
            TotalDesPoints DESC, 
            nombreButsMarque DESC,
            DifferenceButs DESC
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
    $stmt->bindValue(':categorieId', $categorieId, PDO::PARAM_INT);
    $stmt->bindValue(':typeClassement', TYPE_RENCONTRE_CLASSEMENT, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



/**
 * Génère automatiquement les poules de classement pour une catégorie.
 *
 * @param bool $createPouleOnly
 *      false : crée les poules et y ajoute les équipes.
 *      true  : crée uniquement les poules.
 */
public function genererPoulesClassementAutomatique(
    int $tournoiId,
    int $categorieId,
    bool $createPouleOnly = false
): array {

    $stmt = $this->connexion->prepare("
        SELECT id FROM Poules
        WHERE tournoi_id = :tournoiId
          AND fk_idcategorie = :categorieId
          AND is_classement = 0
    ");

    $stmt->execute([
        ':tournoiId' => $tournoiId,
        ':categorieId' => $categorieId
    ]);

    $poulesIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($poulesIds)) {
        return [];
    }

    $nbPoules = count($poulesIds);

    // ───────────────────────────────────────────────────────────────
    // CAS : UNE SEULE POULE
    // ───────────────────────────────────────────────────────────────
    if ($nbPoules === 1) {

        $classement = $this->getClassementPoule($poulesIds[0]);
        $poulesCreees = [];

        $chunks = array_chunk($classement, 2);

        foreach ($chunks as $index => $paire) {

            $rang1 = $index * 2 + 1;
            $rang2 = min($rang1 + 1, count($classement));

            if ($rang1 === $rang2) {
                break;
            }

            $nomPoule = "{$rang1}-{$rang2}ème place";

            $pouleId = $this->creerOuRecupererPouleClassement(
                $tournoiId,
                $categorieId,
                $nomPoule
            );

            if (!$createPouleOnly) {

                foreach ($paire as $equipe) {
                    try {
                        $this->addEquipeToPoule(
                            $equipe['id'],
                            $pouleId,
                            $tournoiId
                        );
                    } catch (Exception $e) {
                        // déjà dans la poule
                    }
                }

            }

            $poulesCreees[$rang1] = [
                'pouleId' => $pouleId,
                'nom'     => $nomPoule,
                'equipes' => $createPouleOnly
                    ? []
                    : array_column($paire, 'id'),
            ];
        }

        return $poulesCreees;
    }

    // ───────────────────────────────────────────────────────────────
    // CAS : PLUSIEURS POULES
    // ───────────────────────────────────────────────────────────────

    $equipesByRang = [];

    foreach ($poulesIds as $pouleId) {

        $classement = $this->getClassementPoule($pouleId);

        foreach ($classement as $rang => $equipe) {
            $equipesByRang[$rang + 1][] = $equipe['id'];
        }
    }

    ksort($equipesByRang);

    $poulesCreees = [];

    foreach ($equipesByRang as $rang => $equipes) {

        $nomPoule = $this->nomPouleClassement($rang, $nbPoules);

        $pouleId = $this->creerOuRecupererPouleClassement(
            $tournoiId,
            $categorieId,
            $nomPoule
        );

        if (!$createPouleOnly) {

            foreach ($equipes as $equipeId) {

                try {
                    $this->addEquipeToPoule(
                        $equipeId,
                        $pouleId,
                        $tournoiId
                    );
                } catch (Exception $e) {
                    // déjà dans la poule
                }

            }

        }

        $poulesCreees[$rang] = [
            'pouleId' => $pouleId,
            'nom'     => $nomPoule,
            'equipes' => $createPouleOnly
                ? []
                : $equipes,
        ];
    }

    return $poulesCreees;
}

private function ordinalFr(int $n): string {
    $map = [1 => '1ers', 2 => '2èmes', 3 => '3èmes', 4 => '4èmes', 5 => '5èmes'];
    return $map[$n] ?? "{$n}èmes";
}






    public function deletePoule($idPoule) {
        try {
            // Commencer une transaction
            $this->connexion->beginTransaction();

            // Supprimer les liens dans EquipePoule
            $queryEquipePoule = "DELETE FROM EquipePoule WHERE poule_id = :idPoule";
            $stmtEquipePoule = $this->connexion->prepare($queryEquipePoule);
            $stmtEquipePoule->bindValue(':idPoule', $idPoule, PDO::PARAM_INT);
            $stmtEquipePoule->execute();

            // Supprimer la poule
            $queryPoule = "DELETE FROM Poules WHERE id = :idPoule";
            $stmtPoule = $this->connexion->prepare($queryPoule);
            $stmtPoule->bindValue(':idPoule', $idPoule, PDO::PARAM_INT);
            $stmtPoule->execute();

            // Valider la transaction
            $this->connexion->commit();

            return true;
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $this->connexion->rollBack();
            throw $e;
        }
    }


    public function getPoulesByEquipeId($equipeId) {
    
    // Requête pour obtenir les poules associées à une équipe spécifique
    $query = "SELECT Poules.* FROM Poules 
              JOIN EquipePoule ON Poules.id = EquipePoule.poule_id 
              JOIN Equipes ON EquipePoule.equipe_id = Equipes.id 
              WHERE Equipes.id = :equipeId";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':equipeId', $equipeId);
    $stmt->execute();

    $poules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$poules) {
        throw new Exception("Aucune poule trouvée pour l'équipe avec l'ID : " . $equipeId);
    }

    return $poules;
}




public function supprimerPoulesParTournoi(int $idTournoi): void {
    // Supprimer les relations dans EquipePoule liées aux poules du tournoi
    $queryDeleteEquipePoule = "DELETE FROM EquipePoule 
                                WHERE poule_id IN (SELECT id FROM Poules WHERE tournoi_id = :idTournoi)";
    
    $stmtDeleteEquipePoule = $this->connexion->prepare($queryDeleteEquipePoule);
    $stmtDeleteEquipePoule->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmtDeleteEquipePoule->execute();

    // Supprimer les poules du tournoi
    $queryDeletePoules = "DELETE FROM Poules WHERE tournoi_id = :idTournoi";
    
    $stmtDeletePoules = $this->connexion->prepare($queryDeletePoules);
    $stmtDeletePoules->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmtDeletePoules->execute();
}



public function getAllPoulesByTournoi(int $idTournoi, $AndIsClassement = false) {

    if ($AndIsClassement === true) {
        $query = "SELECT p.*, c.Nom_categorie, COUNT(ep.equipe_id) AS nombre_equipes
        FROM Poules p
        LEFT JOIN EquipePoule ep ON p.id = ep.poule_id
        LEFT JOIN Categorie c ON p.fk_idcategorie = c.id_categorie
        WHERE p.tournoi_id = :idTournoi
        GROUP BY p.id
        ORDER BY p.nom";
    } else {
        $query = "SELECT p.*, c.Nom_categorie, COUNT(ep.equipe_id) AS nombre_equipes
        FROM Poules p
        LEFT JOIN EquipePoule ep ON p.id = ep.poule_id
        LEFT JOIN Categorie c ON p.fk_idcategorie = c.id_categorie
        WHERE p.tournoi_id = :idTournoi AND p.is_classement = '0'
        GROUP BY p.id
        ORDER BY p.nom";
    }

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':idTournoi', $idTournoi);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    public function addEquipeToPoule(int $equipeId, int $pouleId, int $tournoiId, bool $isMatchRetour = false) {

    // Vérifier si l'équipe est déjà dans la poule
    $queryCheck = "SELECT * FROM EquipePoule WHERE equipe_id = :equipeId AND poule_id = :pouleId";
    $stmtCheck = $this->connexion->prepare($queryCheck);
    $stmtCheck->bindValue(':equipeId', $equipeId);
    $stmtCheck->bindValue(':pouleId', $pouleId);
    $stmtCheck->execute();

    if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("L'équipe est déjà dans cette poule.");
    }

    // Insérer l'équipe dans la poule
    $queryInsert = "INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:equipeId, :pouleId)";
    $stmtInsert = $this->connexion->prepare($queryInsert);
    $stmtInsert->bindValue(':equipeId', $equipeId);
    $stmtInsert->bindValue(':pouleId', $pouleId);

    if (!$stmtInsert->execute()) {
        return false;
    }

    // Déterminer si c'est une poule de classement, pour choisir le bon type de rencontre
    $poule = $this->getPouleById($pouleId);
    $typeRencontreId = (!empty($poule['is_classement']) && $poule['is_classement'] == 1)
        ? TYPE_RENCONTRE_CLASSEMENT
        : TYPE_RENCONTRE_POULE;

    // Générer automatiquement les rencontres manquantes pour cette équipe
     // adapte le chemin/nom de fichier réel
    $rencontreDAO = new RencontreDAO();
    $rencontreDAO->addEquipesToPoule(
        $pouleId,
        $tournoiId,
        [$equipeId],
        $typeRencontreId,
        $isMatchRetour
    );

    return true;
}
    
    
    

    public function getAllPoulesFinalesByTournoi(int $idTournoi) {
    $query = "
        SELECT p.*, c.Nom_categorie
        FROM Poules p
        INNER JOIN Categorie c ON p.fk_idcategorie = c.id_categorie
        WHERE p.tournoi_id = :idTournoi
        AND p.is_classement = '1'
        ORDER BY c.Nom_categorie, p.nom
    ";
    $stmt = $this->connexion->prepare($query);
    
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


   public function getEquipesInPoule(int $idPoule) {
  
    $query = "SELECT
                e.id,
                e.nom,
                e.categorie,                    
                e.tournoi_id,
                ep.poule_id,
                e.club_id,
                ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id AND r.score1 > r.score2 AND r.type_rencontre_id = :typeClassement) OR (r.equipe2_id = e.id AND r.score2 > r.score1 AND r.type_rencontre_id = :typeClassement)) * 3) +
                ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id OR r.equipe2_id = e.id) AND r.type_rencontre_id = :typeClassement AND r.score1 = r.score2) * 2) +
                ((SELECT COUNT(*) FROM Rencontres r WHERE (r.equipe1_id = e.id AND r.score1 < r.score2 AND r.type_rencontre_id = :typeClassement) OR (r.equipe2_id = e.id AND r.score2 < r.score1 AND r.type_rencontre_id = :typeClassement))) AS TotalDesPoints,
                COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.type_rencontre_id = :typeClassement), 0) +
                COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.type_rencontre_id = :typeClassement), 0) AS nombreButsMarque,
                COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.type_rencontre_id = :typeClassement), 0) +
                COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.type_rencontre_id = :typeClassement), 0) AS nombreButsEncaisse,
                (COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.type_rencontre_id = :typeClassement), 0) +
                COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.type_rencontre_id = :typeClassement), 0)) -
                (COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.type_rencontre_id = :typeClassement), 0) +
                COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.type_rencontre_id = :typeClassement), 0)) AS DifferenceButs
              FROM
                Equipes e
              JOIN EquipePoule ep ON e.id = ep.equipe_id
              WHERE
                ep.poule_id = :pouleId
              ORDER BY
                TotalDesPoints DESC,
                nombreButsMarque DESC,
                DifferenceButs DESC";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':pouleId', $idPoule, PDO::PARAM_INT);
    $stmt->bindValue(':typeClassement', TYPE_RENCONTRE_CLASSEMENT, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    
    public function creerPoulesPourCategorie(int $idTournoi, int $idCategorie, int $nombreEquipesParPoule)
{
    // Récupérer le nom de la catégorie
    $stmtNomCategorie = $this->connexion->prepare("
        SELECT Nom_categorie
        FROM Categorie
        WHERE id_categorie = :idCategorie
    ");
    $stmtNomCategorie->bindValue(':idCategorie', $idCategorie);
    $stmtNomCategorie->execute();
    $nomCategorie = $stmtNomCategorie->fetchColumn();

    if (!$nomCategorie) {
        return [];
    }

    // Étape 1 : Récupérer les équipes avec leur club_id
    $stmtEquipes = $this->connexion->prepare("
        SELECT e.id, e.nom, e.club_id
        FROM Equipes e
        WHERE e.tournoi_id = :idTournoi AND e.categorie = :idCategorie
    ");
    $stmtEquipes->bindValue(':idTournoi', $idTournoi);
    $stmtEquipes->bindValue(':idCategorie', $idCategorie);
    $stmtEquipes->execute();
    $equipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

    if (empty($equipes)) {
        return [];
    }

    // Étape 2 : Distribuer les équipes en évitant les clubs en double dans une même poule
    $nbPoules = (int) ceil(count($equipes) / $nombreEquipesParPoule);
    $poules   = array_fill(0, $nbPoules, []);

    // 2a. Grouper par club, clubs les plus représentés en premier
    $parClub = [];
    foreach ($equipes as $equipe) {
        $clubId = $equipe['club_id'] ?? 'sans_club';
        $parClub[$clubId][] = $equipe;
    }
    usort($parClub, fn($a, $b) => count($b) - count($a));

    // 2b. Distribution en serpentin : 0,1,2,...,N,N,...,1,0,0,1,...
    $pouleIndex = 0;
    $direction  = 1;

    foreach ($parClub as $clubEquipes) {
        foreach ($clubEquipes as $equipe) {
            $poules[$pouleIndex][] = $equipe;

            $pouleIndex += $direction;
            if ($pouleIndex >= $nbPoules) {
                $direction  = -1;
                $pouleIndex = $nbPoules - 1;
            } elseif ($pouleIndex < 0) {
                $direction  = 1;
                $pouleIndex = 0;
            }
        }
    }

    // Étape 3 : Insérer ou mettre à jour les poules en base (inchangé)
    $allPouleIds = [];

    foreach ($poules as $index => $poule) {
        $nomPoule = "$nomCategorie - Poule " . ($index + 1);

        $stmtPoule = $this->connexion->prepare("
            SELECT id FROM Poules WHERE nom = :nomPoule AND tournoi_id = :idTournoi
        ");
        $stmtPoule->bindValue(':nomPoule', $nomPoule);
        $stmtPoule->bindValue(':idTournoi', $idTournoi);
        $stmtPoule->execute();
        $pouleId = $stmtPoule->fetchColumn();

        if ($pouleId) {
            $allPouleIds[] = $pouleId;

            $this->connexion->prepare("DELETE FROM EquipePoule WHERE poule_id = :pouleId")
                ->execute([':pouleId' => $pouleId]);

            foreach ($poule as $equipe) {
                $this->connexion->prepare("DELETE FROM EquipePoule WHERE equipe_id = :equipeId")
                    ->execute([':equipeId' => $equipe['id']]);

                $this->connexion->prepare("
                    INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:equipeId, :pouleId)
                ")->execute([':equipeId' => $equipe['id'], ':pouleId' => $pouleId]);
            }
        } else {
            $stmtInsertPoule = $this->connexion->prepare("
                INSERT INTO Poules (nom, is_classement, fk_idcategorie, tournoi_id) 
                VALUES (:nom, 0, :idCategorie, :idTournoi)
            ");
            $stmtInsertPoule->bindValue(':nom', $nomPoule);
            $stmtInsertPoule->bindValue(':idCategorie', $idCategorie);
            $stmtInsertPoule->bindValue(':idTournoi', $idTournoi);
            $stmtInsertPoule->execute();
            $newPouleId = $this->connexion->lastInsertId();

            $allPouleIds[] = $newPouleId;

            foreach ($poule as $equipe) {
                $this->connexion->prepare("DELETE FROM EquipePoule WHERE equipe_id = :equipeId")
                    ->execute([':equipeId' => $equipe['id']]);

                $this->connexion->prepare("
                    INSERT INTO EquipePoule (equipe_id, poule_id) VALUES (:equipeId, :pouleId)
                ")->execute([':equipeId' => $equipe['id'], ':pouleId' => $newPouleId]);
            }
        }
    }

    return $allPouleIds;
}





    public function afficherPoulesPourCategorie(int $idTournoi, int $nombreEquipesParPoule, int $idCategorie): array {
    // Étape 1 : Récupérer toutes les équipes avec leur club_id
    $stmt = $this->connexion->prepare("
        SELECT 
            e.id,
            e.isPresent, 
            e.nom AS equipe_nom, 
            e.club_id,
            c.nom AS club_nom, 
            c.logo AS club_logo
        FROM Equipes e
        INNER JOIN Clubs c ON e.club_id = c.id
        WHERE e.tournoi_id = :idTournoi
          AND e.categorie = :idCategorie
    ");
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->bindValue(':idCategorie', $idCategorie, PDO::PARAM_INT);
    $stmt->execute();
    $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalEquipes  = count($equipes);
    $nombrePoules  = (int) ceil($totalEquipes / $nombreEquipesParPoule);

    // Étape 2 : Distribution serpentin par club
    $poules = array_fill(0, $nombrePoules, ['equipes' => []]);
    for ($i = 0; $i < $nombrePoules; $i++) {
        $poules[$i]['nom'] = "Poule " . ($i + 1);
    }

    // 2a. Grouper par club, plus grand club en premier
    $parClub = [];
    foreach ($equipes as $equipe) {
        $parClub[$equipe['club_id']][] = $equipe;
    }
    usort($parClub, fn($a, $b) => count($b) - count($a));

    // 2b. Serpentin
    $pouleIndex = 0;
    $direction  = 1;

    foreach ($parClub as $clubEquipes) {
        foreach ($clubEquipes as $equipe) {
            $poules[$pouleIndex]['equipes'][] = $equipe;

            $pouleIndex += $direction;
            if ($pouleIndex >= $nombrePoules) {
                $direction  = -1;
                $pouleIndex = $nombrePoules - 1;
            } elseif ($pouleIndex < 0) {
                $direction  = 1;
                $pouleIndex = 0;
            }
        }
    }

    return $poules;
}






    
    
    
    
public function pouleExists(string $pouleNom, int $idTournoi): bool {
    
    $stmt = $this->connexion->prepare("SELECT COUNT(*) FROM Poules WHERE nom = :pouleNom AND tournoi_id = :idTournoi");
    $stmt->bindValue(':pouleNom', $pouleNom);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}



public function pouleHasRencontreProgrammee(int $pouleId, int $idTournoi): bool {
    $query = "
        SELECT COUNT(*) 
        FROM EquipePoule ep
        JOIN Rencontres r ON ep.equipe_id = r.equipe1_id OR ep.equipe_id = r.equipe2_id
        JOIN Planification p ON r.id = p.rencontre_id
        WHERE ep.poule_id = :pouleId
        AND p.tournoi_id = :idTournoi
        AND p.terrain_id IS NOT NULL
        AND p.creneau_id IS NOT NULL
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchColumn() > 0;
}

public function NbreRencontreParPouleProgrammee(int $pouleId, int $idTournoi): int {
    $query = "
        SELECT COUNT(*) 
        FROM Rencontres r
        JOIN Planification p ON r.id = p.rencontre_id
        WHERE r.poule_id = :pouleId
        AND p.tournoi_id = :idTournoi
        AND p.terrain_id IS NOT NULL
        AND p.creneau_id IS NOT NULL
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
    $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}


public function getDernierePouleIdParEquipe(int $equipeId): ?int {
    $query = "
        SELECT ep.poule_id
        FROM EquipePoule ep
        WHERE ep.equipe_id = :equipeId
        ORDER BY ep.poule_id DESC
        LIMIT 1
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':equipeId', $equipeId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchColumn() ?: null;
}

public function getPremierePouleIdParEquipe(int $equipeId): ?int {
    $query = "
        SELECT ep.poule_id
        FROM EquipePoule ep
        WHERE ep.equipe_id = :equipeId
        ORDER BY ep.poule_id ASC
        LIMIT 1
    ";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':equipeId', $equipeId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchColumn() ?: null;
}




public function compterEquipesParPoule(int $poule_id) {
    
    // Utilisez une requête SQL pour compter le nombre d'équipes dans la poule donnée
    $query = "SELECT COUNT(*) AS nombre_equipes FROM EquipePoule WHERE poule_id = :poule_id";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindParam(':poule_id', $poule_id, PDO::PARAM_INT);
    $stmt->execute();

    // Récupérez le résultat de la requête et retournez le nombre d'équipes
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int) $result['nombre_equipes'];
}

public function calculerNombreTours(int $nombre_equipes) {
    // Utilisez log2 pour calculer le nombre de tours
    return ceil(log($nombre_equipes, 2));
}

/**
 * Récupère les infos complètes d'une poule (équipes, rencontres, tours)
 */
public function getInfoPoule($poule_id) {
    // Compter le nombre d'équipes dans la poule donnée
    $queryEquipes = "SELECT COUNT(*) AS nombre_equipes FROM EquipePoule WHERE poule_id = :poule_id";
    $stmtEquipes = $this->connexion->prepare($queryEquipes);
    $stmtEquipes->bindParam(':poule_id', $poule_id, PDO::PARAM_INT);
    $stmtEquipes->execute();

    $resultEquipes = $stmtEquipes->fetch(PDO::FETCH_ASSOC);
    $nombre_equipes = (int) $resultEquipes['nombre_equipes'];

    if ($nombre_equipes === 0) {
        return array(
            'nombre_equipes' => 0,
            'nombre_rencontres' => 0,
            'nombre_tours' => 0,
            'nombre_rencontres_par_tour' => 0,
            'type_rencontre' => 'Aller simple'
        );
    }

    // Compter le nombre de rencontres de poule (type_rencontre_id = 1) dans cette poule
    // ✅ FILTRAGE PAR poule_id ET type_rencontre_id
    $queryRencontres = "
        SELECT COUNT(*) AS nombre_rencontres 
        FROM Rencontres r
        WHERE r.poule_id = :poule_id 
          AND r.type_rencontre_id = :typePoule
    ";
    $stmtRencontres = $this->connexion->prepare($queryRencontres);
    $stmtRencontres->bindParam(':poule_id', $poule_id, PDO::PARAM_INT);
    $stmtRencontres->bindValue(':typePoule', TYPE_RENCONTRE_POULE, PDO::PARAM_INT);
    $stmtRencontres->execute();

    $resultRencontres = $stmtRencontres->fetch(PDO::FETCH_ASSOC);
    $nombre_rencontres = (int) $resultRencontres['nombre_rencontres'];

    // Nombre de rencontres théorique en aller simple (round-robin)
    $rencontres_aller_simple = $nombre_equipes * ($nombre_equipes - 1) / 2;

    // Déterminer le type de rencontre
    $type_rencontre = ($nombre_rencontres == $rencontres_aller_simple) ? "Aller simple" : "Aller-retour";

    // Nombre de tours
    $nombre_toursAller = $nombre_equipes - 1;
    $nombre_tours = ($type_rencontre === "Aller-retour") 
        ? $nombre_toursAller * 2 
        : $nombre_toursAller;

    // Nombre de rencontres par tour
    $nombre_rencontres_par_tour = ceil($nombre_equipes / 2);

    return array(
        'nombre_equipes' => $nombre_equipes,
        'nombre_rencontres' => $nombre_rencontres,
        'nombre_tours' => $nombre_tours,
        'nombre_rencontres_par_tour' => $nombre_rencontres_par_tour,
        'type_rencontre' => $type_rencontre
    );
}










public function getIdFromNom(string $nom): int {
    $query = "SELECT id FROM Poules WHERE nom = :nom";
    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':nom', $nom);
    $stmt->execute();

    // Utilisation de fetchColumn() pour obtenir directement la valeur de l'ID en tant qu'entier
    $id = $stmt->fetchColumn();

    // Assurez-vous de renvoyer l'ID sous forme d'entier
    return (int)$id;
}

    // Ajoutez ici d'autres méthodes pour gérer les équipes, les rencontres, etc.
}


?>