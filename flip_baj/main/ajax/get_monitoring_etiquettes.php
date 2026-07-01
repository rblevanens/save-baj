<?php
require_once '../pdo_connect.php';

// 1. Quel est le prochain code disponible ?
$stmtCode = $pdo->query("SELECT code_barre FROM al_bourse_liste WHERE UPPER(nom_jeu) LIKE '%TIQUETTE_VIERGE%' AND statut = 2 ORDER BY id ASC LIMIT 1");
$prochain_code = $stmtCode->fetchColumn();

// 2. Combien en reste-t-il au total ?
$stmtStock = $pdo->query("SELECT COUNT(*) FROM al_bourse_liste WHERE UPPER(nom_jeu) LIKE '%TIQUETTE_VIERGE%' AND statut = 2");
$stock = $stmtStock->fetchColumn();

$affichage_code = $prochain_code ? str_replace('Festival_', '', $prochain_code) : 'RUPTURE';

// On renvoie tout dans un seul paquet JSON
echo json_encode([
    'succes' => true,
    'code' => $affichage_code,
    'stock' => (int)$stock
]);
exit;