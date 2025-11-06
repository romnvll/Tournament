<?php

class LicenceDAO {
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




    // Récupérer la licence d'un utilisateur
    public function getLicencesParUtilisateur(int $utilisateur_id): array {
    $stmt = $this->connexion->prepare("
        SELECT 
            L.id AS licence_id,
            L.utilisateur_id,
            
            L.licence_type_id,
            L.date_debut,
            L.date_fin,
              
            
            LT.nom AS type_licence,
            LT.limite_tournois,
            LT.prix,
            LT.limite_equipes
        FROM Licence L
        
        JOIN LicenceType LT ON L.licence_type_id = LT.id
        WHERE L.utilisateur_id = :utilisateur_id
    ");
    $stmt->bindParam(':utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getIdUserByToken(string $token): ?int {
    $stmt = $this->connexion->prepare("SELECT id FROM Utilisateurs WHERE email_token = :token");
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? (int)$result['id'] : null;
}

public function getUserInfosByToken(string $token): ?array {
    $stmt = $this->connexion->prepare("
        SELECT email, nom, prenom 
        FROM Utilisateurs 
        WHERE email_token = :token
    ");
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ?: null;
}



public function getLicenceDetailsByLicenceId(int $licenceId): ?array {
    $stmt = $this->connexion->prepare("
        SELECT l.*, lt.nom, lt.prix, lt.duree_jours
        FROM Licence l
        JOIN LicenceType lt ON l.licence_type_id = lt.id
        WHERE lt.id = :id
    ");
    $stmt->bindParam(':id', $licenceId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}



public function creerLicenceParDefautFromToken(string $token): void {
    // 1. Récupérer l'ID de l'utilisateur avec ce token
    $stmtUser = $this->connexion->prepare("SELECT id FROM Utilisateurs WHERE email_token = :token");
    $stmtUser->bindParam(':token', $token, PDO::PARAM_STR);
    $stmtUser->execute();
    $utilisateur = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($utilisateur) {
        $utilisateurId = $utilisateur['id'];

        // 2. Créer la licence gratuite (type 1) si elle n'existe pas déjà
        $stmtCheck = $this->connexion->prepare("SELECT id FROM Licence WHERE utilisateur_id = :utilisateur_id");
        $stmtCheck->bindParam(':utilisateur_id', $utilisateurId, PDO::PARAM_INT);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() === 0) {
            $stmtInsert = $this->connexion->prepare("
                INSERT INTO Licence (utilisateur_id, licence_type_id)
                VALUES (:utilisateur_id, 1)
            ");
            $stmtInsert->bindParam(':utilisateur_id', $utilisateurId, PDO::PARAM_INT);
            $stmtInsert->execute();
        }
    }
}



// Créer une licence gratuite (type ID 1) pour un utilisateur et un tournoi donné
public function creerLicenceParDefaut(int $utilisateurId): void {
    $stmt = $this->connexion->prepare("
        INSERT INTO Licence (utilisateur_id, licence_type_id)
        VALUES (:utilisateur_id, 1)
    ");
    $stmt->bindParam(':utilisateur_id', $utilisateurId, PDO::PARAM_INT);
    $stmt->bindParam(':tournoi_id', $tournoiId, PDO::PARAM_INT);
    $stmt->execute();
}

    // Modifier le type de licence d'un utilisateur
    public function modifierTypeLicence(int $utilisateurId, int $nouveauTypeId): bool {
        $stmt = $this->connexion->prepare("UPDATE Licence SET licence_type_id = :type_id WHERE utilisateur_id = :utilisateur_id");
        $stmt->bindParam(':type_id', $nouveauTypeId);
        $stmt->bindParam(':utilisateur_id', $utilisateurId);
        return $stmt->execute();
    }

    public function reinitialiserDateFin(int $utilisateurId): bool
{
    $stmt = $this->connexion->prepare("
        UPDATE Licence
        SET date_fin = NULL
        WHERE utilisateur_id = :utilisateur_id
    ");
    
    $stmt->bindParam(':utilisateur_id', $utilisateurId, PDO::PARAM_INT);
    
    return $stmt->execute();
}


    // Supprimer une licence d'un utilisateur
    public function supprimerLicence(int $utilisateurId): void {
        $stmt = $this->connexion->prepare("DELETE FROM Licence WHERE utilisateur_id = :utilisateur_id");
        $stmt->bindParam(':utilisateur_id', $utilisateurId);
        $stmt->execute();
    }

    // Récupérer tous les types de licence
    public function getTousLesTypesDeLicence(): array {
        $stmt = $this->connexion->prepare("SELECT * FROM LicenceType ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLicenceTypeByName(string $name): ?array {
        $stmt = $this->connexion->prepare("SELECT * FROM LicenceType WHERE nom = :nom");
        $stmt->bindParam(':nom', $name, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

public function updateLicenceForUser(int $utilisateurId, int $licenceTypeId, int $dureeJours): bool
{
    // Calcul des dates
    $dateDebut = new DateTime();
    $dateFin = (clone $dateDebut)->modify("+{$dureeJours} days");

    // Préparer la requête SQL
    $stmt = $this->connexion->prepare("
        UPDATE Licence
        SET licence_type_id = :licence_id,
            date_debut = :debut,
            date_fin = :fin
        WHERE utilisateur_id = :user_id
    ");

    // Lier les paramètres
    return $stmt->execute([
        'licence_id' => $licenceTypeId,
        'debut'       => $dateDebut->format('Y-m-d'),
        'fin'         => $dateFin->format('Y-m-d'),
        'user_id'     => $utilisateurId,
    ]);
}


public function retrograderLicencesExpirees(): int
{
    // 1. Récupérer l'ID de la licence gratuite
    $stmt = $this->connexion->prepare("SELECT id FROM LicenceType WHERE nom = 'Gratuite' LIMIT 1");
    $stmt->execute();
    $idGratuite = $stmt->fetchColumn();

    if (!$idGratuite) {
        throw new Exception("Licence gratuite non trouvée dans la table LicenceType.");
    }

    // 2. Définir la date du jour
    $today = (new DateTime())->format('Y-m-d');
    
    // 3. Mettre à jour les licences expirées
    $update = $this->connexion->prepare("
        UPDATE Licence
        SET licence_type_id = :idGratuite,
            date_debut = :today,
            date_fin = NULL
        WHERE date_fin IS NOT NULL AND date_fin < :today
    ");

    $update->execute([
        'idGratuite' => $idGratuite,
        'today' => $today,
    ]);

    // 4. Retourner le nombre de lignes affectées
    return $update->rowCount();
}


public function getJoursRestantsLicence(int $utilisateurId): ?int
{
    $stmt = $this->connexion->prepare("
        SELECT date_fin
        FROM Licence
        WHERE utilisateur_id = :id
        LIMIT 1
    ");
    $stmt->bindParam(':id', $utilisateurId, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || empty($result['date_fin'])) {
        // Pas de date de fin : licence gratuite ou non définie
        return null;
    }

    $aujourdHui = new DateTime();
    $dateFin = new DateTime($result['date_fin']);

    // Si déjà expirée
    if ($dateFin < $aujourdHui) {
        return 0;
    }

    $interval = $aujourdHui->diff($dateFin);

    return $interval->days;
}





}
