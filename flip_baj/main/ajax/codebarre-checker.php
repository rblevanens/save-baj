<?php
namespace flip_baj\main\ajax;

include ('../constantes.php');
if (isset($_POST["CodeBarreAjout"]) || isset($_POST["CodeBarreAjoutAdmin"]) ) {
    if (! isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        die();
    }
    include ('../pdo_connect.php');
    if (is_null($pdo)) {
        die('Could not connect to database!');
    }

    if (isset($_POST["CodeBarreAjout"])) {
        $CodeBarreAjout = $_POST["CodeBarreAjout"];
    } else if (isset($_POST["CodeBarreAjoutAdmin"])) {
        $CodeBarreAjout = $_POST["CodeBarreAjoutAdmin"];
    }

    // 1. On utilise ta requête d'origine pour vérifier si le code existe
    $statement = $pdo->prepare($SQL_4_checkcodebarre);
    $statement->execute(['code_barre' => $CodeBarreAjout]);
    $res = $statement->fetch();

    if (!isset($res['code_barre'])) {
        // Le code-barre n'existe pas en base : tout va bien !
        echo json_encode(array(
            "message1" => '',
            "message2" => '1'
        ));
    } else {
        // 2. Le code-barre existe ! On va vérifier si c'est une ÉTIQUETTE VIERGE
        // On fait une requête ciblée pour récupérer le nom du jeu avec certitude
        $stmtCheck = $pdo->prepare("SELECT nom_jeu FROM al_bourse_liste WHERE code_barre = :cb");
        $stmtCheck->execute([':cb' => $CodeBarreAjout]);
        $jeu = $stmtCheck->fetch();

        // Si on trouve le mot "VIERGE" dans le titre du jeu (peu importe les majuscules/minuscules)
        if ($jeu && strpos(strtoupper($jeu['nom_jeu']), 'VIERGE') !== false) {
            // C'est une étiquette vierge ! On laisse passer (le JS recevra un succès)
            echo json_encode(array(
                "message1" => '',
                "message2" => '1'
            ));
        } else {
            // C'est un VRAI doublon (ex: un jeu qui s'appelle "Catan" a déjà ce code) : on bloque !
            echo json_encode(array(
                "message1" => '<p class="bg-danger text-white p-2 rounded">Ce code-barre est déjà utilisé pour un autre jeu.</p>',
                "message2" => '0'
            ));
        }
    }
    $statement = null;
    $pdo = null;
}
?>