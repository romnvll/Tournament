<?php

class RencontreDAO
{
    private $connexion;

    public function __construct()
    {
        require 'databaseInformations.php';

        try {
            $this->connexion = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Erreur de connexion à la base de données : " . $e->getMessage();
            exit;
        }
    }




    public function createRencontreByPoule($pouleId, $tournoi_id, $isClassement = 0)
    {
        // Récupérer uniquement les équipes de cette poule dont le champ 'IsPresent' est vrai
        $equipesPresentes = $this->getEquipesPresentesByPoule($pouleId);

        // Vérifier s'il y a au moins deux équipes présentes pour créer des rencontres
        if (count($equipesPresentes) >= 2) {
            // Appliquer l'algorithme du round-robin pour créer les rencontres
            $rencontres = $this->generateRoundRobin($equipesPresentes);
            //var_dump($rencontres);
            // Insérer les rencontres dans la table Rencontres
            foreach ($rencontres as $rencontre) {

                $equipe1Id = $rencontre['equipe1']['id'];
                $equipe2Id = $rencontre['equipe2']['id'];
                $tour = $rencontre['tour'];

                // Vérifier si la rencontre existe déjà dans la table Rencontres
                if (!$this->isRencontreExist($equipe1Id, $equipe2Id)) {
                    // Si la rencontre n'existe pas, l'insérer dans la table Rencontres
                    $this->insertRencontre($equipe1Id, $equipe2Id, $tournoi_id, $isClassement, $tour);
                }
            }
        }
    }


    private function isRencontreExist($equipe1Id, $equipe2Id)
    {
        // Vérifier si la rencontre existe
        $query = "SELECT isClassement FROM Rencontres WHERE (equipe1_id = :equipe1Id AND equipe2_id = :equipe2Id) OR (equipe1_id = :equipe2Id AND equipe2_id = :equipe1Id)";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
        $stmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si la rencontre existe
        if ($result) {
            // Si isClassement est égal à 1, retourner vrai (la rencontre ne doit pas être créée à nouveau)
            if ($result['isClassement'] == 1) {
                return true;
            }
        }

        // Si la rencontre n'existe pas ou si isClassement est égal à 0, retourner faux (la rencontre peut être créée)
        return false;
    }


    private function generateRoundRobin($equipes)
    {
        $n = count($equipes);
        $rencontres = [];
        $dummyTeamAdded = false;

        if ($n % 2 !== 0) {
            // Si le nombre d'équipes est impair, ajouter une équipe fictive
            $equipes[] = ['id' => null, 'name' => 'Dummy'];
            $n++;
            $dummyTeamAdded = true;
        }

        for ($i = 0; $i < $n - 1; $i++) {

            for ($j = 0; $j < $n / 2; $j++) {
                $equipe1 = $equipes[$j];
                $equipe2 = $equipes[$n - 1 - $j];

                // Si une équipe fictive a été ajoutée, assurez-vous qu'elle n'est pas incluse dans les rencontres
                if (!($dummyTeamAdded && ($equipe1['id'] === null || $equipe2['id'] === null))) {
                    $rencontres[] = [
                        'equipe1' => $equipe1,
                        'equipe2' => $equipe2,
                        'tour' => $i + 1
                    ];
                }
            }
            // Ajouter le numéro du tour et le nom de la poule à chaque rencontre


            // Rotation spécifique des équipes pour la prochaine ronde
            $lastTeam = array_pop($equipes);
            array_splice($equipes, 1, 0, [$lastTeam]);
        }

        return $rencontres;
    }


    public function getEquipesPresentesByPoule($pouleId)
    {
        // Effectuez une requête SQL pour récupérer les équipes présentes pour la poule donnée en utilisant une jointure
        $query = "SELECT e.id, e.nom 
                  FROM Equipes e 
                  INNER JOIN EquipePoule ep ON e.id = ep.equipe_id 
                  WHERE ep.poule_id = :pouleId AND e.IsPresent = 1";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function insertRencontre($equipe1Id, $equipe2Id, $tournoi_id, $isClassement = 0, $tour = null)
    {

        // Vérifiez si la rencontre existe déjà
        $checkQuery = "SELECT * FROM Rencontres WHERE equipe1_id = :equipe1Id AND equipe2_id = :equipe2Id AND tournoi_id = :tournoi_id and isClassement = :isClassement ";
        $checkStmt = $this->connexion->prepare($checkQuery);
        $checkStmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
        $checkStmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
        $checkStmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $checkStmt->bindValue(':isClassement', $isClassement, PDO::PARAM_INT);
        $checkStmt->execute();

        // Si la rencontre existe déjà, retournez une indication (par exemple : false)
        if ($checkStmt->fetch()) {
            return false;  // ou peut-être une exception ou un message d'erreur, selon vos besoins
        }

        // Si la rencontre n'existe pas, continuez avec l'insertion
        $query = "INSERT INTO Rencontres (equipe1_id, equipe2_id, tournoi_id, isClassement, tour) VALUES (:equipe1Id, :equipe2Id, :tournoi_id, :isClassement, :tour)";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
        $stmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
        $stmt->bindValue(':tournoi_id', $tournoi_id, PDO::PARAM_INT);
        $stmt->bindValue(':tour', $tour, PDO::PARAM_INT);
        $stmt->bindValue(':isClassement', $isClassement, PDO::PARAM_BOOL);
        $stmt->execute();

        return true;  // indication que l'insertion a réussi
    }

    public function supprimerRencontresParPoule(int $idPoule): void
    {
        // Récupérer le type de poule (is_classement) depuis la table Poules
        $stmtTypePoule = $this->connexion->prepare("SELECT is_classement FROM Poules WHERE id = :idPoule");
        $stmtTypePoule->bindParam(':idPoule', $idPoule);
        $stmtTypePoule->execute();
        $isClassement = $stmtTypePoule->fetch(PDO::FETCH_COLUMN);



        // Trouver toutes les équipes de la poule
        $stmtEquipesPoule = $this->connexion->prepare("SELECT equipe_id FROM EquipePoule WHERE poule_id = :idPoule");
        $stmtEquipesPoule->bindParam(':idPoule', $idPoule);
        $stmtEquipesPoule->execute();
        $equipes = $stmtEquipesPoule->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($equipes)) {
            // Supprimer toutes les rencontres où les équipes de la poule sont impliquées
            $placeholders = implode(',', array_fill(0, count($equipes), '?'));
            if ($isClassement == 0) {

                $stmtRencontres = $this->connexion->prepare("DELETE FROM Rencontres WHERE equipe1_id IN ($placeholders) OR equipe2_id IN ($placeholders)");
            } elseif ($isClassement == 1) {

                $stmtRencontres = $this->connexion->prepare("DELETE FROM Rencontres WHERE (equipe1_id IN ($placeholders) OR equipe2_id IN ($placeholders)) AND isClassement = 1");
            }
            $stmtRencontres->execute(array_merge($equipes, $equipes));
        }
    }

    public function supprimerRencontresParTournoi(int $idtournoi): void
    {
        $query = "DELETE FROM Rencontres WHERE tournoi_id = :idtournoi";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idtournoi', $idtournoi, PDO::PARAM_INT);
        $stmt->execute();
    }


    public function getRencontreByPoule($pouleid)
    {
        $query = "SELECT r.id AS rencontre_id,
                             r.tour as tour,
                             r.terrain AS num_terrain,
                             r.heure AS heure_rencontre,
                             equipe1.id AS equipe1_id,
                             equipe1.nom AS equipe1_nom,
                             c1.logo AS equipe1_logo,
                             equipe2.id AS equipe2_id,
                             equipe2.nom AS equipe2_nom,
                             c2.logo AS equipe2_logo,
                             r.Arbitre AS arbitre,
                             r.score1,
                             r.score2
                      FROM Rencontres r
                      JOIN Equipes equipe1 ON r.equipe1_id = equipe1.id
                      JOIN EquipePoule ep1 ON equipe1.id = ep1.equipe_id
                      JOIN Equipes equipe2 ON r.equipe2_id = equipe2.id
                      JOIN EquipePoule ep2 ON equipe2.id = ep2.equipe_id
                      LEFT JOIN Clubs c1 ON equipe1.club_id = c1.id
                      LEFT JOIN Clubs c2 ON equipe2.club_id = c2.id
                      WHERE ep1.poule_id = :pouleid AND ep2.poule_id = :pouleid
                      ORDER BY r.heure,r.id";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':pouleid', $pouleid, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    public function resetRencontre(int $idrencontre)
    {
        $query = "UPDATE Rencontres set terrain = null where id=:idrencontre";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
        $stmt->execute();

        $query = "UPDATE Rencontres set heure = null where id=:idrencontre";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
        $stmt->execute();
    }







 /**
         * Modifie les informations d'une rencontre dans la base de données.
         *
         * @param int|null    $idrencontre ID de la rencontre à modifier.
         * @param int|null    $equipeScore1 Score de l'équipe 1 (null efface le score ou "9999" pour conserver le score).
         * @param int|null    $equipeScore2 Score de l'équipe 2 (null efface le score ou "9999" pour conserver le score).
         * @param int|null    $terrain     ID du terrain (null pour exclure).
         * @param string|null $heure       Heure de la rencontre (null pour exclure).
         * @param string|null $arbitre     Nom de l'arbitre (null ou "dontTouch" pour exclure).
         * @param int|null    $tournoi_id  ID du tournoi (null pour exclure).
         *
         * @return void
         */

         public function modifierRencontre(
            ?int $idrencontre,
            ?int $equipeScore1 = null,
            ?int $equipeScore2 = null,
            ?int $terrain = null,
            ?string $heure = null,
            ?string $arbitre = null,
            ?int $tournoi_id = null
        ) {
            $query = "UPDATE Rencontres SET";
            $params = [];
        
            if ($equipeScore1 !== null) {
                if ($equipeScore1 !== 9999) {
                    $query .= " score1 = :score1,";
                    $params[':score1'] = $equipeScore1;
                }
            } else {
                $query .= " score1 = NULL,";
            }
        
            if ($equipeScore2 !== null) {
                if ($equipeScore2 !== 9999) {
                    $query .= " score2 = :score2,";
                    $params[':score2'] = $equipeScore2;
                }
            } else {
                $query .= " score2 = NULL,";
            }
        
            if ($terrain !== null) {
                $query .= " terrain = :terrain,";
                $params[':terrain'] = $terrain;
            }
        
            if ($heure !== null) {
                $query .= " heure = :heure,";
                $params[':heure'] = $heure;
            }
        
            if ($arbitre !== null && $arbitre !== "dontTouch") {
                $query .= " Arbitre = :arbitre,";
                $params[':arbitre'] = $arbitre;
            }
        
            if ($tournoi_id !== null) {
                $query .= " tournoi_id = :tournoi_id,";
                $params[':tournoi_id'] = $tournoi_id;
            }
        
            // Supprime la virgule finale
            $query = rtrim($query, ',');
            $query .= " WHERE id = :idrencontre";
        
            $stmt = $this->connexion->prepare($query);
        
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
        
            $stmt->bindValue(':idrencontre', $idrencontre, PDO::PARAM_INT);
            $stmt->execute();
        }
        





    public function GetResultatDesPoules(int $idPoule)
    {
        $query = "
            SELECT
    e.id,
    e.nom,
    e.categorie,
    e.IsPresent,
    e.tournoi_id,
    ep.poule_id,
    e.club_id,
    ((SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND ((r.equipe1_id = e.id AND r.score1 > r.score2) OR (r.equipe2_id = e.id AND r.score2 > r.score1))) * 3) +
    ((SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND ((r.equipe1_id = e.id OR r.equipe2_id = e.id) AND r.score1 = r.score2)) * 2) +
    ((SELECT COUNT(*) FROM Rencontres r WHERE r.isClassement = 0 AND ((r.equipe1_id = e.id AND r.score1 < r.score2) OR (r.equipe2_id = e.id AND r.score2 < r.score1)))) AS TotalDesPoints,
    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.isClassement = 0), 0) +
    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.isClassement = 0), 0) AS nombreButsMarque,
    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.isClassement = 0), 0) +
    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.isClassement = 0), 0) AS nombreButsEncaisse,
    (COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.isClassement = 0), 0) +
    COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.isClassement = 0), 0)) -
    (COALESCE((SELECT SUM(r.score2) FROM Rencontres r WHERE r.equipe1_id = e.id AND r.isClassement = 0), 0) +
    COALESCE((SELECT SUM(r.score1) FROM Rencontres r WHERE r.equipe2_id = e.id AND r.isClassement = 0), 0)) AS DifferenceButs
FROM
    Equipes e
JOIN EquipePoule ep ON e.id = ep.equipe_id
WHERE
    ep.poule_id = :pouleId 
ORDER BY
    TotalDesPoints DESC,
    nombreButsMarque DESC,
    DifferenceButs DESC;

";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':pouleId', $idPoule, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    ////
    public function GetEquipesClasseesParPoule(int $idTournoi)
    {
        // Obtenez la liste des poules pour ce tournoi
        $query = "SELECT DISTINCT ep.poule_id 
                      FROM EquipePoule ep 
                      JOIN Equipes e ON e.id = ep.equipe_id 
                      WHERE e.tournoi_id = :idTournoi";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->execute();
        $poules = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $equipesParPoule = [];

        foreach ($poules as $pouleId) {
            // Récupérez les équipes classées pour cette poule
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
                            DifferenceButs DESC";

            $stmt = $this->connexion->prepare($query);
            $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
            $stmt->bindValue(':pouleId', $pouleId, PDO::PARAM_INT);
            $stmt->execute();

            $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $equipesParPoule[$pouleId] = $equipes;
        }

        return $equipesParPoule;
    }



    public function CreerRencontresDeClassement(int $idTournoi)
    {
        $equipesParPoule = $this->GetEquipesClasseesParPoule($idTournoi);

        // Segmenter les poules par catégorie d'âge et sexe
        $poulesParCategorie = [];
        foreach ($equipesParPoule as $pouleName => $equipes) {
            preg_match("/(U\d+[MF])-\d+$/", $pouleName, $matches);

            if (isset($matches[1])) {
                $categorie = $matches[1];
                if (!isset($poulesParCategorie[$categorie])) {
                    $poulesParCategorie[$categorie] = [];
                }
                $poulesParCategorie[$categorie][] = $equipes;
            }
        }

        $rencontres = [];

        foreach ($poulesParCategorie as $categorie => $poules) {
            $maxRank = count(reset($poules));

            // Pour chaque classement
            for ($rank = 0; $rank < $maxRank; $rank++) {
                $equipesForRank = [];

                // Collecter toutes les équipes pour le classement actuel de toutes les poules
                foreach ($poules as $poule) {
                    if (isset($poule[$rank])) {
                        $equipesForRank[] = $poule[$rank];
                    }
                }

                // Créer des rencontres pour ce classement
                for ($i = 0; $i < count($equipesForRank) - 1; $i += 2) {
                    for ($j = $i + 1; $j < count($equipesForRank); $j++) {
                        $rencontres[] = [
                            'equipe1_id' => $equipesForRank[$i]['id'],
                            'equipe2_id' => $equipesForRank[$j]['id'],
                            'tournoi_id' => $idTournoi
                        ];
                        break; // Nous avons seulement besoin de la première rencontre pour ce i
                    }
                }
            }
        }


        foreach ($rencontres as $match) {
            // Vérifier si la rencontre existe déjà
            $checkQuery = "SELECT id FROM Rencontres WHERE 
                           (equipe1_id = :equipe1_id AND equipe2_id = :equipe2_id) OR 
                           (equipe1_id = :equipe2_id AND equipe2_id = :equipe1_id) AND 
                           tournoi_id = :tournoi_id";
            $stmt = $this->connexion->prepare($checkQuery);
            $stmt->execute([
                'equipe1_id' => $match['equipe1_id'],
                'equipe2_id' => $match['equipe2_id'],
                'tournoi_id' => $match['tournoi_id']
            ]);

            if ($stmt->fetchColumn() == 0) {  // Si la rencontre n'existe pas, insérez-la
                $insertQuery = "INSERT INTO Rencontres (equipe1_id, equipe2_id, tournoi_id) VALUES (:equipe1_id, :equipe2_id, :tournoi_id)";
                $stmt = $this->connexion->prepare($insertQuery);
                $stmt->execute($match);
            }
        }
    }





    ////


    function afficherRencontreByTournoiByClub(int $idTournoi, int $club)
    {
        $query = "SELECT r.id AS rencontre_id,
            r.equipe1_id,
            equipe1.nom AS equipe1_nom,
            c1.logo AS equipe1_logo,
            r.equipe2_id,
            equipe2.nom AS equipe2_nom,
            c2.logo AS equipe2_logo,
            r.score1,
            r.score2,
            r.heure AS heure_rencontre,
            r.terrain AS num_terrain,
            r.Arbitre
     FROM Rencontres r
     JOIN Equipes equipe1 ON r.equipe1_id = equipe1.id
     JOIN Equipes equipe2 ON r.equipe2_id = equipe2.id
     LEFT JOIN Clubs c1 ON equipe1.club_id = c1.id
     LEFT JOIN Clubs c2 ON equipe2.club_id = c2.id
     WHERE (equipe1.tournoi_id = :idTournoi AND equipe1.club_id = :club)
        OR (equipe2.tournoi_id = :idTournoi AND equipe2.club_id = :club) 
     ORDER BY heure";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':club', $club, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function afficherRencontreByTournoiByEquipe(int $idTournoi, int $id_equipe)
    {
        $query = "SELECT r.id AS rencontre_id,
            r.equipe1_id,
            equipe1.nom AS equipe1_nom,
            c1.logo AS equipe1_logo,
            c2.logo AS equipe2_logo,
            r.equipe2_id,
            equipe2.nom AS equipe2_nom,
            r.score1,
            r.score2,
            r.heure AS heure_rencontre,
            r.terrain AS num_terrain,
            r.Arbitre
     FROM Rencontres r
     JOIN Equipes equipe1 ON r.equipe1_id = equipe1.id
     JOIN Clubs c1 ON equipe1.club_id = c1.id
     JOIN Equipes equipe2 ON r.equipe2_id = equipe2.id
     JOIN Clubs c2 ON equipe2.club_id = c2.id
     WHERE (equipe1.tournoi_id = :idTournoi AND equipe1.id = :id_equipe)
        OR (equipe2.tournoi_id = :idTournoi AND equipe2.id = :id_equipe)
     ORDER BY r.heure;
     ";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':idTournoi', $idTournoi, PDO::PARAM_INT);
        $stmt->bindValue(':id_equipe', $id_equipe, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function rencontreExisteDeja($equipe1Id, $equipe2Id)
    {

        $query = "SELECT COUNT(*) FROM Rencontres WHERE (equipe1_id = :equipe1Id AND equipe2_id = :equipe2Id) OR (equipe1_id = :equipe2Id AND equipe2_id = :equipe1Id)";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':equipe1Id', $equipe1Id, PDO::PARAM_INT);
        $stmt->bindValue(':equipe2Id', $equipe2Id, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return ($count > 0);

        // Pour l'exemple, on renvoie toujours faux pour éviter la duplication.
        //return false;
    }

    function updateEtatRencontre($etat, $tournoi_id, $heure)
    {
        // Vérifier que $etat est valide (0, 1 ou 2)
        if ($etat < 0 || $etat > 3) {
            // Retourner une erreur ou gérer le cas invalide selon vos besoins
            return false;
        }

        $query = "UPDATE Rencontres SET etat = :etat WHERE tournoi_id = :tournoiId and heure = :heure";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':etat', $etat, PDO::PARAM_INT);
        $stmt->bindValue(':heure', $heure, PDO::PARAM_STR);
        $stmt->bindValue(':tournoiId', $tournoi_id, PDO::PARAM_INT);
        $stmt->execute();

        // Vérifier si une ligne a été affectée (mise à jour réussie)
        return $stmt->rowCount() > 0;
    }

    public function getEtatRencontre($rencontreId)
    {
        $query = "SELECT etat FROM Rencontres WHERE id = :rencontreId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':rencontreId', $rencontreId, PDO::PARAM_INT);
        $stmt->execute();

        // Récupérer la valeur de l'état de la rencontre
        $etat = $stmt->fetchColumn();

        // Vérifier si une valeur a été récupérée
        if ($etat !== false) {
            return $etat; // Retourner l'état de la rencontre
        } else {
            return null; // Retourner null si aucune valeur n'est trouvée
        }
    }



    public function getRencontreByTournoi($tournoiId)
    {
        $query = "SELECT 
            r.id AS rencontre_id,
            r.isClassement AS isClassement,
            r.terrain AS num_terrain,
            r.tour AS tour,
            r.heure AS heure_rencontre,
            equipe1.id AS equipe1_id,
            equipe1.nom AS equipe1_nom,
            equipe1.categorie AS equipe1_categorie,
            (SELECT poule1.nom FROM EquipePoule ep1 JOIN Poules poule1 ON ep1.poule_id = poule1.id WHERE ep1.equipe_id = equipe1.id LIMIT 1) AS equipe1_poule_nom,
            equipe2.id AS equipe2_id,
            equipe2.nom AS equipe2_nom,
            equipe2.categorie AS equipe2_categorie,
            (SELECT poule2.nom FROM EquipePoule ep2 JOIN Poules poule2 ON ep2.poule_id = poule2.id WHERE ep2.equipe_id = equipe2.id LIMIT 1) AS equipe2_poule_nom,
            r.Arbitre AS arbitre,
            r.score1,
            r.score2
        FROM Rencontres r
        JOIN Equipes equipe1 ON r.equipe1_id = equipe1.id
        JOIN Equipes equipe2 ON r.equipe2_id = equipe2.id
        WHERE equipe1.IsPresent = 1 AND equipe2.IsPresent = 1 AND r.tournoi_id = :tournoiId
        ORDER BY CASE WHEN r.heure IS NULL THEN 1 ELSE 0 END, r.heure, equipe1.categorie
        
        ";

        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        $rencontres[] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Combiner les résultats pour chaque catégorie en un seul tableau
        $resultats = array_merge(...$rencontres);

        return $resultats;
    }




    // Méthode pour obtenir les équipes par catégorie et tournoi
    public function getEquipesByCategorieAndTournoi($categorie, $tournoiId)
    {
        $query = "SELECT * FROM Equipes WHERE categorie = :categorie AND tournoi_id = :tournoiId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':categorie', $categorie);
        $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getAllRencontresByTournoiId($tournoiId)
    {
        $query = "SELECT r.*, 
                      e1.nom AS equipe1_nom, e2.nom AS equipe2_nom,
                      e1.categorie AS equipe1_categorie, e2.categorie AS equipe2_categorie,
                      c1.logo AS equipe1_logo, c2.logo AS equipe2_logo
                      FROM Rencontres r
                      LEFT JOIN Equipes e1 ON r.equipe1_id = e1.id
                      LEFT JOIN Equipes e2 ON r.equipe2_id = e2.id
                      LEFT JOIN Clubs c1 ON e1.club_id = c1.id
                      LEFT JOIN Clubs c2 ON e2.club_id = c2.id
                      WHERE r.tournoi_id = :tournoiId
                      ORDER BY r.heure";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();

        $rencontres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rencontres;
    }
}
