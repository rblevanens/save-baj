<?php
namespace flip_baj\main;

require_once 'pdo_connect.php';

$filename = "sauvegarde_flip_baj_" . date("Y-m-d_H-i-s") . ".sql";

header("Content-Type: application/sql");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// On récupère le nom de la base de données actuelle
$dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

$tables = [];
$views = [];

// 0. Séparation des Vraies Tables et des Vues
$stmt = $pdo->query("SHOW FULL TABLES");
while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
    if ($row[1] == 'VIEW') {
        $views[] = $row[0];
    } else {
        $tables[] = $row[0];
    }
}

// 1. SAUVEGARDE DES TABLES (Structure + Données)
foreach ($tables as $table) {
    echo "DROP TABLE IF EXISTS `$table`;\n";
    $stmtCreate = $pdo->query("SHOW CREATE TABLE `$table`");
    $rowCreate = $stmtCreate->fetch(\PDO::FETCH_NUM);
    echo $rowCreate[1] . ";\n\n";

    $stmtData = $pdo->query("SELECT * FROM `$table`");
    while ($row = $stmtData->fetch(\PDO::FETCH_ASSOC)) {
        $keys = array_keys($row);
        $clean_keys = array_map(function($k) { return "`$k`"; }, $keys);

        $values = array_values($row);
        $clean_values = array_map(function($v) use ($pdo) {
            if ($v === null) return 'NULL';
            return $pdo->quote($v);
        }, $values);

        echo "INSERT INTO `$table` (" . implode(', ', $clean_keys) . ") VALUES (" . implode(', ', $clean_values) . ");\n";
    }
    echo "\n\n";
}

// 2. SAUVEGARDE DES VUES (Structure uniquement, sans DEFINER)
foreach ($views as $view) {
    echo "DROP VIEW IF EXISTS `$view`;\n";
    $stmtCreate = $pdo->query("SHOW CREATE VIEW `$view`");
    $rowCreate = $stmtCreate->fetch(\PDO::FETCH_ASSOC);
    $createViewSql = preg_replace('/DEFINER\s*=\s*[^\s]+\s*/i', '', $rowCreate['Create View']);
    echo $createViewSql . ";\n\n";
}

// 3. SAUVEGARDE DES TRIGGERS (Déclencheurs)
$stmtTriggers = $pdo->query("SHOW TRIGGERS");
while ($row = $stmtTriggers->fetch(\PDO::FETCH_ASSOC)) {
    $trigger = $row['Trigger'];
    echo "DROP TRIGGER IF EXISTS `$trigger`;\n";
    $stmtCreate = $pdo->query("SHOW CREATE TRIGGER `$trigger`");
    $rowCreate = $stmtCreate->fetch(\PDO::FETCH_ASSOC);
    $createTriggerSql = preg_replace('/DEFINER\s*=\s*[^\s]+\s*/i', '', $rowCreate['SQL Original Statement']);
    echo $createTriggerSql . ";\n\n";
}

// 4. SAUVEGARDE DES PROCÉDURES (Routines)
$stmtProcs = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = '$dbName'");
while ($row = $stmtProcs->fetch(\PDO::FETCH_ASSOC)) {
    $proc = $row['Name'];
    echo "DROP PROCEDURE IF EXISTS `$proc`;\n";
    $stmtCreate = $pdo->query("SHOW CREATE PROCEDURE `$proc`");
    $rowCreate = $stmtCreate->fetch(\PDO::FETCH_ASSOC);
    $createProcSql = preg_replace('/DEFINER\s*=\s*[^\s]+\s*/i', '', $rowCreate['Create Procedure']);
    echo $createProcSql . ";\n\n";
}

// 5. SAUVEGARDE DES FONCTIONS
$stmtFuncs = $pdo->query("SHOW FUNCTION STATUS WHERE Db = '$dbName'");
while ($row = $stmtFuncs->fetch(\PDO::FETCH_ASSOC)) {
    $func = $row['Name'];
    echo "DROP FUNCTION IF EXISTS `$func`;\n";
    $stmtCreate = $pdo->query("SHOW CREATE FUNCTION `$func`");
    $rowCreate = $stmtCreate->fetch(\PDO::FETCH_ASSOC);
    $createFuncSql = preg_replace('/DEFINER\s*=\s*[^\s]+\s*/i', '', $rowCreate['Create Function']);
    echo $createFuncSql . ";\n\n";
}

exit;
?>