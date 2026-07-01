<?php
namespace flip_baj\main;

include ('header.php');
include ('utils.php');

$user = array(
    "message1" => '',
    "message2" => ''
);
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $userarray = getUser($id);
    if ($userarray['message2'] == '1') {
        $user = $userarray['message1'];
        // error_log("userarray".print_R($user,true), 0);
    }
}
?>

<script type="text/javascript" src="js/datatables.min.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/modalevendeur.js"></script>
<script type="text/javascript" src="js/receptionjeux.js"></script>

<!-- Navigation -->
<ul class="filariane ms-2">
	<li><a href="index.php">Home</a></li>
	<li><a href="selectionvendeur.php?t=reception">Selection du vendeur</a></li>
	<li><a href="#">Gestion de <?php echo $user['nom'].' '.$user['prenom'] ?></a></li>
</ul>

<main class="container">
	<!-- Page Title -->
	<h2 class="text-center mb-4">Reception des jeux</h2>

	<div class="row">
		<div class="col-sm-5">

			<!-- Vendeur Card -->
			<div class="card">
				<div class="card-header bg-primary">
					<h4 class="card-title text-white">Vendeur</h4>
				</div>
				<div class="card-body">
					<div class="d-flex align-items-center">
						<img src="img/g6895.png" class="me-3" width="30" height="50"
							alt="...">
						<div class="flex-grow-1">
							<h5 class="mb-0"><?php echo $user['nom'].' '.$user['prenom'] ?></h5>
							<div id="email"><?php echo $user['email'] ?></div>
							<div><?php echo $user['telephone'] ?></div>
                <?php
                if (isset($user['denomination_sociale']) && $user["denomination_sociale"] != '') {
                    echo "<div>" . $user['denomination_sociale'] . "</div>";
                }
                if (isset($user['siege_social']) && $user["siege_social"] != '') {
                    echo "<div>" . $user['siege_social'] . "</div>";
                }
                ?>
            </div>
						<div class="ms-auto">
							<button id="showModal" type="button" class="btn btn-primary">
								Modifier<br>Coordonnées
							</button>
						</div>
					</div>
					<ul class="list-group">
						<li class="list-group-item d-flex justify-content-between align-items-center">Jeux en stock<span class="badge bg-secondary rounded-pill"
							id='nbJeuxStockVendeurSelectionne'></span></li>
						<li class="list-group-item d-flex justify-content-between align-items-center">Jeux non reçus<span class="badge bg-secondary rounded-pill"
							id='nbJeuxPasRecusVendeurSelectionne'></span></li>
						<li class="list-group-item d-flex justify-content-between align-items-center">Jeux vendus<span class="badge bg-secondary rounded-pill"
							id='nbJeuxVendusVendeurSelectionne'></span></li>
						<li class="list-group-item d-flex justify-content-between align-items-center">Jeux rendus<span class="badge bg-secondary rounded-pill"
							id='nbJeuxRendusVendeurSelectionne'></span></li>
						<li class="list-group-item d-flex justify-content-between align-items-center">Jeux donnés<span class="badge bg-secondary rounded-pill"
							id='nbJeuxDonnesVendeurSelectionne'></span></li>
					</ul>
				</div>
			</div>

			<br />
			<div class="card">
				<div class="card-header bg-primary">
					<h4 class="card-title text-white">Tips utiles</h4>
				</div>
				<div class="card-body">
				<p>Un jeu en *italique* est un jeu suspect.</p>
				<p>Éditer le prix d’un jeu : double-cliquez dans la cellule « Prix ».</p>
				<p>Éditer le code-barres d’un jeu : double-cliquez dans la cellule « Code-barres » puis appuyez sur Entrée.</p>
				<p>Passer un jeu de l’état « non reçu » à « en stock » : attribuez-lui un code-barres en double-cliquant dans la cellule correspondante, puis appuyez sur Entrée.</p>
				<p>Supprimer un jeu : cliquez sur l’icône en forme de poubelle dans la colonne « Action ».</p>
				<p>Donner un jeu au festival : cliquez sur l’icône en forme de cadeau dans la colonne « Action ».</p>
				<p>Marquer un jeu comme suspect : cliquez sur l’icône « ! » dans la colonne « Action ».</p>
				<p>Réduire un tableau trop grand : cliquez sur l’icône de réduction du tableau.</p>

				</div>
			</div>
		</div>
		<input type="hidden" id="ip"
			value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" /> <input type="hidden"
			id="attestation_signee"
			value="<?php echo $user['attestation_signee'] ?>" /> <input
			type="hidden" id="denomination_sociale"
			value="<?php echo $user['denomination_sociale'] ?>" />

		<div class="col-sm-7">
			<div class="card">
				<div class="card-header bg-primary">
					<h3 class="card-title text-white">Liste des jeux</h3>
				</div>
				<div class="card-body">
					<form id="formulaireajoutjeu"
						class="row g-3 justify-content-center">
						<input id="idVendeurEdition" type="hidden"
							value="<?php echo $user['id'] ?>" /> <input id="idJeux"
							type="hidden" />
						<div class="md-12 row">
							<div class="text-center">Ajouter un jeu</div>
						</div>
						<div class="col-auto">
							<div class="input-group" title="Nom du jeu">
								<span class="input-group-text"><i class="bi bi-person"></i></span>
								<input type="text" class="form-control" id="NomJeuAjout"
									data-jeuxacreer="" placeholder="Nom du jeu">
							</div>
						</div>

						<div class="col-auto">
							<div class="input-group" title="Code à 4 ou 5 chiffres">
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

						<div class="col-auto">
							<div class="input-group" title="Montant à verser au vendeur">
								<span class="input-group-text"><i class="bi bi-currency-euro"></i></span>
								<input size="4" type="text" size="6" class="form-control"
									id="PrixRendu" placeholder="Rendu">
							</div>
						</div>

						<div class="col-sm-3">
							<div class="input-group" title="Montant retenu pour le FLIP">
								<span class="input-group-text"><i class="bi bi-currency-euro"></i></span>
								<input size="4" type="text" size="6" class="form-control"
									id="PrixComm" placeholder="Commission" disabled>
							</div>
						</div>

						<div class="col-auto" title="Ajouter le jeu a la base de donnée">
							<button id="boutonsave" type="button" class="btn btn-primary boutonsave">Ajouter</button>
						</div>
					</form>


					<div id="messageerreurformulaire"></div>
					<div id="messageformulaire"></div>
					<div
						class="mt-4 d-flex justify-content-end align-items-center mb-3">
						<div>
							<button id="tout-reduire" class="btn btn-outline-warning btn-sm">
								Réduire tous les tableaux <span class="bi bi-arrows-collapse"></span>
							</button>
						</div>
					</div>

					<div class="table-responsive">
						<div class="mt-2 d-flex">
							<div class="col-sm-4 text-begin">
								<input class="form-check-input align-text-bottom"
									type="checkbox" id="checkprice"> <label
									class="form-check-label" for="checkprice">Afficher le prix min
									et max</label>
							</div>
							<div class="col-sm-4 text-center">
								<h5>Jeux en stock</h5>
							</div>
							<div class="col-sm-4 text-end">
								<i class="bi bi-arrows-collapse reduire"
									data-table-id="jeuxenstock"></i>
							</div>
						</div>
						<table id="jeuxenstock"
							class="table table-bordered table-striped display">
							<thead>
								<tr class="bg-info text-white">
									<th>Code</th>
									<th>Jeu</th>
									<th>Vendu</th>
									<th>Rendu</th>
									<th>vigilance</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody class="reductible align-middle">
								<!-- Lignes ajoutées via le js-->
							</tbody>
						</table>
					</div>
					<div class="table-responsive">
						<div class="mt-2 d-flex">
							<div class="col-sm-4 text-begin"></div>
							<div class="col-sm-4 text-center">
								<h5>Jeux non reçus</h5>
							</div>
							<div class="col-sm-4 text-end">
								<i class="bi bi-arrows-collapse reduire"
									data-table-id="jeuxnonrecus"></i>
							</div>
						</div>
						<table id="jeuxnonrecus"
							class="table table-bordered table-striped display">
							<thead>
								<tr class="bg-info text-white">
									<th>Code</th>
									<th>Jeu</th>
									<th>Vendu</th>
									<th>Rendu</th>
									<th>vigilance</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody class="reductible align-middle">
								<!-- Lignes ajoutées via le js-->
							</tbody>
						</table>
					</div>
					<div class="table-responsive">
						<div class="mt-2 d-flex">
							<div class="col-sm-4 text-begin"></div>
							<div class="col-sm-4 text-center">
								<h5>Jeux vendus</h5>
							</div>
							<div class="col-sm-4 text-end">
								<i class="bi bi-arrows-collapse reduire"
									data-table-id="jeuxvendus"></i>
							</div>
						</div>
						<table id="jeuxvendus"
							class="table table-bordered table-striped display">
							<thead>
								<tr class="bg-info text-white">
									<th>Code</th>
									<th>Jeu</th>
									<th>Vendu</th>
									<th>Rendu</th>
									<th>vigilance</th>
									<th>Vente</th>
								</tr>
							</thead>
							<tbody class="reductible align-middle">
								<!-- Lignes ajoutées via le js-->
							</tbody>
						</table>
					</div>
					<div class="table-responsive">
						<div class="mt-2 d-flex">
							<div class="col-sm-4 text-begin"></div>
							<div class="col-sm-4 text-center">
								<h5>Jeux rendus</h5>
							</div>
							<div class="col-sm-4 text-end">
								<i class="bi bi-arrows-collapse reduire"
									data-table-id="jeuxrendus"></i>
							</div>
						</div>
						<table id="jeuxrendus"
							class="table table-bordered table-striped display">
							<thead>
								<tr class="bg-info text-white">
									<th>Code</th>
									<th>Jeu</th>
									<th>Vendu</th>
									<th>Rendu</th>
									<th>vigilance</th>
									<th>Restitution</th>
								</tr>
							</thead>
							<tbody class="reductible align-middle">
								<!-- Lignes ajoutées via le js-->
							</tbody>
						</table>
					</div>
					<div class="table-responsive">
						<div class="mt-2 d-flex">
							<div class="col-sm-4 text-begin"></div>
							<div class="col-sm-4 text-center">
								<h5>Jeux donnés</h5>
							</div>
							<div class="col-sm-4 text-end">
								<i class="bi bi-arrows-collapse reduire"
									data-table-id="jeuxdonnes"></i>
							</div>
						</div>
						<table id="jeuxdonnes"
							class="table table-bordered table-striped display">
							<thead>
								<tr class="bg-info text-white">
									<th>Code</th>
									<th>Jeu</th>
									<th>Vendu</th>
									<th>Rendu</th>
									<th>vigilance</th>
									<th>Don</th>
								</tr>
							</thead>
							<tbody class="reductible align-middle">
								<!-- Lignes ajoutées via le js-->
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
<form id="formulaireImportCSV" method="post" enctype="multipart/form-data" class="mt-4">
  <div class="row">
    <div class="col">
      <label for="fileCSV" class="form-label">Importer un fichier CSV au format (UT8)</label>
      <input type="file" class="form-control" id="fileCSV" name="fileCSV" accept=".csv" required>
    </div>
    <div class="col-auto align-self-end">
      <button type="submit" class="btn btn-success">Importer</button>
    </div>
  </div>
  <input type="hidden" name="idVendeurImport" value="<?php echo $user['id']; ?>">
</form>
<div id="importResultat" class="mt-2"></div>


</main>
<?php
// fenetre modal de creation d'un vendeur
include ('modalevendeur.php');
include ('footer.php');
?>
