<?php
namespace flip_baj\main;
use PDO, PDOException;

/**
 * Vérifie si le code-barre est valide et le formate si nécessaire.
 *
 * @param codeBarreAVerifier integer Le code-barre à vérifier.
 * @return string Le code-barre vérifié et formaté, ou une chaîne vide si le code-barre n'est pas valide.
 */
function VerifCodeBarre($codeBarreAVerifier)
{

  $Resultat = "";
  $code_barre_len = strlen("Festival_nnnnn");
  $codeBarreAVerifier = trim($codeBarreAVerifier);
  if (strlen($codeBarreAVerifier) <= 5.0) {
    while (strlen($codeBarreAVerifier) < 5.0) {
      $codeBarreAVerifier = "0".$codeBarreAVerifier;
    }
    $codeBarreAVerifier = "Festival_".$codeBarreAVerifier;
  } else {
    if (strlen($codeBarreAVerifier) != $code_barre_len) {
      return $Resultat;
    }
  }
  if (substr($codeBarreAVerifier,0,$code_barre_len-5)!='Festival_')
  	{ return $Resultat; }

  $re = "/(.*)_[0-9][0-9][0-9][0-9][0-9]/";
  if (!preg_match($re, $codeBarreAVerifier)) {
    return $Resultat;
  }
  $Resultat = $codeBarreAVerifier;
  return $Resultat;
}


/**
 * Calcule le prix rendu à partir du prix initial.
 *
 * @param prix integer Le prix initial.
 * @return integer Le prix rendu en minorant de 1/6 le prix initial.
 */
function PrixRendu($prix)
{
    // Calcul du prix rendu à partir du prix initial (prix - 1/6 du prix).
    $resultat = $prix - ceil($prix / 6.);
    return $resultat;
}

/**
 * Calcule le prix initial à partir du prix rendu.
 *
 * @param prixRendu integer Le prix rendu.
 * @return integer Le prix initial, prix rendu avec une majoration de 20% arrondi à l'entier supérieur.
 */
function PrixRendu2Prix($prixRendu)
{
    // Calcul du prix initial à partir du prix rendu avec une majoration de 20% (prix rendu + 20%).
    $resultat = $prixRendu + ceil($prixRendu * .2);
    return $resultat;
}

/** Cette fonction prend en argument l'id d'un vendeur et renvoie du code HTML pour afficher
 * la liste des dons qui lui sont associés.
 * @param { Integer } id - Id du vendeur as integer.
 * @return {HTMLElement} ret - Liste en HTML des dons.
 * */
function AfficherDons($id) {
    $ret ='';
    include ('pdo_connect.php');
    include ('constantes.php');
    if (is_null($pdo)) {
        die('Could not connect to database!');
    }
    try {
        $statement = $pdo->prepare($SQL_47_get_dons);
    } catch (PDOException $e) {
        print "Erreur !: " . $e->getMessage() . "<br/>";
        die();
    }
    $statement->execute([
        'id'=>$id,
        'type'=>'Non remboursement'
    ]);
    $nb=0;
    //définition d'un tableau pour associer le numéro de mois à son écriture en lettres.
    $mois =array(1=>" janvier "," février "," mars "," avril "," mai "," juin ",
        " juillet "," août "," septembre "," octobre "," novembre "," décembre ");
    while ($don[$nb] = $statement->fetch(PDO::FETCH_ASSOC)) {
        $annee=substr($don[$nb]['date_don'],0,4);
        $numeromois=substr($don[$nb]['date_don'],5,2);
        $numeromois=$numeromois[0]=='0' ? $numeromois[1] : $numeromois;
        $jour=substr($don[$nb]['date_don'],8,2);
        $heure=substr($don[$nb]['date_don'],11,2);
        $minute=substr($don[$nb]['date_don'],14,2);
        //$seconde=substr($don[$nb]['date_don'],17,2); //--> pas sûr de l'utilité de la précision à la seconde..
        $don[$nb]['date_don']=$jour.$mois[$numeromois].$annee.' à '.$heure.'h'.$minute;
        $nb+=1;
    }
    if ($nb > 0){
        $ret .= '<ul class="list-group mt-2"> <li class="list-group-item d-flex justify-content-between align-items-center">Liste des dons</li>';
    }
    for ($i = 0; $i < $nb; $i++) {
        $ret .= '<li class="list-group-item d-flex justify-content-between align-items-center"><span>'.$don[$i]['montant_don'].'€ le '.$don[$i]['date_don'].'</span></li>';
    }
    $ret .= '</ul>';
    return $ret;
}

function AfficherTrans($type) {
    $ret ='';
    include ('pdo_connect.php');
    include ('constantes.php');
    if (is_null($pdo)) {
        die('Could not connect to database!');
    }
    try {
        $statement = $pdo->prepare($SQL_43_getTrans);
    } catch (PDOException $e) {
        print "Erreur !: " . $e->getMessage() . "<br/>";
        die();
    }
    $statement->execute([
        'type' =>$type
    ]);
    $nb=0;
    //définition d'un tableau pour associer le numéro de mois à son écriture en lettres.
    $mois =array(1=>" janvier "," février "," mars "," avril "," mai "," juin ",
        " juillet "," août "," septembre "," octobre "," novembre "," décembre ");
    while ($trans[$nb] = $statement->fetch(PDO::FETCH_ASSOC)) {
        $annee=substr($trans[$nb]['date'],0,4);
        $numeromois=substr($trans[$nb]['date'],5,2);
        $numeromois=$numeromois[0]=='0' ? $numeromois[1] : $numeromois;
        $jour=substr($trans[$nb]['date'],8,2);
        $heure=substr($trans[$nb]['date'],11,2);
        $minute=substr($trans[$nb]['date'],14,2);
        //$seconde=substr($don[$nb]['date_don'],17,2); //--> pas sûr de l'utilité de la précision à la seconde..
        $trans[$nb]['date']=$jour.$mois[$numeromois].$annee.' à '.$heure.'h'.$minute;
        $nb+=1;
    }
    if ($nb > 0){
        $ret .= '<ul class="list-group mt-2"> <li class="list-group-item d-flex justify-content-between align-items-center">Liste des transaction</li>';
    }
    for ($i = 0; $i < $nb; $i++) {
        $ret .= '<li class="list-group-item d-flex justify-content-between align-items-center"><span>'.$trans[$i]['montant_total'].'€ le '.$trans[$i]['date'].'</span></li>';
    }
    $ret .= '</ul>';
    return $ret;
}

/** Cette fonction prend en argument l'id d'un vendeur et renvoie du code HTML pour afficher
 * la liste des remboursements qui lui sont associés.
 * @param { Integer } id - Id du vendeur as integer.
 * @return {HTMLElement} ret - Liste en HTML des remboursements.
 * */
function AfficherRemb($id) {
    $ret ='';
    include ('pdo_connect.php');
    include ('constantes.php');
    if (is_null($pdo)) {
        die('Could not connect to database!');
    }
    try {
        $statement = $pdo->prepare($SQL_46_get_remboursement);
    } catch (PDOException $e) {
        print "Erreur !: " . $e->getMessage() . "<br/>";
        die();
    }
    $statement->execute([
        'id'=>$id
    ]);
    $nb=0;
    //définition d'un tableau pour associer le numéro de mois à son écriture en lettres.
    $mois =array(1=>" janvier "," février "," mars "," avril "," mai "," juin ",
        " juillet "," août "," septembre "," octobre "," novembre "," décembre ");
    while ($remb[$nb] = $statement->fetch(PDO::FETCH_ASSOC)) {
        $annee=substr($remb[$nb]['date_remb'],0,4);
        $numeromois=substr($remb[$nb]['date_remb'],5,2);
        $numeromois=$numeromois[0]=='0' ? $numeromois[1] : $numeromois;
        $jour=substr($remb[$nb]['date_remb'],8,2);
        $heure=substr($remb[$nb]['date_remb'],11,2);
        $minute=substr($remb[$nb]['date_remb'],14,2);
        //$seconde=substr($remb[$nb]['date_remb'],17,2); //--> pas sûr de l'utilité de la précision à la seconde..
        $remb[$nb]['date_remb']=$jour.$mois[$numeromois].$annee.' à '.$heure.'h'.$minute;
        $nb+=1;
    }
    if ($nb > 0){
        $ret .= '<ul class="list-group mt-2"> <li class="list-group-item d-flex justify-content-between align-items-center">Liste des remboursements</li>';
    }
    for ($i = 0; $i < $nb; $i++) {
        $ret .= '<li class="list-group-item d-flex justify-content-between align-items-center"><span>'.$remb[$i]['montant_remb'].'€ en '.$remb[$i]['type_remb'].' le '.$remb[$i]['date_remb'].'</span></li>';
    }
    $ret .= '</ul>';
    return $ret;
}

/** Récupère et retourne l'utilisateur correspondant à l'ID spécifié.
 *  @param idDuVendeur integer L'identifiant de l'utilisateur à récupérer.
 *  @return array Un tableau associatif contenant les informations de l'utilisateur ou un message d'erreur.
 *  Le tableau retourné a la structure suivante :
 *          { "message1" string => Soit un message d'erreur, soit un tableau contenant les informations de l'utilisateur,
 *            "message2" boolean => 0 en cas d'erreur de traitement, 1 en cas de succès
 */
function getUser($idDuVendeur)
{
    include ('pdo_connect.php');
    include ('constantes.php');
    if (is_null($pdo)) {
        die('Could not connect to database!');
    }
    // error_log('getuser:'.$id,0);
    
    try {
        $statement = $pdo->prepare($SQL_2_getvendeur);
    } catch (PDOException $e) {
        print "Erreur !: " . $e->getMessage() . "<br/>";
        die();
    }
    if (! $statement) {
        $ret = array(
            "message1" => $pdo->error,
            "message2" => '0'
        );
    }
    if (! $statement->execute([
        'idDuVendeur' => $idDuVendeur
    ])) {
        $ret = array(
            "message1" => $statement->error,
            "message2" => '0'
        );
    } else {
        $statement->execute();
        $totalData = 0;
        $nestedData = array();
        while ($vendeur = $statement->fetch()) {
            $nestedData['id'] = $vendeur['idDuVendeur'];
            $nestedData['nom'] = $vendeur['nom'];
            $nestedData['prenom'] = $vendeur['prenom'];
            $nestedData['email'] = $vendeur['email'];
            $nestedData['telephone'] = $vendeur['telephone'];
            $nestedData['adresse'] = $vendeur['adresse'];
            $nestedData['codepostal'] = $vendeur['code_postal'];
            $nestedData['ville'] = $vendeur['ville'];
            $nestedData['denomination_sociale'] = $vendeur['denomination_sociale'];
            $nestedData['siege_social'] = $vendeur['siege_social'];
            $nestedData['attestation_signee'] = $vendeur['attestation_signee'];
            $totalData ++;
        }
        // error_log("totalData\n".$totalData, 0);
        // error_log("totalData\n".json_encode(array("message1" => $nestedData,"message2" => '1')), 0);
        $ret = array(
            "message1" => $nestedData,
            "message2" => '1'
        );
    }
    
    $statement = null;
    $pdo = null;
    return $ret;
}

?>
