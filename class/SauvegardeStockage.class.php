<?php

/**
 * SauvegardeStockage
 * -------------------
 * Stocke les sauvegardes JSON de tournois sur le disque du serveur,
 * dans un dossier par utilisateur (sauvegardes/<utilisateur_id>/...),
 * protégé par un .htaccess (aucun accès direct, uniquement via PHP
 * après vérification que l'utilisateur est bien le propriétaire).
 */
class SauvegardeStockage
{
    private string $racine;

    public function __construct()
    {
        // Dossier "sauvegardes" à la racine du projet (à côté de class/, templates/, etc.)
        $this->racine = dirname(__DIR__) . '/sauvegardes';
        $this->assurerProtection($this->racine);
    }

    /* ================= Écriture ================= */

    /**
     * Enregistre une sauvegarde pour un utilisateur et retourne le nom de fichier généré.
     */
    public function enregistrer(int $utilisateurId, int $idTournoi, string $nomTournoi, string $jsonContenu): string
    {
        $dossier = $this->dossierUtilisateur($utilisateurId);

        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', $nomTournoi);
        $slug = trim($slug, '_') ?: 'tournoi';
        $nomFichier = sprintf('tournoi_%d_%s_%s.json', $idTournoi, $slug, date('Ymd_His'));

        file_put_contents($dossier . '/' . $nomFichier, $jsonContenu);

        return $nomFichier;
    }

    /* ================= Lecture / listing ================= */

    /**
     * Liste les sauvegardes d'un utilisateur, les plus récentes en premier.
     */
    public function lister(int $utilisateurId): array
    {
        $dossier = $this->dossierUtilisateur($utilisateurId);
        $fichiers = glob($dossier . '/*.json') ?: [];
        rsort($fichiers);

        $resultat = [];
        foreach ($fichiers as $chemin) {
            $resultat[] = [
                'nom'   => basename($chemin),
                'taille' => $this->formaterTaille(filesize($chemin)),
                'date'  => date('d/m/Y H:i', filemtime($chemin)),
            ];
        }
        return $resultat;
    }

    /**
     * Retourne le contenu JSON d'une sauvegarde si elle appartient bien à l'utilisateur, sinon null.
     */
    public function lire(int $utilisateurId, string $nomFichier): ?string
    {
        $chemin = $this->chemin($utilisateurId, $nomFichier);
        return $chemin ? file_get_contents($chemin) : null;
    }

    /* ================= Renommage ================= */

    /**
     * Renomme une sauvegarde. $nouveauNom est le nom "libre" choisi par l'utilisateur
     * (sans extension) ; l'extension .json est toujours forcée, et les caractères
     * sont nettoyés pour rester un nom de fichier sûr.
     *
     * Retourne le nouveau nom de fichier en cas de succès, ou null si la sauvegarde
     * d'origine n'existe pas, si le nouveau nom est vide une fois nettoyé, ou si un
     * fichier portant ce nom existe déjà.
     */
    public function renommer(int $utilisateurId, string $ancienNom, string $nouveauNom): ?string
    {
        $cheminActuel = $this->chemin($utilisateurId, $ancienNom);
        if (!$cheminActuel) {
            return null;
        }

        $nouveauNomNettoye = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($nouveauNom));
        $nouveauNomNettoye = trim($nouveauNomNettoye, '_');
        if ($nouveauNomNettoye === '') {
            return null;
        }

        $nouveauFichier = $nouveauNomNettoye . '.json';
        $nouveauChemin = $this->dossierUtilisateur($utilisateurId) . '/' . $nouveauFichier;

        if (basename($ancienNom) === $nouveauFichier) {
            return $nouveauFichier; // rien à faire
        }
        if (is_file($nouveauChemin)) {
            return null; // on n'écrase jamais une sauvegarde existante
        }

        return rename($cheminActuel, $nouveauChemin) ? $nouveauFichier : null;
    }

    /* ================= Suppression ================= */

    public function supprimer(int $utilisateurId, string $nomFichier): bool
    {
        $chemin = $this->chemin($utilisateurId, $nomFichier);
        if (!$chemin) return false;
        return unlink($chemin);
    }

    /* ================= Helpers ================= */

    private function dossierUtilisateur(int $utilisateurId): string
    {
        $dossier = $this->racine . '/' . $utilisateurId;
        if (!is_dir($dossier)) {
            mkdir($dossier, 0750, true);
        }
        return $dossier;
    }

    /**
     * Résout le chemin réel d'une sauvegarde en s'assurant qu'elle appartient bien à
     * l'utilisateur (basename() empêche toute traversée de répertoire du type ../../).
     */
    private function chemin(int $utilisateurId, string $nomFichier): ?string
    {
        $nomFichier = basename($nomFichier);
        $chemin = $this->dossierUtilisateur($utilisateurId) . '/' . $nomFichier;
        return is_file($chemin) ? $chemin : null;
    }

    private function formaterTaille(int $octets): string
    {
        if ($octets < 1024) return $octets . ' o';
        if ($octets < 1024 * 1024) return round($octets / 1024, 1) . ' Ko';
        return round($octets / (1024 * 1024), 1) . ' Mo';
    }

    /**
     * Pose un .htaccess interdisant tout accès HTTP direct au dossier des sauvegardes
     * (compatible Apache 2.2 et 2.4+). Appelé à chaque instanciation : sans effet si déjà présent.
     */
    private function assurerProtection(string $dossier): void
    {
        if (!is_dir($dossier)) {
            mkdir($dossier, 0750, true);
        }
        $htaccess = $dossier . '/.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, <<<HTACCESS
# Aucun accès HTTP direct : ces fichiers ne doivent être lus que par PHP (sauvegardeTournoi_traitement.php)
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>
HTACCESS
            );
        }
    }
}
