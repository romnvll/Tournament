<?php

class MessageDAO {
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
     * Crée un message à destination d'une catégorie, d'une poule ou d'une équipe.
     * Exactement un seul des 3 identifiants (categorieId / pouleId / equipeId) doit être renseigné.
     */
    public function creerMessage(int $tournoiId, string $contenu, ?int $categorieId, ?int $pouleId, ?int $equipeId, int $utilisateurId): bool {
        $stmt = $this->connexion->prepare("
            INSERT INTO Messages (tournoi_id, contenu, categorie_id, poule_id, equipe_id, utilisateur_id)
            VALUES (:tournoi_id, :contenu, :categorie_id, :poule_id, :equipe_id, :utilisateur_id)
        ");

        $stmt->bindParam(':tournoi_id', $tournoiId, PDO::PARAM_INT);
        $stmt->bindParam(':contenu', $contenu);
        $stmt->bindParam(':utilisateur_id', $utilisateurId, PDO::PARAM_INT);

        if ($categorieId !== null) {
            $stmt->bindParam(':categorie_id', $categorieId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':categorie_id', null, PDO::PARAM_NULL);
        }

        if ($pouleId !== null) {
            $stmt->bindParam(':poule_id', $pouleId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':poule_id', null, PDO::PARAM_NULL);
        }

        if ($equipeId !== null) {
            $stmt->bindParam(':equipe_id', $equipeId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':equipe_id', null, PDO::PARAM_NULL);
        }

        return $stmt->execute();
    }

    public function supprimerMessage(int $id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Messages WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Historique des messages envoyés pour un tournoi (vue organisateur),
     * avec le libellé de la cible (nom de catégorie, poule ou équipe) et
     * le nombre d'équipes destinataires / nombre d'équipes ayant lu.
     */
    public function afficherHistoriqueMessages(int $tournoiId): array {
        $stmt = $this->connexion->prepare("
            SELECT
                m.id,
                m.contenu,
                m.date_creation,
                m.categorie_id,
                m.poule_id,
                m.equipe_id,
                cat.Nom_categorie AS nom_categorie,
                cat.Couleur AS couleur_categorie,
                p.nom AS nom_poule,
                pcat.Nom_categorie AS nom_categorie_poule,
                e.nom AS nom_equipe,
                ecat.Nom_categorie AS nom_categorie_equipe,
                (SELECT COUNT(*) FROM MessagesVues mv WHERE mv.message_id = m.id) AS nb_recu,
                (SELECT COUNT(*) FROM MessagesVues mv WHERE mv.message_id = m.id AND mv.statut = 'lu') AS nb_lu,
                (
                    CASE
                        WHEN m.categorie_id IS NOT NULL THEN (SELECT COUNT(*) FROM Equipes WHERE categorie = m.categorie_id)
                        WHEN m.poule_id IS NOT NULL THEN (SELECT COUNT(*) FROM EquipePoule WHERE poule_id = m.poule_id)
                        WHEN m.equipe_id IS NOT NULL THEN 1
                        ELSE (SELECT COUNT(*) FROM Equipes WHERE tournoi_id = :tournoi_id)
                    END
                ) AS nb_destinataires
            FROM Messages m
            LEFT JOIN Categorie cat ON m.categorie_id = cat.id_categorie
            LEFT JOIN Poules p ON m.poule_id = p.id
            LEFT JOIN Categorie pcat ON p.fk_idcategorie = pcat.id_categorie
            LEFT JOIN Equipes e ON m.equipe_id = e.id
            LEFT JOIN Categorie ecat ON e.categorie = ecat.id_categorie
            WHERE m.tournoi_id = :tournoi_id
            ORDER BY m.date_creation DESC
        ");
        $stmt->bindParam(':tournoi_id', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Liste des messages visibles par une équipe donnée :
     * - messages adressés directement à cette équipe
     * - messages adressés à une poule dans laquelle l'équipe est ACTUELLEMENT inscrite
     * - messages adressés à la catégorie de cette équipe
     * Inclut le statut de lecture (NULL si jamais vu, 'recu' ou 'lu').
     */
   /**
 * Liste des messages visibles par une équipe donnée, avec le statut de
 * lecture PROPRE AU VISITEUR (cookie), pas à l'équipe.
 */
public function afficherMessagesParEquipe(int $equipeId, string $visiteurId): array {
    $stmt = $this->connexion->prepare("
        SELECT DISTINCT
            m.id,
            m.contenu,
            m.date_creation,
            m.categorie_id,
            m.poule_id,
            m.equipe_id,
            mv.statut,
            mv.date_recu,
            mv.date_lu
        FROM Messages m
        INNER JOIN Equipes eq ON eq.id = :equipe_id
        LEFT JOIN EquipePoule ep ON ep.equipe_id = eq.id AND ep.poule_id = m.poule_id
        LEFT JOIN MessagesVues mv ON mv.message_id = m.id AND mv.visiteur_id = :visiteur_id
        WHERE
            m.equipe_id = eq.id
            OR m.categorie_id = eq.categorie
            OR ep.poule_id IS NOT NULL
            OR (m.categorie_id IS NULL AND m.poule_id IS NULL AND m.equipe_id IS NULL)
        ORDER BY m.date_creation DESC
    ");
    $stmt->bindParam(':equipe_id', $equipeId, PDO::PARAM_INT);
    $stmt->bindParam(':visiteur_id', $visiteurId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    /**
     * Nombre de messages non lus pour une équipe (pour la cloche).
     * Un message est considéré "non lu" s'il n'a aucune ligne dans
     * MessagesVues pour cette équipe, ou si son statut n'est pas 'lu'.
     */
    /**
 * Nombre de messages non lus PAR CE VISITEUR (pour la cloche).
 */
public function compterMessagesNonLusParEquipe(int $equipeId, string $visiteurId): int {
    $stmt = $this->connexion->prepare("
        SELECT COUNT(*) AS nb
        FROM Messages m
        INNER JOIN Equipes eq ON eq.id = :equipe_id
        LEFT JOIN EquipePoule ep ON ep.equipe_id = eq.id AND ep.poule_id = m.poule_id
        LEFT JOIN MessagesVues mv ON mv.message_id = m.id AND mv.visiteur_id = :visiteur_id
        WHERE
            (m.equipe_id = eq.id OR m.categorie_id = eq.categorie OR ep.poule_id IS NOT NULL OR (m.categorie_id IS NULL AND m.poule_id IS NULL AND m.equipe_id IS NULL))
            AND (mv.statut IS NULL OR mv.statut != 'lu')
    ");
    $stmt->bindParam(':equipe_id', $equipeId, PDO::PARAM_INT);
    $stmt->bindParam(':visiteur_id', $visiteurId);
    $stmt->execute();
    return (int)$stmt->fetch(PDO::FETCH_ASSOC)['nb'];
}

  /**
 * Marque un message comme "reçu" par ce visiteur (équipe_id est gardé
 * pour les stats organisateur), sans écraser un statut déjà 'lu'.
 */
public function marquerRecu(int $messageId, int $equipeId, string $visiteurId): void {
    $stmt = $this->connexion->prepare("
        INSERT INTO MessagesVues (message_id, equipe_id, visiteur_id, statut)
        VALUES (:message_id, :equipe_id, :visiteur_id, 'recu')
        ON DUPLICATE KEY UPDATE statut = statut
    ");
    $stmt->bindParam(':message_id', $messageId, PDO::PARAM_INT);
    $stmt->bindParam(':equipe_id', $equipeId, PDO::PARAM_INT);
    $stmt->bindParam(':visiteur_id', $visiteurId);
    $stmt->execute();
}

   /**
 * Marque tous les messages visibles par une équipe comme "reçus" pour CE
 * VISITEUR, en une requête (utilisé au chargement de la cloche).
 */
public function marquerTousRecusPourEquipe(int $equipeId, string $visiteurId): void {
    $stmt = $this->connexion->prepare("
        INSERT INTO MessagesVues (message_id, equipe_id, visiteur_id, statut)
        SELECT DISTINCT m.id, :equipe_id, :visiteur_id, 'recu'
        FROM Messages m
        INNER JOIN Equipes eq ON eq.id = :equipe_id_eq
        LEFT JOIN EquipePoule ep ON ep.equipe_id = eq.id AND ep.poule_id = m.poule_id
        WHERE (m.equipe_id = eq.id OR m.categorie_id = eq.categorie OR ep.poule_id IS NOT NULL OR (m.categorie_id IS NULL AND m.poule_id IS NULL AND m.equipe_id IS NULL))
        ON DUPLICATE KEY UPDATE statut = statut
    ");
    $stmt->bindParam(':equipe_id', $equipeId, PDO::PARAM_INT);
    $stmt->bindParam(':equipe_id_eq', $equipeId, PDO::PARAM_INT);
    $stmt->bindParam(':visiteur_id', $visiteurId);
    $stmt->execute();
}


  /**
 * Marque un message comme "lu" par ce visiteur.
 */
public function marquerLu(int $messageId, int $equipeId, string $visiteurId): void {
    $stmt = $this->connexion->prepare("
        INSERT INTO MessagesVues (message_id, equipe_id, visiteur_id, statut, date_lu)
        VALUES (:message_id, :equipe_id, :visiteur_id, 'lu', NOW())
        ON DUPLICATE KEY UPDATE statut = 'lu', date_lu = COALESCE(date_lu, NOW())
    ");
    $stmt->bindParam(':message_id', $messageId, PDO::PARAM_INT);
    $stmt->bindParam(':equipe_id', $equipeId, PDO::PARAM_INT);
    $stmt->bindParam(':visiteur_id', $visiteurId);
    $stmt->execute();
}

    /**
     * Détail des équipes destinataires d'un message et leur statut de lecture
     * (utilisé pour "quelle équipe a vu le message").
     */
    public function afficherStatutParEquipePourMessage(int $messageId): array {
    $stmt = $this->connexion->prepare("
        SELECT
            e.id AS equipe_id,
            e.nom AS nom_equipe,
            c.nom AS nom_club,
            COALESCE(mv.statut, 'non_recu') AS statut,
            mv.date_recu,
            mv.date_lu
        FROM Equipes e
        JOIN Clubs c ON c.id = e.club_id
        LEFT JOIN MessagesVues mv ON mv.message_id = :message_id_vues AND mv.equipe_id = e.id
        WHERE e.id IN (
            SELECT equipe_id
            FROM Messages
            WHERE id = :message_id_direct
              AND equipe_id IS NOT NULL

            UNION

            SELECT e2.id
            FROM Equipes e2
            INNER JOIN Messages mm
                ON mm.id = :message_id_categorie
               AND mm.categorie_id = e2.categorie

            UNION

            SELECT ep.equipe_id
            FROM EquipePoule ep
            INNER JOIN Messages mm
                ON mm.id = :message_id_poule
               AND mm.poule_id = ep.poule_id
        )
        ORDER BY statut DESC, nom_equipe ASC
    ");

    $stmt->bindParam(':message_id_vues', $messageId, PDO::PARAM_INT);
    $stmt->bindParam(':message_id_direct', $messageId, PDO::PARAM_INT);
    $stmt->bindParam(':message_id_categorie', $messageId, PDO::PARAM_INT);
    $stmt->bindParam(':message_id_poule', $messageId, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    /**
     * Liste des catégories utilisées dans ce tournoi (via les équipes inscrites),
     * pour peupler le select "Catégorie" du formulaire d'envoi.
     */
    public function listerCategoriesDuTournoi(int $tournoiId): array {
        $stmt = $this->connexion->prepare("
            SELECT DISTINCT cat.id_categorie, cat.Nom_categorie, cat.Couleur
            FROM Categorie cat
            INNER JOIN Equipes e ON e.categorie = cat.id_categorie
            WHERE e.tournoi_id = :tournoi_id
            ORDER BY cat.Nom_categorie ASC
        ");
        $stmt->bindParam(':tournoi_id', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Liste des poules du tournoi, avec le nom de leur catégorie (pour filtrage
     * côté JS en cascade quand l'organisateur sélectionne une catégorie).
     */
    public function listerPoulesDuTournoi(int $tournoiId): array {
        $stmt = $this->connexion->prepare("
            SELECT p.id, p.nom, p.fk_idcategorie AS categorie_id, cat.Nom_categorie AS nom_categorie
            FROM Poules p
            LEFT JOIN Categorie cat ON cat.id_categorie = p.fk_idcategorie
            WHERE p.tournoi_id = :tournoi_id
            ORDER BY cat.Nom_categorie ASC, p.nom ASC
        ");
        $stmt->bindParam(':tournoi_id', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Liste des équipes du tournoi, avec leur catégorie et leur(s) poule(s)
     * actuelle(s) (pour filtrage côté JS en cascade catégorie -> poule -> équipe).
     */
    public function listerEquipesDuTournoi(int $tournoiId): array {
        $stmt = $this->connexion->prepare("
            SELECT
                e.id,
                e.nom,
                e.categorie AS categorie_id,
                cat.Nom_categorie AS nom_categorie,
                GROUP_CONCAT(DISTINCT ep.poule_id) AS poule_ids
            FROM Equipes e
            LEFT JOIN Categorie cat ON cat.id_categorie = e.categorie
            LEFT JOIN EquipePoule ep ON ep.equipe_id = e.id
            WHERE e.tournoi_id = :tournoi_id
            GROUP BY e.id, e.nom, e.categorie, cat.Nom_categorie
            ORDER BY e.nom ASC
        ");
        $stmt->bindParam(':tournoi_id', $tournoiId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}