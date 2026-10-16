<?php
require 'security.php';
require 'class/creneauxDao.class.php';

header('Content-Type: application/json');

$idTournoi = (int)($_GET['id_tournoi'] ?? 0);
$equipe1Id = (int)($_GET['equipe1'] ?? 0);
$equipe2Id = (int)($_GET['equipe2'] ?? 0);

if ($idTournoi === 0) {
    echo json_encode([]);
    exit;
}

$creneauxDao = new creneauxDao();
$slots = $creneauxDao->getCreneauxLibres($idTournoi, date('H:i:s'), $equipe1Id, $equipe2Id, 12);

echo json_encode($slots);