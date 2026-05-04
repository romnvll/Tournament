<?php
require('security.php');
require_once 'class/gymnaseDao.class.php';

$gymnaseDao = new GymnaseDAO();
$userId = (int) $userData['id'];

$action = $_POST['action'] ?? '';

switch ($action) {

    // ─────────────────────────────────────────────
    // CRÉER un gymnase
    // ─────────────────────────────────────────────
    case 'creer':
        $nom        = trim($_POST['nom']        ?? '');
        $adresse    = trim($_POST['adresse']    ?? '');
        $ville      = trim($_POST['ville']      ?? '');
        $codePostal = trim($_POST['code_postal'] ?? '');
        $telephone  = trim($_POST['telephone']  ?? '') ?: null;
        $commentaire = trim($_POST['commentaire'] ?? '') ?: null;

        if (empty($nom) || empty($adresse) || empty($ville) || empty($codePostal)) {
            header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'])
                . '&gestionGymnases=true&error=champs_manquants');
            exit;
        }

        $gymnaseDao->creerGymnase($userId, $nom, $adresse, $ville, $codePostal, $telephone, $commentaire);
        header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'])
            . '&gestionGymnases=true&status=gymnase_cree');
        exit;

    // ─────────────────────────────────────────────
    // MODIFIER un gymnase
    // ─────────────────────────────────────────────
    case 'modifier':
        $gymnaseId  = (int) ($_POST['gymnase_id'] ?? 0);
        $nom        = trim($_POST['nom']         ?? '');
        $adresse    = trim($_POST['adresse']     ?? '');
        
        $ville      = trim($_POST['ville']       ?? '');
        $codePostal = trim($_POST['code_postal'] ?? '');
        $telephone  = trim($_POST['telephone']   ?? '') ?: null;
        $commentaire = trim($_POST['commentaire'] ?? '') ?: null;
        
        if ($gymnaseId === 0 || empty($nom) || empty($adresse) || empty($ville) || empty($codePostal)) {
            header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'])
                . '&gestionGymnases=true&error=champs_manquants');
            exit;
        }

        // Sécurité : on vérifie que le gymnase appartient à cet utilisateur
        if (!$gymnaseDao->appartientAUtilisateur($gymnaseId, $userId)) {
            header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'])
                . '&gestionGymnases=true&error=acces_refuse');
            exit;
        }

        $gymnaseDao->modifierGymnase($gymnaseId, $userId, $nom, $adresse, $ville, $codePostal, $telephone, $commentaire);
        header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'])
         . '&gestionGymnases=true&status=gymnase_modifie');
        exit;

    // ─────────────────────────────────────────────
    // SUPPRIMER un gymnase
    // ─────────────────────────────────────────────
    case 'supprimer':
        $gymnaseId = (int) ($_POST['gymnase_id'] ?? 0);

        if ($gymnaseId === 0) {
            header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'])
                . '&gestionGymnases=true&error=id_manquant');
            exit;
        }

        // Sécurité : on vérifie que le gymnase appartient à cet utilisateur
        if (!$gymnaseDao->appartientAUtilisateur($gymnaseId, $userId)) {
            header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'])
                . '&gestionGymnases=true&error=acces_refuse');
            exit;
        }

        $gymnaseDao->supprimerGymnase($gymnaseId, $userId);
        header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'])
            . '&gestionGymnases=true&status=gymnase_supprime');
        exit;

    default:
        header('Location: mesPreferences.php?id_tournoi=' . intval($_POST['id_tournoi'] ?? 0)
            . '&gestionGymnases=true&error=action_inconnue');
        exit;
}