<?php
namespace flip_baj\main\jassmeux;

require_once '../pdo_connect.php';
require_once '../constantes.php';
require_once '../pdf/fpdf/fpdf_extended.php';

header('Content-Type: application/json');
$response = ['message1' => '', 'message2' => '0', 'pdf' => ''];

// --- MOTEUR DE CODE-BARRES (Code 39) ---
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

// Sécurité anti-doublon : cherche le premier numéro libre
function genererCodeUniqueJassMeux(\PDO $pdo) {
    $num = 10000;
    $configFile = __DIR__ . '/../tmp/config_compteur.txt';
    if (file_exists($configFile)) {
        $forced = intval(file_get_contents($configFile));
        if ($forced > $num) {
            $num = $forced;
        }
    }

    while (true) {
        $code = (string)$num;
        $stmt = $pdo->prepare('SELECT code_barre FROM al_bourse_liste WHERE code_barre = :cb AND annee = ' . annee_base);
        $stmt->execute([':cb' => 'Festival_' . $code]);
        if (!$stmt->fetchColumn()) return $code;
        $num++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileCSV'])) {
    $handle = fopen($_FILES['fileCSV']['tmp_name'], 'r');
    if ($handle !== false) {

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);
        $vendeurData = fgetcsv($handle, 1000, ';');

        $nom = trim($vendeurData[2] ?? '');
        $prenom = trim($vendeurData[3] ?? '');
        $adresse = trim($vendeurData[4] ?? '');
        $cp = trim($vendeurData[5] ?? '');
        $ville = trim($vendeurData[6] ?? '');
        $telephone = trim($vendeurData[7] ?? '');
        $email = trim($vendeurData[8] ?? '');
        $asso_nom = trim($vendeurData[9] ?? '');
        $asso_siege = trim($vendeurData[10] ?? '');

        if(empty($nom) || empty($prenom)) {
            echo json_encode(["message1" => "Erreur : Vendeur introuvable.", "message2" => "0"]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM al_bourse_users WHERE nom = ? AND prenom = ? AND telephone = ?");
        $stmt->execute([$nom, $prenom, $telephone]);
        $idVendeur = $stmt->fetchColumn();

        if (!$idVendeur) {
            $stmtInsert = $pdo->prepare("INSERT INTO al_bourse_users (nom, prenom, telephone, email, adresse, code_postal, ville, denomination_sociale, siege_social, attestation_signee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'False')");
            $stmtInsert->execute([$nom, $prenom, $telephone, $email, $adresse, $cp, $ville, $asso_nom, $asso_siege]);
            $idVendeur = $pdo->lastInsertId();
        }

        fgetcsv($handle, 1000, ';');
        fgetcsv($handle, 1000, ';');

        $inserted = 0;
        $ignored = 0;
        $etiquettes = [];

        // --- NOUVEAUTÉ : CAPTURE DE L'ADRESSE IP ---
        $ip_importateur = $_SERVER['REMOTE_ADDR'] ?? 'IP_INCONNUE';

        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            if (count($data) >= 4) {
                $nom_jeu = trim($data[2] ?? '');
                $prix_brut = trim($data[3] ?? '');
                $quantite_brut = trim($data[4] ?? '1');

                if ($nom_jeu === '' || $prix_brut === '') continue;

                if (!preg_match('/^\d+$/', $prix_brut) || intval($prix_brut) <= 0) {
                    $ignored++;
                    continue;
                }

                $prix = intval($prix_brut);
                $quantite = (is_numeric($quantite_brut) && intval($quantite_brut) > 0) ? intval($quantite_brut) : 1;

                for ($i = 0; $i < $quantite; $i++) {
                    $code_num = genererCodeUniqueJassMeux($pdo);
                    $code_barre_bdd = 'Festival_' . $code_num;

                    // --- NOUVEAUTÉ : INSERTION DE L'IP DANS ID_DEPOT ---
                    $stmt = $pdo->prepare("INSERT INTO al_bourse_liste (id_utilisateur, nom_jeu, prix, code_barre, statut, vigilance, id_depot, date_reception, annee) VALUES (?, ?, ?, ?, 2, 0, ?, NOW(), ?)");
                    $stmt->execute([$idVendeur, $nom_jeu, $prix, $code_barre_bdd, $ip_importateur, annee_base]);

                    $inserted++;
                    $etiquettes[] = ['nom' => $nom_jeu, 'prix' => $prix, 'code' => $code_num];
                }
            }
        }
        fclose($handle);

        if (!empty($etiquettes)) {
            $pdf = new \FPDF_Extended('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);

            $col = 0;
            $row = 0;
            $margeTop = 4.5;

            foreach ($etiquettes as $etiquette) {
                if ($col == 0 && $row == 0) $pdf->AddPage();

                $x = $col * 70;
                $y = $margeTop + ($row * 35);

                $pdf->SetFont('Arial', '', 10);
                $pdf->SetXY($x + 2, $y + 4);
                $pdf->Cell(66, 4, 'JEU N. ' . $etiquette['code'], 0, 1, 'C');

                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetXY($x + 2, $y + 9);
                $nom_jeu_court = mb_substr($etiquette['nom'], 0, 30, 'UTF-8');
                $pdf->Cell(66, 4, utf8_decode($nom_jeu_court), 0, 1, 'C');

                $pdf->SetFont('Arial', 'B', 16);
                $pdf->SetXY($x + 2, $y + 15);
                $pdf->Cell(66, 6, $etiquette['prix'] . ' ' . chr(128), 0, 1, 'C');

                imprimerCode39($pdf, $x + 20, $y + 22, $etiquette['code'], 0.35, 10);

                $col++;
                if ($col == 3) {
                    $col = 0;
                    $row++;
                    if ($row == 8) $row = 0;
                }
            }

            $pdfName = 'JassMeux_' . $idVendeur . '_' . time() . '.pdf';
            $pdfPath = __DIR__ . '/../tmp/' . $pdfName;
            if (!is_dir(dirname($pdfPath))) mkdir(dirname($pdfPath), 0777, true);
            $pdf->Output('F', $pdfPath);
            $response['pdf'] = $pdfName;
        }

        $response['message1'] = "Succès : **$inserted** étiquettes de jeux importées. ($ignored rejet(s) pour prix invalide).";
        $response['message2'] = '1';
    } else {
        $response['message1'] = "Impossible de lire le fichier.";
    }
}
echo json_encode($response);
exit;