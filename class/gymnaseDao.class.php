<?php

class GymnaseDAO
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

    /**
     * Récupère tous les gymnases d'un utilisateur
     * @param int $userId
     * @return array
     */
    public function getGymnasesByUser(int $userId): array
    {
        $query = "SELECT * FROM Gymnases WHERE utilisateur_id = :userId ORDER BY nom ASC";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un gymnase par son ID (vérifie que l'utilisateur en est propriétaire)
     * @param int $gymnaseId
     * @param int $userId
     * @return array|null
     */
    public function getGymnaseById(int $gymnaseId, int $userId): ?array
    {
        $query = "SELECT * FROM Gymnases WHERE id = :id AND utilisateur_id = :userId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':id', $gymnaseId, PDO::PARAM_INT);
        $stmt->bindValue(':utilisateur_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crée un nouveau gymnase pour un utilisateur
     * @param int    $userId
     * @param string $nom
     * @param string $adresse
     * @param string $ville
     * @param string $codePostal
     * @param string|null $telephone
     * @param string|null $commentaire
     * @return int  ID du gymnase créé
     */
    public function creerGymnase(
        int $userId,
        string $nom,
        string $adresse,
        string $ville,
        string $codePostal,
        ?string $telephone = null,
        ?string $commentaire = null
    ): int {
        $query = "INSERT INTO Gymnases (utilisateur_id, nom, adresse, ville, code_postal, telephone, commentaire)
                  VALUES (:userId, :nom, :adresse, :ville, :codePostal, :telephone, :commentaire)";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':userId',      $userId,      PDO::PARAM_INT);
        $stmt->bindValue(':nom',         $nom,         PDO::PARAM_STR);
        $stmt->bindValue(':adresse',     $adresse,     PDO::PARAM_STR);
        $stmt->bindValue(':ville',       $ville,       PDO::PARAM_STR);
        $stmt->bindValue(':codePostal',  $codePostal,  PDO::PARAM_STR);
        $stmt->bindValue(':telephone',   $telephone,   PDO::PARAM_STR);
        $stmt->bindValue(':commentaire', $commentaire, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $this->connexion->lastInsertId();
    }


    /**
 * Attache (ou détache) un gymnase à un tournoi
 * Vérifie que le tournoi appartient à l'utilisateur
 * et que le gymnase (si fourni) lui appartient aussi
 *
 * @param int      $tournoiId
 * @param int      $userId
 * @param int|null $gymnaseId  null pour détacher le gymnase
 * @return bool
 */
public function attacherGymnaseATournoi(int $tournoiId, int $userId, ?int $gymnaseId): bool
{
    // Si un gymnase est fourni, vérifier qu'il appartient bien à cet utilisateur
    if ($gymnaseId !== null && !$this->appartientAUtilisateur($gymnaseId, $userId)) {
        return false;
    }

    $query = "UPDATE Tournois
              SET gymnase_id = :gymnaseId
              WHERE id = :tournoiId
              AND utilisateur_id = :userId";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':gymnaseId',  $gymnaseId,  $gymnaseId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':tournoiId',  $tournoiId,  PDO::PARAM_INT);
    $stmt->bindValue(':userId',     $userId,     PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->rowCount() > 0;
}

/**
 * Récupère le gymnase associé à un tournoi
 *
 * @param int $tournoiId
 * @return array|null  Les données du gymnase, ou null si aucun gymnase attaché
 */
public function getGymnaseByTournoiId(int $tournoiId): ?array
{
    $query = "SELECT g.*
              FROM Gymnases g
              INNER JOIN Tournois t ON t.gymnase_id = g.id
              WHERE t.id = :tournoiId";

    $stmt = $this->connexion->prepare($query);
    $stmt->bindValue(':tournoiId', $tournoiId, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

    /**
     * Modifie un gymnase existant (vérifie que l'utilisateur en est propriétaire)
     * @param int    $gymnaseId
     * @param int    $userId
     * @param string $nom
     * @param string $adresse
     * @param string $ville
     * @param string $codePostal
     * @param string|null $telephone
     * @param string|null $commentaire
     * @return bool
     */
    public function modifierGymnase(
        int $gymnaseId,
        int $userId,
        string $nom,
        string $adresse,
        string $ville,
        string $codePostal,
        ?string $telephone = null,
        ?string $commentaire = null
    ): bool {
        $query = "UPDATE Gymnases
                  SET nom         = :nom,
                      adresse     = :adresse,
                      ville       = :ville,
                      code_postal = :codePostal,
                      telephone   = :telephone,
                      commentaire = :commentaire
                  WHERE id = :id AND utilisateur_id = :userId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':nom',         $nom,         PDO::PARAM_STR);
        $stmt->bindValue(':adresse',     $adresse,     PDO::PARAM_STR);
        $stmt->bindValue(':ville',       $ville,       PDO::PARAM_STR);
        $stmt->bindValue(':codePostal',  $codePostal,  PDO::PARAM_STR);
        $stmt->bindValue(':telephone',   $telephone,   PDO::PARAM_STR);
        $stmt->bindValue(':commentaire', $commentaire, PDO::PARAM_STR);
        $stmt->bindValue(':id',          $gymnaseId,   PDO::PARAM_INT);
        $stmt->bindValue(':userId',      $userId,      PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprime un gymnase (vérifie que l'utilisateur en est propriétaire)
     * @param int $gymnaseId
     * @param int $userId
     * @return bool
     */
    public function supprimerGymnase(int $gymnaseId, int $userId): bool
    {
        $query = "DELETE FROM Gymnases WHERE id = :id AND utilisateur_id = :userId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':id',     $gymnaseId, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId,    PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Vérifie qu'un gymnase appartient bien à un utilisateur
     * @param int $gymnaseId
     * @param int $userId
     * @return bool
     */
    public function appartientAUtilisateur(int $gymnaseId, int $userId): bool
    {
        $query = "SELECT COUNT(*) FROM Gymnases WHERE id = :id AND utilisateur_id = :userId";
        $stmt = $this->connexion->prepare($query);
        $stmt->bindValue(':id',     $gymnaseId, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId,    PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }
}