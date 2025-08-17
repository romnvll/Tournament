<?php
class AssistanceDao {
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

   // Ajouter une demande avec message HTML (images incluses)
    public function ajouterDemande(int $utilisateur_id, string $message): bool {
        $stmt = $this->connexion->prepare("
            INSERT INTO Assistance (utilisateur_id, message)
            VALUES (:utilisateur_id, :message)
        ");
        return $stmt->execute([
            ':utilisateur_id' => $utilisateur_id,
            ':message' => $message
        ]);
    }

    // Récupérer toutes les demandes d’un utilisateur
    public function getDemandesParUtilisateur(int $utilisateur_id): array {
        $stmt = $this->connexion->prepare("
            SELECT * FROM Assistance
            WHERE utilisateur_id = :id
            ORDER BY date_creation DESC
        ");
        $stmt->execute([':id' => $utilisateur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer toutes les demandes (utile pour admin)
    public function getToutesLesDemandes(): array {
        $stmt = $this->connexion->query("
            SELECT a.*, u.nom, u.prenom, u.email
            FROM Assistance a
            JOIN Utilisateurs u ON a.utilisateur_id = u.id
            ORDER BY a.date_creation DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}