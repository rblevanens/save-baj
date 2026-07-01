<?php
namespace flip_baj\main\ajax;

require_once '../pdo_connect.php';

header('Content-Type: application/json');

// 1. RECHERCHE D'UNE ÉTIQUETTE VIERGE DISPONIBLE EN PRIORITÉ
// On cherche le code barre d'un jeu dont le nom contient "ETIQUETTE VIERGE" et qui n'est pas encore vendu
$stmtVierge = $pdo->query("SELECT code_barre FROM al_bourse_liste WHERE UPPER(nom_jeu) LIKE '%TIQUETTE_VIERGE%' AND statut = 2 ORDER BY id ASC LIMIT 1");
$codeVierge = $stmtVierge->fetchColumn();

$code_vierge_propre = null;
if ($codeVierge) {
    // Si on trouve "Festival_10042", on ne garde que "10042" pour le pré-remplir dans la case
    $code_vierge_propre = preg_replace('/[^0-9]/', '', $codeVierge);
}

// 2. Trouver le plus grand ID RÉELLEMENT PRÉSENT (pour la suite logique s'il n'y a pas de vierge)
$stmt = $pdo->query("SELECT code_barre FROM al_bourse_liste");
$codes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

$maxIdBdd = 6000;
foreach ($codes as $c) {
    $num = intval(preg_replace('/[^0-9]/', '', $c));
    if ($num > $maxIdBdd) {
        $maxIdBdd = $num;
    }
}
$lastId = $maxIdBdd;
$nextId = $lastId + 1;

// 3. Gestion du fichier de forçage (ton code d'origine intact)
$configFile = __DIR__ . '/../tmp/config_compteur.txt';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_id'])) {
    $forceId = intval($_POST['force_id']);
    if ($forceId > 0) {
        if (!is_dir(dirname($configFile))) mkdir(dirname($configFile), 0777, true);
        file_put_contents($configFile, $forceId);
    }
}

if (file_exists($configFile)) {
    $forced = intval(file_get_contents($configFile));
    if ($forced > $nextId) {
        $nextId = $forced;
    }
}

// 4. On renvoie tout au Javascript
echo json_encode([
    'success' => true,
    'last_id' => $lastId,
    'next_id' => $nextId,
    'code_vierge' => $code_vierge_propre // LA VARIABLE CLÉ
]);
exit;