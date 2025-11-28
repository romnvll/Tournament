<?php
class creneauxDao {
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

   public function ajouterCreneau(string $nom, int $tournoi_id): void {
    // Vérifier si le format de l'heure est valide (HH:MM)
    if (preg_match("/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/", $nom)) {
        // Ajouter les secondes ":00" pour respecter le format TIME (HH:MM:SS)
        $nom .= ":00";
    } else {
        throw new Exception("Le format de l'heure est invalide. Utilisez le format HH:MM.");
    }

    // Récupérer le nombre actuel de créneaux pour ce tournoi
    $stmtCount = $this->connexion->prepare("
        SELECT COUNT(*) FROM Creneaux WHERE tournoi_id = :tournoi_id order by ordre
    ");
    $stmtCount->bindParam(':tournoi_id', $tournoi_id);
    $stmtCount->execute();
    $ordre = (int)$stmtCount->fetchColumn() + 1;

    // Insérer le nouveau créneau avec ordre
    $stmtInsert = $this->connexion->prepare("
        INSERT INTO Creneaux (nom, tournoi_id, ordre)
        VALUES (:nom, :tournoi_id, :ordre)
    ");
    $stmtInsert->bindParam(':nom', $nom);
    $stmtInsert->bindParam(':tournoi_id', $tournoi_id);
    $stmtInsert->bindParam(':ordre', $ordre);
    $stmtInsert->execute();
}

public function getAudiosPourCreneau(int $creneau_id): array {
    $stmt = $this->connexion->prepare("
        SELECT 
            T.terrain_id,
            T.audio_path AS terrain_audio,
            E1.audio_path AS equipe1_audio,
            E2.audio_path AS equipe2_audio,
            A.audio_path AS arbitre_audio
        FROM Planification P
        LEFT JOIN Rencontres R ON R.id = P.rencontre_id
        LEFT JOIN Terrains T ON T.terrain_id = P.terrain_id
        LEFT JOIN Equipes E1 ON E1.id = R.equipe1_id
        LEFT JOIN Equipes E2 ON E2.id = R.equipe2_id
        LEFT JOIN Arbitres A ON A.arbitre_id = P.arbitre_id
        WHERE P.creneau_id = :creneau_id
    ");
    $stmt->bindParam(':creneau_id', $creneau_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}





public function ajouterCreneauEntre(int $tournoi_id, int $ordreAvant, int $pasMinutes): void
{
    // Récupérer l'heure du créneau précédent
    $stmtPrev = $this->connexion->prepare("
        SELECT nom
        FROM Creneaux
        WHERE tournoi_id = :tournoi_id AND ordre = :ordreAvant
    ");
    $stmtPrev->execute([
        ':tournoi_id' => $tournoi_id,
        ':ordreAvant' => $ordreAvant
    ]);

    $result = $stmtPrev->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception("Aucun créneau trouvé à l'ordre spécifié.");
    }

    $heurePrecedente = $result['nom'];

    // Ajouter X minutes (pas horaire) pour obtenir la nouvelle heure
    $interval = new DateInterval('PT' . $pasMinutes . 'M');
    $nouvelleHeure = (new DateTime($heurePrecedente))->add($interval)->format('H:i:s');

    // Commencer la transaction
    $this->connexion->beginTransaction();

    try {
        // Récupérer les créneaux suivants
        $stmtSuivants = $this->connexion->prepare("
            SELECT creneau_id, nom
            FROM Creneaux
            WHERE tournoi_id = :tournoi_id AND ordre > :ordreAvant
            ORDER BY ordre ASC
        ");
        $stmtSuivants->execute([
            ':tournoi_id' => $tournoi_id,
            ':ordreAvant' => $ordreAvant
        ]);

        $creneauxSuivants = $stmtSuivants->fetchAll(PDO::FETCH_ASSOC);

        // Décaler les heures et les ordres
        foreach ($creneauxSuivants as $index => $creneau) {
            $newTime = (new DateTime($creneau['nom']))->add($interval)->format('H:i:s');

            $stmtUpdate = $this->connexion->prepare("
                UPDATE Creneaux
                SET nom = :new_nom, ordre = ordre + 1
                WHERE creneau_id = :id
            ");
            $stmtUpdate->execute([
                ':new_nom' => $newTime,
                ':id' => $creneau['creneau_id']
            ]);
        }

        // Insérer le nouveau créneau à l'ordre suivant
        $stmtInsert = $this->connexion->prepare("
            INSERT INTO Creneaux (nom, tournoi_id, ordre)
            VALUES (:nom, :tournoi_id, :ordre)
        ");
        $stmtInsert->execute([
            ':nom' => $nouvelleHeure,
            ':tournoi_id' => $tournoi_id,
            ':ordre' => $ordreAvant + 1
        ]);

        $this->connexion->commit();
    } catch (Exception $e) {
        $this->connexion->rollBack();
        throw $e;
    }
}


    
    

    public function mettreAJourIntervalle($tournoi_id, $intervalle) {
        try {
            $this->connexion->beginTransaction();
    
            // Récupérer l'heure du premier créneau
            $sqlFirst = "
                SELECT nom
                FROM Creneaux
                WHERE tournoi_id = :tournoi_id
                ORDER BY creneau_id ASC
                LIMIT 1
            ";
    
            $stmtFirst = $this->connexion->prepare($sqlFirst);
            $stmtFirst->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
            $stmtFirst->execute();
    
            $firstCreneau = $stmtFirst->fetch(PDO::FETCH_ASSOC);
            
            if (!$firstCreneau) {
                throw new Exception("Aucun créneau trouvé pour le tournoi $tournoi_id");
            }
    
            $firstHoraire = $firstCreneau['nom']; // Déjà sous format `HH:MM:SS`
    
            // Mise à jour des créneaux avec correction du dépassement de 24h
            $sqlUpdate = "
                UPDATE Creneaux
                JOIN (
                    SELECT creneau_id, ROW_NUMBER() OVER (ORDER BY creneau_id ASC) AS rownum
                    FROM Creneaux
                    WHERE tournoi_id = :tournoi_id
                ) AS ordered_creneaux
                ON Creneaux.creneau_id = ordered_creneaux.creneau_id
                SET Creneaux.nom = SEC_TO_TIME(
                    MOD(
                        TIME_TO_SEC(:firstHoraire) + ((ordered_creneaux.rownum - 1) * :intervalle * 60),
                        86400
                    )
                )
                WHERE Creneaux.tournoi_id = :tournoi_id;
            ";
    
            $stmtUpdate = $this->connexion->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
            $stmtUpdate->bindParam(':firstHoraire', $firstHoraire);
            $stmtUpdate->bindParam(':intervalle', $intervalle, PDO::PARAM_INT);
    
            $stmtUpdate->execute();
    
            $this->connexion->commit();
    
            return $stmtUpdate->rowCount();
        } catch (Exception $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }
    
    
    
    
    
    
    

    

    public function mettreAJourCreneauxAvecMinutesAjoutees($tournoiId, $minutes) {
        $sql = "
            UPDATE Creneaux
            SET nom = DATE_FORMAT( 
                ADDTIME(nom, SEC_TO_TIME(:minutes * 60)), '%H:%i'
            )
            WHERE tournoi_id = :tournoiId
        ";
    
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindParam(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->bindParam(':minutes', $minutes, PDO::PARAM_INT);
    
        if ($stmt->execute()) {
            return $stmt->rowCount(); // Retourne le nombre de lignes mises à jour
        } else {
            return false; // En cas d'échec
        }
    }
    

    
    
    
    


    public function modifierCreneau(int $creneau_id, string $nom = null, int $tournoi_id = null): void {
        $sql = "UPDATE Creneaux SET ";
        $params = [];
        if ($nom !== null) {
            $sql .= "nom = :nom, ";
            $params[':nom'] = $nom;
        }
        if ($tournoi_id !== null) {
            $sql .= "tournoi_id = :tournoi_id, ";
            $params[':tournoi_id'] = $tournoi_id;
        }
        $sql = rtrim($sql, ", ");
        $sql .= " WHERE creneau_id = :creneau_id";
        $params[':creneau_id'] = $creneau_id;

        $stmt = $this->connexion->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();
    }

    public function supprimerCreneau(int $creneau_id): void {
        // Commencer une transaction pour garantir l'intégrité
        $this->connexion->beginTransaction();
    
        try {
            // Supprimer d'abord les lignes dans Planification si les conditions sont remplies
            $stmtPlanification = $this->connexion->prepare("
                DELETE FROM Planification
                WHERE creneau_id = :creneau_id
                AND rencontre_id IS NULL
                AND label_id IS NULL
            ");
            $stmtPlanification->bindParam(':creneau_id', $creneau_id);
            $stmtPlanification->execute();
    
            // Vérifier si des lignes dans Planification sont encore liées au creneau_id
            $stmtCheck = $this->connexion->prepare("
                SELECT COUNT(*) FROM Planification WHERE creneau_id = :creneau_id
            ");
            $stmtCheck->bindParam(':creneau_id', $creneau_id);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();
    
            // Si aucune ligne restante, supprimer le créneau
            if ($count == 0) {
                $stmtCreneaux = $this->connexion->prepare("
                    DELETE FROM Creneaux WHERE creneau_id = :creneau_id
                ");
                $stmtCreneaux->bindParam(':creneau_id', $creneau_id);
                $stmtCreneaux->execute();
            }
    
            // Valider la transaction
            $this->connexion->commit();
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->connexion->rollBack();
            throw $e; // Relancer l'exception pour traitement
        }
    }
    

    public function supprimerCreneauxParTournoi(int $tournoi_id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Creneaux WHERE tournoi_id = :tournoi_id");
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();
    }


    public function supprimerCreneauEtRecaler(int $creneau_id, int $tournoi_id, int $pas): void
{
    try {
        // Vérifier les utilisations du créneau
        $stmt = $this->connexion->prepare("
            SELECT * FROM Planification 
            WHERE creneau_id = :creneau_id AND tournoi_id = :tournoi_id
        ");
        $stmt->execute([
            ':creneau_id' => $creneau_id,
            ':tournoi_id' => $tournoi_id,
        ]);
        $planifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($planifications as $planif) {
            if (!is_null($planif['label_id']) || !is_null($planif['rencontre_id'])) {
                throw new Exception("Créneau déjà utilisé, suppression impossible.");
            }
        }

        // Supprimer les planifications vides
        $stmt = $this->connexion->prepare("
            DELETE FROM Planification 
            WHERE creneau_id = :creneau_id AND tournoi_id = :tournoi_id
        ");
        $stmt->execute([
            ':creneau_id' => $creneau_id,
            ':tournoi_id' => $tournoi_id,
        ]);

        // Récupérer les infos du créneau à supprimer
        $stmt = $this->connexion->prepare("
            SELECT ordre 
            FROM Creneaux 
            WHERE creneau_id = :id AND tournoi_id = :tournoi_id
        ");
        $stmt->bindParam(':id', $creneau_id, PDO::PARAM_INT);
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();
        $creneau = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$creneau) {
            throw new Exception("Créneau non trouvé.");
        }

        $ordreSupprime = $creneau['ordre'];

        // Supprimer le créneau
        $stmt = $this->connexion->prepare("
            DELETE FROM Creneaux 
            WHERE creneau_id = :id AND tournoi_id = :tournoi_id
        ");
        $stmt->bindParam(':id', $creneau_id, PDO::PARAM_INT);
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();

        // Récupérer les créneaux suivants
        $stmt = $this->connexion->prepare("
            SELECT creneau_id, nom 
            FROM Creneaux 
            WHERE tournoi_id = :tournoi_id AND ordre > :ordre 
            ORDER BY ordre ASC
        ");
        $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->bindParam(':ordre', $ordreSupprime, PDO::PARAM_INT);
        $stmt->execute();
        $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recaler les créneaux suivants
        $ordreActuel = $ordreSupprime;
        foreach ($creneaux as $creneau) {
            $nouvelleHeure = (new DateTime($creneau['nom']))
                ->sub(new DateInterval("PT{$pas}M"))
                ->format("H:i:s");

            $stmtUpdate = $this->connexion->prepare("
                UPDATE Creneaux 
                SET nom = :nom, ordre = :ordre 
                WHERE creneau_id = :id
            ");
            $stmtUpdate->bindParam(':nom', $nouvelleHeure);
            $stmtUpdate->bindParam(':ordre', $ordreActuel, PDO::PARAM_INT);
            $stmtUpdate->bindParam(':id', $creneau['creneau_id'], PDO::PARAM_INT);
            $stmtUpdate->execute();

            $ordreActuel++;
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>".$e->getMessage()."</div>";
        exit();
    }
}




    


    public function afficherCreneaux(int $tournoi_id): array {
        $stmt = $this->connexion->prepare("SELECT * FROM Creneaux WHERE tournoi_id = :tournoi_id order by ordre");
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


public function afficherCreneauxOccupes(int $tournoi_id): array
{
    $sql = "
        SELECT DISTINCT c.*
        FROM Creneaux c
        INNER JOIN Planification p 
            ON p.creneau_id = c.creneau_id 
            AND p.tournoi_id = c.tournoi_id
        WHERE c.tournoi_id = :tournoi_id
          AND (
                p.rencontre_id IS NOT NULL 
                OR p.arbitre_id IS NOT NULL
                OR p.label_id IS NOT NULL
              )
        ORDER BY c.ordre
    ";

    $stmt = $this->connexion->prepare($sql);
    $stmt->bindParam(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




    public function existeCreneauPourTournoi($tournoi_id) {
        $sql = "SELECT COUNT(*) FROM Creneaux WHERE tournoi_id = :tournoi_id";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();
        
        // Retourner true si le nombre de lignes est supérieur à 0, sinon false
        return $stmt->fetchColumn() > 0;
    }

    public function getLastCreneau($tournoi_id) {
        $sql = "SELECT nom FROM Creneaux WHERE 
        tournoi_id = :tournoi_id ORDER BY creneau_id DESC LIMIT 1";
        $stmt = $this->connexion->prepare($sql);
        $stmt->bindParam(':tournoi_id', $tournoi_id);
        $stmt->execute();

       return  $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    }
    
}
