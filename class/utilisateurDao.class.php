<?php

class UtilisateurDAO {
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

     public function getAllUtilisateurs() {
        $stmt = $this->connexion->query("SELECT * FROM Utilisateurs");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ajouterUtilisateur(string $nom, string $email, string $password): void {
        $hash = hash('sha256', $password);
        $stmt = $this->connexion->prepare("INSERT INTO Utilisateurs (nom, email, password) VALUES (:nom, :email, :password)");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hash);
        $stmt->execute();
    }

    public function verifierConnexion(string $email, string $password): ?array {
        $stmt = $this->connexion->prepare("SELECT * FROM Utilisateurs WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur && hash('sha256', $password) === $utilisateur['password']) {
            return $utilisateur;
        }

        return null;
    }

    public function getUtilisateurById(int $id): ?array {
        $stmt = $this->connexion->prepare("SELECT * FROM Utilisateurs WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUtilisateurByEmail(string $email): ?array {
        $stmt = $this->connexion->prepare("SELECT * FROM Utilisateurs WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function modifierUtilisateur(int $id, string $nom, string $email): bool {
        $stmt = $this->connexion->prepare("UPDATE Utilisateurs SET nom = :nom, email = :email WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }

    public function changerMotDePasse(int $id, string $ancien, string $nouveau): bool {
        $stmt = $this->connexion->prepare("SELECT password FROM Utilisateurs WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

       if ($resultat && password_verify($ancien, $resultat['password'])) {
        $nouveauHash = password_hash($nouveau, PASSWORD_DEFAULT);
        $updateStmt = $this->connexion->prepare("UPDATE Utilisateurs SET password = :password WHERE id = :id");
        $updateStmt->bindParam(':password', $nouveauHash);
        $updateStmt->bindParam(':id', $id);
        return $updateStmt->execute();
    }

        return false;
    }

    public function supprimerUtilisateur(int $id): void {
        $stmt = $this->connexion->prepare("DELETE FROM Utilisateurs WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function mettreAJourDerniereConnexion(int $id): void
            {
                $stmt = $this->connexion->prepare("
                    UPDATE Utilisateurs 
                    SET dernier_login = NOW()
                    WHERE id = :id
                ");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
            }

            public function getUserByToken(string $token): ?array {
    $stmt = $this->connexion->prepare("SELECT * FROM Utilisateurs WHERE email_token = :token");
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ;
}


    public function getTousLesUtilisateurs(): array {
        $stmt = $this->connexion->prepare("SELECT id, nom, email FROM Utilisateurs ORDER BY nom ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
