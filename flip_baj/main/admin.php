<?php
namespace flip_baj\main;

include 'header.php';
include 'utils.php';


session_start();
$_SESSION['adminID'] = 1;  // Stocke l'ID dans une session

// Plus tard, vous pouvez récupérer cet ID
$idadminVision = $_SESSION['adminID'];

echo "<script type='text/javascript'>\n";
echo "var idVendeurEdition = " . json_encode($idadminVision) . ";\n";
echo "</script>\n";

/**
 * Vérifie si le mot de passe correspond à celui attendu.
 *
 * @param string $password
 *            Le mot de passe à vérifier.
 * @return bool True si le mot de passe est correct, sinon False.
 */
function checkPassword($password)
{
    // Comparaison avec un mot de passe stocké :
    return $password === "Vision";
}

function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

debug_to_console($idadminVision);

// Vérifie si l'utilisateur est déjà connecté
if (isset($_COOKIE['loggedin']) && $_COOKIE['loggedin'] === 'true') {
    // Vérifie si le délai de 1 minute s'est écoulé
    if (isset($_COOKIE['last_activity']) && (time() - $_COOKIE['last_activity'] > 60)) {
        // Si plus de 1 minute s'est écoulée, déconnecte l'utilisateur
        setcookie('loggedin', '', time() - 60); // Supprime le cookie 'loggedin'
        header("Location: login.php");
        exit();
    } else {
        // Met à jour le temps de dernière activité
        setcookie('last_activity', time(), time() + 60); // Met à jour le cookie 'last_activity'
    }
} else {
    // Si l'utilisateur n'est pas connecté ou si le mot de passe est incorrect, redirige vers la page de connexion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (checkPassword($_POST['password'])) {
            setcookie('loggedin', 'true', time() + 60); // Crée le cookie 'loggedin'
            setcookie('last_activity', time(), time() + 60); // Crée le cookie 'last_activity'
            header("Refresh:0");
        } else {
            echo "Mot de passe incorrect. Veuillez réessayer.";
        }
    }
    // Si l'utilisateur n'est pas connecté, affiche le formulaire de connexion
    if (! isset($_COOKIE['loggedin']) || $_COOKIE['loggedin'] !== 'true') {
        ?>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript">
    var idVendeurEdition = <?php echo $idadminVision; ?>;
</script>

<ul class="filariane">
	<li><a href="index.php">Home</a></li>
	<li><a href="#">Admin</a></li>
</ul>

<main class="container">
	<h2 class="text-center">Section réservée aux responsables</h2>

	<div class="row justify-content-center">
		<div class="col-sm-4">
			<form method="post">
				<div class="form-floating mb-3">
					<input type="password" name="password" class="form-control"
						id="password" required> <label for="password">Mot de passe</label>
				</div>
				<div class="text-center">
					<input type="button" value="Connexion" class="btn btn-primary">
				</div>
			</form>
		</div>
	</div>
</main>

<?php
        exit();
    }
}
?>


<script type="text/javascript" src="js/datatables.min.js"></script>
<script type="text/javascript" src="js/crypto-js.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/listejeux.js"></script>


<script type="text/javascript" src="js/admin.js"></script>

<!-- Besoin pour ajouter un jeu -->

<ul class="filariane ms-2">
	<li><a href="index.php">Home</a></li>
	<li><a href="#">Admin</a></li>
</ul>

<h2 class="text-center">Gestion admin</h2>

<main class="container">
	<div class="row" style="max-height: 420px; overflow: auto">
		<!-- Section pour gérer l'argent en caisse -->
		<div class="col-sm-3">
			<input type="hidden" id="ip"
				value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" />
			<div class="card">
				<div class="card-header bg-primary text-white">
					<h3 class="card-title">Gestion des flux d'argent</h3>
				</div>
				<div class="card-body">
					<button class="btn btn-primary" id="OpenModalAjoutArgent">Ajouter
						une transaction</button>
				</div>
				<!-- Champ pour afficher l'argent actuellement en caisse -->
				<ul class="list-group">
					<li
						class="list-group-item d-flex justify-content-between align-items-center">Argent
						actuellement en caisse <span
						class="badge bg-secondary rounded-pill" id='argentEnCaisse'>0 €</span>
					</li>
				</ul>

				<!-- Liste des transactions -->
				<div class="mt-3" id="listetrans">
                    <?php echo AfficherTrans("gestion");?>
                </div>
			</div>

			<!-- Modal pour ajouter une transaction -->
			<div class="modal fade" id="modal" role="dialog">
				<div class="modal-dialog" style="width: 500px;">
					<div class="modal-content">
						<div class="modal-body">
							<div class="form-group">
								<label for="MontantAjout">Montant ajouté en euros <i>Mettre un
										"-" devant en cas de retrait</i></label> <input type="number"
									class="form-control" name="MontantAjout" id="MontantAjout">
							</div>
							<div class="form-group">
								<label for="MdpAjoutTrans">Mot de passe </label> <input
									type="password" class="form-control" name="MdpAjoutTrans"
									id="MdpAjoutTrans">
							</div>
							<div class="form-group mt-2">
								<button class="btn btn-success" id="SaveTransaction">Confirmer</button>
								<button class="btn btn-danger" id="modal-close"
									data-bs-dismiss="modal">Annuler</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Section pour afficher les Jeux en vigilance -->
		<div class="col-sm-9">
			<div class="card">
				<div class="card-header bg-primary text-white">
					<h3 class="card-title">Jeux en vigilance</h3>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6">
							<h4>Particuliers</h4>
							<div class="table-responsive max-height-table">
								<table class="table table-bordered table-striped"
									id="tableauJeuxAlchimie">
									<thead>
										<tr class="bg-info text-black">
											<th>Nom</th>
											<th>Code</th>
											<th>Prix</th>
											<th>Statut</th>
										</tr>
									</thead>
									<tbody>
										<!-- Les données seront ajoutées dynamiquement via AJAX -->
									</tbody>
								</table>
							</div>
						</div>
						<div class="col-sm-6">
							<h4>Autres vendeurs</h4>
							<div class="table-responsive">
								<table class="table table-bordered table-striped"
									id="tableauJeuxAutres">
									<thead>
										<tr class="bg-info text-black">
											<th>Nom</th>
											<th>Code</th>
											<th>Prix</th>
											<th>Statut</th>
										</tr>
									</thead>
									<tbody>
										<!-- Les données seront ajoutées dynamiquement via AJAX -->
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

    <!--
	<div class="row mt-4">

		 Section pour importer des fichiers

		<div class="col-sm-3">
			<div class="card">
				<div class="card-header bg-primary text-white">
					<h3 class="card-title">Importation des tables</h3>
				</div>
				<div class="card-body text-center">
					<form id="insertTable" method="post" class="needs-validation"
						novalidate>

						 Champ pour télécharger le fichier pour la table al_bourse_users

						<div class="mb-3">
							<label for="file_user">Fichier pour al_bourse_users :</label> <input
								type="file" class="form-control" id="file_user" name="file_user"
								placeholder=" " required>
							<div class="invalid-feedback d-none">Veuillez sélectionner un
								fichier.</div>
						</div>

						Champ pour télécharger le fichier pour la table al_bourse_liste

						<div class="mb-3">
							<label for="file_liste">Fichier pour al_bourse_liste :</label> <input
								type="file" class="form-control" id="file_liste"
								name="file_liste" placeholder=" " required>
							<div class="invalid-feedback d-none">Veuillez sélectionner un
								fichier.</div>
						</div>

					    Mdp pour verrouiller l'import

						<div class="mb-3">
							<div class="form-floating">
								<input type="password" class="form-control" id="mdpImport"
									name="mdpImport" placeholder=" " required> <label
									for="mdpImport">Mot de passe pour l'import :</label>
								<div class="invalid-feedback d-none">Veuillez entrer un mot de
									passe.</div>
							</div>
						</div>

						Bouton pour soumettre le formulaire

						<button type="submit" class="btn btn-primary">Importer</button>
					</form>
				</div>

			</div>
		</div>

		-->

		<!-- Section pour télécharger les fichiers de fin -->
    <div class="row mt-4 justify-content-center">
        <div class="col-sm-3">
            <div class="card ">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">Exports de fin de festival</h3>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary btn-block my-2" id="getstats">Télécharger les stats de fin</button>
                    <button class="btn btn-primary btn-block my-2" id="getpdf">Télécharger les factures</button>
                </div>
            </div>
        </div>

        <div class="col-sm-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title"><i class="bi bi-upc-scan"></i> Numérotation ID</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">État actuel de la base de données :</p>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-1">
                            Dernier utilisé :
                            <span class="badge bg-secondary rounded-pill fs-6" id="lastIdBadge">Chargement...</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-1">
                            Prochain prévu :
                            <span class="badge bg-success rounded-pill fs-6" id="nextIdBadge">Chargement...</span>
                        </li>
                    </ul>

                    <hr class="my-2">

                    <div class="form-group mt-2 mb-3">
                        <label for="forceIdInput" class="form-label small fw-bold">Forcer le point de départ :</label>
                        <div class="input-group input-group-sm mb-1">
                            <input type="number" class="form-control" id="forceIdInput" placeholder="Ex: 7000">
                            <button class="btn btn-outline-primary" type="button" id="btnForceId">Appliquer</button>
                        </div>
                        <small class="text-muted" style="font-size: 0.70rem;">Utile pour sauter un lot de numéros ou si vous changez de rouleau d'étiquettes.</small>
                    </div>

                    <hr class="my-2">
                    <div class="d-grid mt-2 pt-1">
                        <button class="btn btn-dark btn-sm fw-bold shadow-sm" type="button" id="btnPlancheVierge">
                            <i class="bi bi-printer"></i> Imprimer planche vierge (24)
                        </button>
                        <small class="text-muted text-center mt-1" style="font-size: 0.70rem;">Réserve automatiquement les 24 prochains numéros.</small>
                    </div>
                    <hr class="my-2">
                    <div class="d-grid mt-2">
                        <button class="btn btn-warning btn-sm fw-bold shadow-sm" type="button" onclick="window.ouvrirSaisieVierge()">
                            <i class="bi bi-link-45deg"></i> Assigner un jeu à une étiquette
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Ajouter un jeu</h3>
                </div>
                <div class="card-body">
                    <form id="formulaireajoutjeu2"
                          class="row g-3 justify-content-center">
                        <div class="col-auto">
                            <div class="input-group" title="Nom du jeu">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="NomJeuAjout"
                                       data-jeuxacreer="" placeholder="Nom du jeu">
                            </div>
                        </div>

                        <div class="col-auto">
                            <div class="input-group" title="Code a 4 chiffres">
                                <span class="input-group-text"><i class="bi bi-upc"></i></span>
                                <input size="17" type="text" class="form-control"
                                       id="CodeBarreAjout" placeholder="Code à 4 ou 5 chiffres">
                            </div>
                        </div>

                        <div class="col-auto">
                            <div class="input-group" title= "Prix du jeu">
                                <span class="input-group-text"><i class="bi bi-currency-euro"></i></span>
                                <input size="4" type="text" size="6" class="form-control"
                                       id="PrixAjout" placeholder="Prix">
                            </div>
                        </div>

                        <div class="col-auto" title="Ajouter le jeu a la base de donnée">
                            <button id="boutonsave" type="button" class="btn btn-primary boutonsave">Ajouter</button>
                        </div>
                    </form>

                    <div id="messageerreurformulaire"></div>
                    <div id="messageformulaire"></div>

                    <hr class="my-4">

                    <div class="text-center mt-3">
                        <h5 class="text-muted mb-3">Besoin d'importer un fichier partenaire (Excel/CSV) ?</h5>
                        <a href="jassmeux.php" target="_blank" class="btn btn-info text-white btn-lg shadow-sm" style="font-weight: bold;">
                            <i class=" bi bi-box-arrow-in-down"></i> Lancer le module JassMeux
                        </a>
                        <p class="small text-muted mt-2">
                            <em>Ce module externe permet l'import de masse (> 50 jeux) et la génération automatique des étiquettes.</em>
                        </p>
                    </div>
                </div>
            </div>
        </div>
	


<a href="http://localhost/FlipBAJ/flip_baj/main/ajax/envoi_mail_acheteur.php" class="btn btn-primary btn-block my-2">
    📤 Envoyer justificatifs acheteurs
</a>
<br></br>


<a href="http://localhost/FlipBAJ/flip_baj/main/ajax/envoi_mail_vendeur.php" class="btn btn-primary btn-block my-2">
    📤 Envoyer factures vendeurs
</a>


		<div class="modal fade" id="pdfModal" tabindex="-1"
			aria-labelledby="pdfModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="pdfModalLabel">Générer PDF</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal"
							aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<form id="pdfForm">
							<div class="mb-3">
								<label for="start_date" class="form-label">Date de début :</label>
								<input type="datetime-local" class="form-control"
									id="start_date">
							</div>
							<div class="mb-3">
								<label for="end_date" class="form-label">Date de fin :</label> <input
									type="datetime-local" class="form-control" id="end_date">
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary"
							data-bs-dismiss="modal">Annuler</button>
						<button type="button" class="btn btn-primary"
							id="generatePdfButton">Générer</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
</main>
<script src="js/admin.js"></script>

<div class="modal fade" id="modalSaisieVierge" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-upc-scan"></i> Assigner une étiquette vierge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="formSaisieVierge">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small">1. Code-barres de l'étiquette</label>
                            <input type="text" class="form-control form-control-lg border-warning" id="scanCodeVierge" placeholder="Ex: 10042 ou Festival_10042" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small">2. Prix de vente (€)</label>
                            <input type="number" class="form-control form-control-lg" id="prixJeuVierge" placeholder="Ex: 15" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold small">3. Vendeur (Propriétaire du jeu)</label>
                        <input type="text" class="form-control" id="searchVendeurVierge" placeholder="Rechercher par nom ou prénom..." required>
                        <input type="hidden" id="idVendeurVierge">
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary fw-bold small">4. Nom du jeu</label>
                        <input type="text" class="form-control" id="nomJeuVierge" placeholder="Saisir le nom du jeu..." required>
                    </div>

                    <div id="msgVierge"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning fw-bold px-4" id="btnSaveVierge">
                    <i class="bi bi-check-circle"></i> Valider et Assigner le jeu
                </button>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>
