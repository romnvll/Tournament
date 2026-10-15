<?php

/**
 * TournoiBackupDao
 * -----------------
 * Sauvegarde (export) et restauration (import) complète d'un tournoi,
 * avec toutes ses tables dépendantes.
 *
 * Tables sauvegardées (liées par tournoi_id / fk_idTournoi) :
 *   Terrains, Creneaux, Arbitres, Equipes, Poules, EquipePoule,
 *   phases_finales, Rencontres, elimination_config, Labels,
 *   Planification, Personne, PersonneTable.
 *
 * Tables "partagées" référencées mais pas possédées par le tournoi
 * (elles appartiennent à l'utilisateur), réexportées pour que la
 * sauvegarde soit autonome :
 *   Clubs, Categorie, Gymnases, TypeDeSport.
 *
 * NB : Messages / MessagesVues / Assistance / Sponsors ne sont pas
 * inclus (données de communication, pas structurantes pour un tournoi).
 *
 * Connexion : même principe que les autres DAO du projet (ex. TerrainDao) :
 * databaseInformations.php définit $host / $dbname / $username / $password.
 */
class TournoiBackupDao
{
    private PDO $connexion;

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

    /* ==================================================================
     *  EXPORT
     * ================================================================== */

    /* ==================================================================
     *  LICENCE
     * ================================================================== */

    public function compterTournoisUtilisateur(int $utilisateurId): int
    {
        $stmt = $this->connexion->prepare('SELECT COUNT(*) FROM Tournois WHERE utilisateur_id = ?');
        $stmt->execute([$utilisateurId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Limite de tournois de la licence active de l'utilisateur (la plus récente parmi
     * celles non expirées), ou null si l'utilisateur n'a aucune licence (dans ce cas
     * on ne bloque pas : pas de limite connue).
     */
    public function limiteTournoisUtilisateur(int $utilisateurId): ?int
    {
        $stmt = $this->connexion->prepare(
            'SELECT lt.limite_tournois
             FROM Licence l
             INNER JOIN LicenceType lt ON lt.id = l.licence_type_id
             WHERE l.utilisateur_id = ?
               AND (l.date_fin IS NULL OR l.date_fin >= CURDATE())
             ORDER BY l.date_debut DESC
             LIMIT 1'
        );
        $stmt->execute([$utilisateurId]);
        $valeur = $stmt->fetchColumn();
        return $valeur !== false ? (int) $valeur : null;
    }

    public function exporter(int $idTournoi): array
    {
        $tournoi = $this->fetchOne('SELECT * FROM Tournois WHERE id = ?', [$idTournoi]);
        if (!$tournoi) {
            throw new InvalidArgumentException("Tournoi introuvable (id=$idTournoi).");
        }

        $terrains        = $this->fetchAll('SELECT * FROM Terrains WHERE fk_idTournoi = ?', [$idTournoi]);
        $creneaux        = $this->fetchAll('SELECT * FROM Creneaux WHERE tournoi_id = ?', [$idTournoi]);
        $arbitres        = $this->fetchAll('SELECT * FROM Arbitres WHERE tournoi_id = ?', [$idTournoi]);
        $equipes         = $this->fetchAll('SELECT * FROM Equipes WHERE tournoi_id = ?', [$idTournoi]);
        $poules          = $this->fetchAll('SELECT * FROM Poules WHERE tournoi_id = ?', [$idTournoi]);
        $phasesFinales   = $this->fetchAll('SELECT * FROM phases_finales WHERE tournoi_id = ?', [$idTournoi]);
        $rencontres      = $this->fetchAll('SELECT * FROM Rencontres WHERE tournoi_id = ?', [$idTournoi]);
        $eliminationCfg  = $this->fetchAll('SELECT * FROM elimination_config WHERE tournoi_id = ?', [$idTournoi]);
        $labels          = $this->fetchAll('SELECT * FROM Labels WHERE tournoi_id = ?', [$idTournoi]);

        // Planification : on ne se fie pas uniquement à Planification.tournoi_id (pas toujours
        // fiable selon la façon dont les lignes ont été insérées côté appli) ; on récupère aussi
        // tout ce qui est rattaché à un terrain, une rencontre, un arbitre ou un label de ce tournoi.
        $planification = $this->exporterPlanification(
            $idTournoi,
            array_column($terrains, 'terrain_id'),
            array_column($rencontres, 'id'),
            array_column($arbitres, 'arbitre_id'),
            array_column($labels, 'label_id')
        );

        $personnes       = $this->fetchAll('SELECT * FROM Personne WHERE tournoi_id = ?', [$idTournoi]);
        $personneTable   = $this->fetchAll('SELECT * FROM PersonneTable WHERE tournoi_id = ?', [$idTournoi]);

        $equipePoule = [];
        if ($poules) {
            $pouleIds = array_column($poules, 'id');
            $equipePoule = $this->fetchAllIn(
                'SELECT * FROM EquipePoule WHERE poule_id IN (%s)',
                $pouleIds
            );
        }

        // Tables partagées référencées : on ne garde que ce qui est utilisé
        $clubIds = array_unique(array_filter(array_merge(
            array_column($equipes, 'club_id'),
            array_column($arbitres, 'club_id')
        )));
        $categorieIds = array_unique(array_filter(array_merge(
            array_column($equipes, 'categorie'),
            array_column($poules, 'fk_idcategorie'),
            array_column($labels, 'categorie_id'),
            array_column($eliminationCfg, 'categorie_id')
        )));

        $clubs      = $clubIds ? $this->fetchAllIn('SELECT * FROM Clubs WHERE id IN (%s)', $clubIds) : [];
        $categories = $categorieIds ? $this->fetchAllIn('SELECT * FROM Categorie WHERE id_categorie IN (%s)', $categorieIds) : [];

        $gymnase    = $tournoi['gymnase_id'] ? $this->fetchOne('SELECT * FROM Gymnases WHERE id = ?', [$tournoi['gymnase_id']]) : null;
        $typeSport  = $tournoi['type_sport_id'] ? $this->fetchOne('SELECT * FROM TypeDeSport WHERE id = ?', [$tournoi['type_sport_id']]) : null;

        return [
            'format_version'  => 1,
            'date_export'     => date('c'),
            'tournoi'         => $tournoi,
            'typeDeSport'     => $typeSport,
            'gymnase'         => $gymnase,
            'clubs'           => $clubs,
            'categories'      => $categories,
            'terrains'        => $terrains,
            'creneaux'        => $creneaux,
            'arbitres'        => $arbitres,
            'equipes'         => $equipes,
            'poules'          => $poules,
            'equipePoule'     => $equipePoule,
            'phasesFinales'   => $phasesFinales,
            'rencontres'      => $rencontres,
            'eliminationConfig' => $eliminationCfg,
            'labels'          => $labels,
            'planification'   => $planification,
            'personnes'       => $personnes,
            'personneTable'   => $personneTable,
        ];
    }

    private function exporterPlanification(int $idTournoi, array $terrainIds, array $rencontreIds, array $arbitreIds, array $labelIds): array
    {
        $conditions = ['tournoi_id = ?'];
        $params = [$idTournoi];

        $ajouterConditionIn = function (string $colonne, array $ids) use (&$conditions, &$params) {
            $ids = array_values(array_unique(array_filter($ids)));
            if (!$ids) return;
            $conditions[] = $colonne . ' IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            foreach ($ids as $id) $params[] = $id;
        };

        $ajouterConditionIn('terrain_id', $terrainIds);
        $ajouterConditionIn('rencontre_id', $rencontreIds);
        $ajouterConditionIn('arbitre_id', $arbitreIds);
        $ajouterConditionIn('label_id', $labelIds);

        $sql = 'SELECT DISTINCT * FROM Planification WHERE ' . implode(' OR ', $conditions);
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==================================================================
     *  IMPORT / RESTAURATION
     * ================================================================== */

    /**
     * Restaure une sauvegarde pour le compte de $utilisateurId.
     * Retourne l'id du nouveau tournoi créé.
     */
    public function restaurer(array $data, int $utilisateurId): int
    {
        if (!isset($data['tournoi']) || !is_array($data['tournoi'])) {
            throw new InvalidArgumentException("Fichier de sauvegarde invalide.");
        }

        $this->connexion->beginTransaction();
        try {
            $mapClub = $this->restaurerClubs($data['clubs'] ?? [], $utilisateurId);
            $mapCategorie = $this->restaurerCategories($data['categories'] ?? [], $utilisateurId);

            $gymnaseId = null;
            if (!empty($data['gymnase'])) {
                $gymnaseId = $this->restaurerGymnase($data['gymnase'], $utilisateurId);
            }

            $typeSportId = null;
            if (!empty($data['typeDeSport'])) {
                $typeSportId = $this->restaurerTypeSport($data['typeDeSport']);
            }

            $newTournoiId = $this->restaurerTournoi($data['tournoi'], $utilisateurId, $gymnaseId, $typeSportId);

            $mapTerrain = $this->restaurerTerrains($data['terrains'] ?? [], $newTournoiId);
            $mapCreneau = $this->restaurerCreneaux($data['creneaux'] ?? [], $newTournoiId);
            $mapArbitre = $this->restaurerArbitres($data['arbitres'] ?? [], $newTournoiId, $mapClub);
            $mapEquipe = $this->restaurerEquipes($data['equipes'] ?? [], $newTournoiId, $mapClub, $mapCategorie);
            $mapPoule = $this->restaurerPoules($data['poules'] ?? [], $newTournoiId, $mapCategorie);
            $this->restaurerEquipePoule($data['equipePoule'] ?? [], $mapEquipe, $mapPoule);
            $mapPhaseFinale = $this->restaurerPhasesFinales($data['phasesFinales'] ?? [], $newTournoiId);
            $mapRencontre = $this->restaurerRencontres($data['rencontres'] ?? [], $newTournoiId, $mapEquipe, $mapPoule, $mapPhaseFinale);
            $mapLabel = $this->restaurerLabels($data['labels'] ?? [], $newTournoiId, $mapCategorie);
            $this->restaurerEliminationConfig($data['eliminationConfig'] ?? [], $newTournoiId, $mapCategorie);
            $this->restaurerPlanification($data['planification'] ?? [], $newTournoiId, $mapTerrain, $mapCreneau, $mapRencontre, $mapLabel, $mapArbitre);
            $mapPersonne = $this->restaurerPersonnes($data['personnes'] ?? [], $newTournoiId);
            $this->restaurerPersonneTable($data['personneTable'] ?? [], $newTournoiId, $mapPersonne, $mapTerrain);

            $this->connexion->commit();
            return $newTournoiId;
        } catch (Throwable $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }

    // --- Tables partagées (réutilisées si une correspondance existe déjà) ---

    private function restaurerClubs(array $clubs, int $utilisateurId): array
    {
        $map = [];
        foreach ($clubs as $c) {
            $existant = $this->fetchOne(
                'SELECT id FROM Clubs WHERE nom = ? AND utilisateur_id = ?',
                [$c['nom'], $utilisateurId]
            );
            if ($existant) {
                $map[$c['id']] = $existant['id'];
                continue;
            }
            $stmt = $this->connexion->prepare(
                'INSERT INTO Clubs (nom, logo, type_sport_id, utilisateur_id) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$c['nom'], $c['logo'] ?? 'logos/default.png', $c['type_sport_id'] ?? 1, $utilisateurId]);
            $map[$c['id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerCategories(array $categories, int $utilisateurId): array
    {
        $map = [];
        foreach ($categories as $cat) {
            $existant = $this->fetchOne(
                'SELECT id_categorie FROM Categorie WHERE Nom_categorie = ? AND utilisateur_id = ?',
                [$cat['Nom_categorie'], $utilisateurId]
            );
            if ($existant) {
                $map[$cat['id_categorie']] = $existant['id_categorie'];
                continue;
            }
            $stmt = $this->connexion->prepare(
                'INSERT INTO Categorie (Nom_categorie, Couleur, afficherClassement, ordrePlacementAuto, utilisateur_id)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $cat['Nom_categorie'], $cat['Couleur'],
                $cat['afficherClassement'] ?? 1, $cat['ordrePlacementAuto'] ?? null,
                $utilisateurId,
            ]);
            $map[$cat['id_categorie']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerGymnase(array $g, int $utilisateurId): int
    {
        $stmt = $this->connexion->prepare(
            'INSERT INTO Gymnases (nom, adresse, ville, commentaire, code_postal, utilisateur_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $g['nom'], $g['adresse'] ?? null, $g['ville'] ?? null,
            $g['commentaire'] ?? null, $g['code_postal'] ?? null, $utilisateurId,
        ]);
        return (int) $this->connexion->lastInsertId();
    }

    private function restaurerTypeSport(array $t): int
    {
        $existant = $this->fetchOne('SELECT id FROM TypeDeSport WHERE nom = ?', [$t['nom']]);
        if ($existant) {
            return (int) $existant['id'];
        }
        $stmt = $this->connexion->prepare('INSERT INTO TypeDeSport (nom) VALUES (?)');
        $stmt->execute([$t['nom']]);
        return (int) $this->connexion->lastInsertId();
    }

    // --- Tournoi + tables qui en dépendent directement ---

    private function restaurerTournoi(array $t, int $utilisateurId, ?int $gymnaseId, ?int $typeSportId): int
    {
        $stmt = $this->connexion->prepare(
            'INSERT INTO Tournois
             (nom, dateDebut, nb_terrains, heure_debut, pasHoraire, gestionRepas, gestionPartenaires,
              gestionTables, gestionArbitres, gestionInformations, gestionVoix, isClassement, isVisible,
              heureIsVisible, telephone, commentaire, isArchived, IsRankingView, refreshClientTime,
              type_sport_id, utilisateur_id, gymnase_id, effetsSonores, gestionTempsChangement,
              tempsChangementMinutes, afficherCountdownCoach)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $t['nom'] . ' (restauré)', $t['dateDebut'], $t['nb_terrains'], $t['heure_debut'], $t['pasHoraire'],
            $t['gestionRepas'] ?? 0, $t['gestionPartenaires'] ?? 0, $t['gestionTables'] ?? 0,
            $t['gestionArbitres'] ?? 0, $t['gestionInformations'] ?? 0, $t['gestionVoix'] ?? 0,
            $t['isClassement'] ?? null, $t['isVisible'] ?? 1, $t['heureIsVisible'] ?? 1,
            $t['telephone'] ?? null, $t['commentaire'] ?? null,
            0, // isArchived : on restaure toujours un tournoi actif, jamais déjà archivé
            $t['IsRankingView'] ?? 0, $t['refreshClientTime'] ?? 30000,
            $typeSportId, $utilisateurId, $gymnaseId,
            $t['effetsSonores'] ?? 0, $t['gestionTempsChangement'] ?? 0,
            $t['tempsChangementMinutes'] ?? 0, $t['afficherCountdownCoach'] ?? 0,
        ]);
        return (int) $this->connexion->lastInsertId();
    }

    private function restaurerTerrains(array $rows, int $newTournoiId): array
    {
        $map = [];
        $stmt = $this->connexion->prepare('INSERT INTO Terrains (nom, fk_idTournoi) VALUES (?, ?)');
        foreach ($rows as $r) {
            $stmt->execute([$r['nom'], $newTournoiId]);
            $map[$r['terrain_id']] = (int) $this->connexion->lastInsertId();
            // audio_path non repris : les fichiers audio ne sont pas sauvegardés ici
        }
        return $map;
    }

    private function restaurerCreneaux(array $rows, int $newTournoiId): array
    {
        $map = [];
        $stmt = $this->connexion->prepare(
            'INSERT INTO Creneaux (nom, tournoi_id, ordre, tempsChangementMinutes) VALUES (?, ?, ?, ?)'
        );
        foreach ($rows as $r) {
            $stmt->execute([$r['nom'], $newTournoiId, $r['ordre'], $r['tempsChangementMinutes'] ?? 0]);
            $map[$r['creneau_id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerArbitres(array $rows, int $newTournoiId, array $mapClub): array
    {
        $map = [];
        $stmt = $this->connexion->prepare('INSERT INTO Arbitres (nom, tournoi_id, club_id) VALUES (?, ?, ?)');
        foreach ($rows as $r) {
            $clubId = $mapClub[$r['club_id']] ?? null;
            if ($clubId === null) continue; // club introuvable/non restauré : on ignore l'arbitre
            $stmt->execute([$r['nom'], $newTournoiId, $clubId]);
            $map[$r['arbitre_id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerEquipes(array $rows, int $newTournoiId, array $mapClub, array $mapCategorie): array
    {
        $map = [];
        $stmt = $this->connexion->prepare(
            'INSERT INTO Equipes (nom, nomCoach, IsPresent, tournoi_id, club_id, categorie) VALUES (?,?,?,?,?,?)'
        );
        foreach ($rows as $r) {
            $clubId = $mapClub[$r['club_id']] ?? null;
            $categorieId = $mapCategorie[$r['categorie']] ?? null;
            if ($clubId === null || $categorieId === null) continue;
            $stmt->execute([$r['nom'], $r['nomCoach'] ?? null, $r['IsPresent'] ?? 1, $newTournoiId, $clubId, $categorieId]);
            $map[$r['id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerPoules(array $rows, int $newTournoiId, array $mapCategorie): array
    {
        $map = [];
        $stmt = $this->connexion->prepare(
            'INSERT INTO Poules (nom, is_classement, fk_idcategorie, tournoi_id) VALUES (?,?,?,?)'
        );
        foreach ($rows as $r) {
            $categorieId = $r['fk_idcategorie'] ? ($mapCategorie[$r['fk_idcategorie']] ?? null) : null;
            $stmt->execute([$r['nom'], $r['is_classement'] ?? 0, $categorieId, $newTournoiId]);
            $map[$r['id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerEquipePoule(array $rows, array $mapEquipe, array $mapPoule): void
    {
        $stmt = $this->connexion->prepare('INSERT IGNORE INTO EquipePoule (equipe_id, poule_id) VALUES (?, ?)');
        foreach ($rows as $r) {
            $equipeId = $mapEquipe[$r['equipe_id']] ?? null;
            $pouleId = $mapPoule[$r['poule_id']] ?? null;
            if ($equipeId === null || $pouleId === null) continue;
            $stmt->execute([$equipeId, $pouleId]);
        }
    }

    private function restaurerPhasesFinales(array $rows, int $newTournoiId): array
    {
        $map = [];
        $stmt = $this->connexion->prepare(
            'INSERT INTO phases_finales (tournoi_id, libelle, ordre, qualifies_par_poule) VALUES (?,?,?,?)'
        );
        foreach ($rows as $r) {
            $stmt->execute([$newTournoiId, $r['libelle'], $r['ordre'], $r['qualifies_par_poule'] ?? null]);
            $map[$r['id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerRencontres(array $rows, int $newTournoiId, array $mapEquipe, array $mapPoule, array $mapPhaseFinale): array
    {
        $map = [];
        $stmt = $this->connexion->prepare(
            'INSERT INTO Rencontres
             (type_rencontre_id, equipe1_id, equipe2_id, score1, score2, tour, heure, terrain, Arbitre,
              isTerminated, tournoi_id, poule_id, phase_finale_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        foreach ($rows as $r) {
            $stmt->execute([
                $r['type_rencontre_id'],
                $r['equipe1_id'] ? ($mapEquipe[$r['equipe1_id']] ?? null) : null,
                $r['equipe2_id'] ? ($mapEquipe[$r['equipe2_id']] ?? null) : null,
                $r['score1'], $r['score2'], $r['tour'], $r['heure'],
                $r['terrain'], $r['Arbitre'], $r['isTerminated'] ?? 0,
                $newTournoiId,
                $r['poule_id'] ? ($mapPoule[$r['poule_id']] ?? null) : null,
                $r['phase_finale_id'] ? ($mapPhaseFinale[$r['phase_finale_id']] ?? null) : null,
            ]);
            $map[$r['id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerLabels(array $rows, int $newTournoiId, array $mapCategorie): array
    {
        $map = [];
        $stmt = $this->connexion->prepare(
            'INSERT INTO Labels (description, couleur, tournoi_id, categorie_id) VALUES (?,?,?,?)'
        );
        foreach ($rows as $r) {
            $categorieId = $r['categorie_id'] ? ($mapCategorie[$r['categorie_id']] ?? null) : null;
            $stmt->execute([$r['description'], $r['couleur'], $newTournoiId, $categorieId]);
            $map[$r['label_id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerEliminationConfig(array $rows, int $newTournoiId, array $mapCategorie): void
    {
        $stmt = $this->connexion->prepare(
            'INSERT INTO elimination_config (tournoi_id, categorie_id, qualifies_par_poule) VALUES (?,?,?)'
        );
        foreach ($rows as $r) {
            $categorieId = $mapCategorie[$r['categorie_id']] ?? null;
            if ($categorieId === null) continue;
            $stmt->execute([$newTournoiId, $categorieId, $r['qualifies_par_poule']]);
        }
    }

    private function restaurerPlanification(array $rows, int $newTournoiId, array $mapTerrain, array $mapCreneau, array $mapRencontre, array $mapLabel, array $mapArbitre): void
    {
        $stmt = $this->connexion->prepare(
            'INSERT INTO Planification (terrain_id, creneau_id, rencontre_id, tournoi_id, label_id, arbitre_id)
             VALUES (?,?,?,?,?,?)'
        );
        foreach ($rows as $r) {
            $stmt->execute([
                $r['terrain_id'] ? ($mapTerrain[$r['terrain_id']] ?? null) : null,
                $r['creneau_id'] ? ($mapCreneau[$r['creneau_id']] ?? null) : null,
                $r['rencontre_id'] ? ($mapRencontre[$r['rencontre_id']] ?? null) : null,
                $newTournoiId,
                $r['label_id'] ? ($mapLabel[$r['label_id']] ?? null) : null,
                $r['arbitre_id'] ? ($mapArbitre[$r['arbitre_id']] ?? null) : null,
            ]);
        }
    }

    private function restaurerPersonnes(array $rows, int $newTournoiId): array
    {
        $map = [];
        $stmt = $this->connexion->prepare('INSERT INTO Personne (Nom, Prenom, Mail, tournoi_id) VALUES (?,?,?,?)');
        foreach ($rows as $r) {
            $stmt->execute([$r['Nom'], $r['Prenom'], $r['Mail'], $newTournoiId]);
            $map[$r['id']] = (int) $this->connexion->lastInsertId();
        }
        return $map;
    }

    private function restaurerPersonneTable(array $rows, int $newTournoiId, array $mapPersonne, array $mapTerrain): void
    {
        $stmt = $this->connexion->prepare(
            'INSERT INTO PersonneTable (personne_id, terrain_id, code_pin, url_key, sentMail, tournoi_id)
             VALUES (?,?,?,?,0,?)'
        );
        foreach ($rows as $r) {
            $personneId = $mapPersonne[$r['personne_id']] ?? null;
            $terrainId = $mapTerrain[$r['terrain_id']] ?? null;
            if ($personneId === null || $terrainId === null) continue;

            // code_pin / url_key recopiés à l'identique (pour que les liens/mails déjà envoyés
            // restent valides après restauration). ATTENTION : url_key doit être unique en base ;
            // si le tournoi d'origine existe encore en parallèle, ceci crée un doublon de url_key
            // (ambiguïté de lookup dans PersonneTableDao::recupererInformationsParCle()).
            $stmt->execute([
                $personneId,
                $terrainId,
                $r['code_pin'],
                $r['url_key'],
                $newTournoiId,
            ]);
        }
    }

    /* ================= Helpers ================= */

    private function fetchOne(string $sql, array $params): ?array
    {
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function fetchAll(string $sql, array $params): array
    {
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchAllIn(string $sqlTemplate, array $ids): array
    {
        $ids = array_values(array_unique($ids));
        if (!$ids) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->connexion->prepare(sprintf($sqlTemplate, $placeholders));
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}