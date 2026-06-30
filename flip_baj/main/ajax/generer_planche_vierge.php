<?php
namespace flip_baj\main\ajax;

require_once '../pdo_connect.php';
require_once '../constantes.php';
require_once '../pdf/fpdf/fpdf_extended.php';

header('Content-Type: application/json');

try {
    // --- 1. MOTEUR DE CODE-BARRES ---
    function imprimerCode39($pdf, $xpos, $ypos, $code, $baseline=0.3, $height=10) {
        $code = '*' . strtoupper($code) . '*';
        $barChar = [
            '0'=>'bwbWBwBwb', '1'=>'BwbWbwbwB', '2'=>'bwBWbwbwB', '3'=>'BwBWbwbwb',
            '4'=>'bwbWBwbwB', '5'=>'BwbWBwbwb', '6'=>'bwBWBwbwb', '7'=>'bwbWbwBwB',
            '8'=>'BwbWbwBwb', '9'=>'bwBWbwBwb', '-'=>'bWbwbwBwB', '*'=>'bWbwBwBwb'
        ];
        $wide = $baseline * 3;
        $narrow = $baseline;
        $pdf->SetFillColor(0, 0, 0);

        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            if (!isset($barChar[$char])) continue;
            $seq = $barChar[$char];
            for ($j = 0; $j < 9; $j++) {
                $w = (strtolower($seq[$j]) == $seq[$j]) ? $narrow : $wide;
                if ($j % 2 == 0) $pdf->Rect($xpos, $ypos, $w, $height, 'F');
                $xpos += $w;
            }
            $xpos += $narrow;
        }
    }

    // --- 2. TROUVER LE PROCHAIN ID LIBRE ---
    $stmt = $pdo->query("SELECT code_barre FROM al_bourse_liste");
    $codes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    $maxIdBdd = 6000;
    foreach ($codes as $c) {
        $num = intval(preg_replace('/[^0-9]/', '', $c));
        if ($num > $maxIdBdd) {
            $maxIdBdd = $num;
        }
    }

    $nextId = $maxIdBdd + 1;
    $configFile = __DIR__ . '/../tmp/config_compteur.txt';

    if (file_exists($configFile)) {
        $forced = intval(file_get_contents($configFile));
        if ($forced > $nextId) {
            $nextId = $forced;
        }
    }

    // --- 3. CRÉATION DES 24 JEUX FANTÔMES ---
    $etiquettes = [];
    $nb_etiquettes = 24;
    $ip_creation = $_SERVER['REMOTE_ADDR'] ?? 'IP_INCONNUE';

    for ($i = 0; $i < $nb_etiquettes; $i++) {
        $code_num = $nextId + $i;
        $code_barre_bdd = 'Festival_' . $code_num;

        // Insertion du jeu vierge (id_utilisateur = 1 pour l'admin, statut 2 = en stock)
        $stmtInsert = $pdo->prepare("INSERT INTO al_bourse_liste (id_utilisateur, nom_jeu, prix, code_barre, statut, vigilance, id_depot, date_reception, annee) VALUES (1, '[ÉTIQUETTE VIERGE]', 0, ?, 2, 0, ?, NOW(), ?)");
        $stmtInsert->execute([$code_barre_bdd, $ip_creation, annee_base]);

        $etiquettes[] = $code_num;
    }

    // On dit au système que les 24 prochains numéros sont désormais réservés !
    $prochainVraiId = $nextId + $nb_etiquettes;
    if (!is_dir(dirname($configFile))) mkdir(dirname($configFile), 0777, true);
    file_put_contents($configFile, $prochainVraiId);

    // --- 4. GÉNÉRATION DU PDF ---
    $pdf = new \FPDF_Extended('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);

    $col = 0;
    $row = 0;
    $margeTop = 1.7;

    foreach ($etiquettes as $code) {
        $x = $col * 67;
        $y = $margeTop + ($row * 36.5);

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY($x + 2, $y + 4);
        $pdf->Cell(66, 4, 'JEU N. ' . $code, 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($x + 10, $y + 16);
        $pdf->Cell(66, 4, 'Prix: ................................... ' . chr(128), 0, 1, 'L');

        imprimerCode39($pdf, $x + 20, $y + 22, $code, 0.35, 10);

        $col++;
        if ($col == 3) {
            $col = 0;
            $row++;
            if ($row == 8 && $code != end($etiquettes)) {
                $row = 0;
                $pdf->AddPage();
            }
        }
    }

    $pdfName = 'Planche_Vierge_' . date('Ymd_His') . '.pdf';
    $dossierVierge = 'planche_vierge';
    $pdfPath = __DIR__ . '/../tmp/' . $dossierVierge . '/' . $pdfName;

    if (!is_dir(dirname($pdfPath))) mkdir(dirname($pdfPath), 0777, true);
    $pdf->Output('F', $pdfPath);

    // On renvoie le "Feu Vert" au Javascript !
    echo json_encode(['success' => true, 'pdf' => $dossierVierge . '/' . $pdfName]);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>