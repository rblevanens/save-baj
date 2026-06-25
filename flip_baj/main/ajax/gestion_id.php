<?php
namespace flip_baj\main\ajax;

require_once '../pdo_connect.php';
require_once '../constantes.php';

header('Content-Type: application/json');

$configFile = __DIR__ . '/../tmp/config_compteur.txt';

// 1. Sauvegarde du forçage si on clique sur "Appliquer"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_id'])) {
    $forceId = intval($_POST['force_id']);
    if ($forceId > 0) {
        if (!is_dir(dirname($configFile))) mkdir(dirname($configFile), 0777, true);

        $resultat_ecriture = file_put_contents($configFile, $forceId);
        if ($resultat_ecriture === false) {
            echo json_encode(['success' => false, 'error' => "Permissions refusées pour écrire dans tmp/config_compteur.txt"]);
            exit;
        }
    }
}

// 2. Trouver le plus grand ID RÉELLEMENT PRÉSENT dans la base
$stmt = $pdo->query("SELECT code_barre FROM al_bourse_liste");
$codes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

// On met un plancher basique (le minimum syndical)
$maxIdBdd = 6000;
foreach ($codes as $c) {
    // On nettoie "Festival_10042" pour ne garder que "10042"
    $num = intval(preg_replace('/[^0-9]/', '', $c));
    if ($num > $maxIdBdd) {
        $maxIdBdd = $num;
    }
}

// Le dernier jeu VRAIMENT utilisé en base
$lastId = $maxIdBdd;
$nextId = $lastId + 1;

// 3. Le fichier de forçage a-t-il un numéro PLUS GRAND ?
if (file_exists($configFile)) {
    $forced = intval(file_get_contents($configFile));

    // Si l'admin a forcé 15000, et que la BDD en est à 10050 -> On prend 15000
    // Si l'admin a forcé 10000, mais que la BDD en est DÉJÀ à 10050 -> On IGNORE le forçage (sécurité)
    if ($forced > $nextId) {
        $nextId = $forced;
    }
}

// 4. On renvoie TOUT UNE SEULE FOIS au JavaScript
echo json_encode([
    'success' => true,
    'last_id' => $lastId,
    'next_id' => $nextId
]);