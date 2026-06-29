/**
 * Ce fichier contient les fonctionnalités de gestion administrative pour la page admin.php.
 * Il inclut des méthodes pour :
 * - importer proprement les tables al_bourse_liste et al_bourse_users via AJAX.
 * - ajouter une transaction de type "Sortie de fond de caisse vers trésorier ou chemin inverse".
 * - exporter les données de fin de BAJ.
 */

$(document).ready(function() {
	$('#messageinfo').hide();
	/**
	 * Ouvre la fenêtre modale d'ajout d'argent.
	 */
	$("#OpenModalAjoutArgent").on('click', function(e) {
		$('#MontantAjout').val(0);
		$('#MdpAjoutTrans').val('');
		$('#modal').modal('show');
	});

	/**
	 * Ferme la fenêtre modale.
	 */
	$('#modal-close').on('click', function(e) {
		$('#modal').modal('hide');
	});



	/**
	 * Gère l'ajout d'une transaction.
	 */
	function ajoutTransaction() {
		// Récupérer les valeurs des champs
		var montantAjout = $("#MontantAjout").val();
		var mdpAjoutTrans = $("#MdpAjoutTrans").val();
		var motDePasseStocke = "5ccc2e8715d7a17c3110afe37b8561b84b450db8efcf187d90649f212201050f";

		if (montantAjout == "" ||
			montantAjout == "0") {
			alert("Il faut rentrer un montant");
			return;
		}

		// Vérifie le Mot de passe
		if (verifierMotDePasse(mdpAjoutTrans, motDePasseStocke)) {
			console.log("Mot de passe correct");
			// Appeler l'AJAX pour l'ajout de transaction
			$.ajax({
				type: 'POST',
				url: 'ajax/transaction-add.php',
				data: {
					type: "gestion",
					montantTotal: montantAjout,
					montantPercu: 0,
					montantDon: 0,
					montantRendu: 0,
					paiement: 'especes',
					ip: $("#ip").val(),
					id_phpbb_acheteur: ''
				},
				success: function(response) {
					mettreAJourArgentEnCaisse();
					AfficherTrans();
					console.log(response);
				},
				error: function(error) {
					// Gérer l'erreur de l'AJAX
					console.error(error);
				}
			});
			$('#modal').modal('hide');
		} else {
			alert("Mauvais mot de passe");
		}
	}

	/**
	 * Crypte le mot de passe en utilisant SHA-256.
	 * @param {string} motDePasse - Le mot de passe à crypter.
	 * @returns {string} Le mot de passe crypté.
	 */
	function crypterMotDePasse(motDePasse) {
		// Utilise CryptoJS pour hacher le mot de passe avec SHA-256
		var hash = CryptoJS.SHA256(motDePasse).toString(CryptoJS.enc.Hex);
		return hash;
	}

	/**
	 * Vérifie si le mot de passe entré correspond au mot de passe haché stocké.
	 * @param {string} motDePasseEntree - Le mot de passe entré par l'utilisateur.
	 * @param {string} motDePasseHacheStocke - Le mot de passe haché stocké en base de données.
	 * @returns {boolean} true si les mots de passe correspondent, sinon false.
	 */
	function verifierMotDePasse(motDePasseEntree, motDePasseHacheStocke) {
		// Hache le mot de passe entré
		var motDePasseHacheEntree = crypterMotDePasse(motDePasseEntree);

		// Compare les hachages
		return motDePasseHacheEntree === motDePasseHacheStocke;
	}

	// Activer la fonction ajoutTransaction lors du clic sur le bouton "Confirmer"
	$('#SaveTransaction').on('click', function(e) {
		ajoutTransaction();
	});

	/**
	 * Met à jour dynamiquement le champ "Argent actuellement en caisse".
	 */
	function mettreAJourArgentEnCaisse() {
		// Utiliser AJAX pour récupérer la somme en caisse depuis le serveur
		$.ajax({
			url: 'ajax/argentencaisse-get.php',
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				var argentEnCaisseElement = document.getElementById('argentEnCaisse');
				argentEnCaisseElement.textContent = data.message1 + ' €';
			},
			error: function(error) {
				console.error('Erreur lors de la récupération de la somme en caisse :', error);
			}
		});
	}


	mettreAJourArgentEnCaisse();

	/**
	 * Ajoute des transactions à la liste en utilisant AJAX.
	 */
	function AfficherTrans() {
		var listetrans = '';
		$.ajax({
			type: 'POST',
			url: 'ajax/transactionliste-get.php',
			data: { type: 'gestion' },
			success: function(data) {
				if (data.message2 == '1') listetrans = data.message1;
				console.log(data);
				$("#listetrans").html(listetrans);
			},
			dataType: 'json',
		});
	}

	/**
	 * Peuple un tableau avec des données AJAX.
	 * @param {string} tableauId - L'identifiant du tableau à peupler.
	 * @param {object} params - Les paramètres à inclure dans la requête AJAX.
	 */
	function peuplerTableau(tableauId, params) {
		var tableau = $('#' + tableauId + ' tbody');

		$.ajax({
			url: 'ajax/jeuxliste-getenstockspeed.php',
			type: 'POST',
			dataType: 'json',
			data: params, // Inclure les paramètres dans la requête AJAX
			success: function(response) {
				// Effacer le contenu actuel du tableau
				tableau.empty();

				// Accéder directement à la propriété data qui est un tableau
				var data = response.data;

				// Ajouter les nouvelles données au tableau
				if (params.idVendeur == 1) {
					data.forEach(function(row) {
						var newRow = '<tr><td>' + row.Code + '</td><td>' + row.Jeu + '</td><td>' + row.Vendu + '</td><td>' + row.statut + '</td></tr>';
						tableau.append(newRow);
					});
				}
				else {
					data.forEach(function(row) {
						if (row.idvendeur != 1) {
							var newRow = '<tr><td>' + row.Code + '</td><td>' + row.Jeu + '</td><td>' + row.Vendu + '</td><td>' + row.statut + '</td></tr>';
							tableau.append(newRow);
						}
					});
				}
			},
			error: function(error) {
				console.error('Erreur lors de la récupération des données pour le tableau', tableauId, ':', error);
			}
		});
	}

	// Appeler la fonction pour peupler chaque tableau avec les paramètres appropriés
	peuplerTableau('tableauJeuxAlchimie', { vigilance: 1, idVendeur: 1 });
	peuplerTableau('tableauJeuxAutres', { vigilance: 1 });

	/**
	 * Importe les tables via AJAX.
	 * @param {FormData} formData - Données du formulaire à envoyer.
	 * @return {string | err} - Message de réussite ou d'erreur.
	 */
	function importerTables(formData) {
		$(this).prop('disabled', true);
		console.log(formData.get('mdpImport') + btoa(formData.get('mdpImport')));
		if (btoa(formData.get('mdpImport')) == 'SW1wb3J0ZXI=') {
			if (confirm("Voulez-vous vraiment importer ces tables ?")) {
				alert("L'importation commence, cela va prendre quelques minutes...")
				$.ajax({
					type: 'POST',
					url: 'ajax/upload_files.php',
					data: formData, // Ne pas spécifier contentType ni processData
					contentType: false,
					processData: false,
					success: function(data) {
						if (data.message2 == '1') {
							alert("L'importation est terminée")
						} else {
							alert(data.message1)
						}
					},
					error: function(xhr, status, error) {
						console.log(xhr);
						console.log(status);
						console.log(error);
					},
					dataType: 'json'
				})
			}
		} else {
			alert("Mauvais mot de passe")
		}
		$(this).prop('disabled', false);
	}

	/**
	 * Gère la soumission du formulaire d'insertion de tables.
	 * @param {Event} event - Objet représentant l'événement de soumission du formulaire.
	 */
	$("form#insertTable").submit(function(event) {
		event.preventDefault();
		var formData = new FormData(this);
		importerTables(formData);
	});

	/**
	 * Télécharge les statistiques de fin.
	 */
	$('#getstats').on('click', function() {
		$('#pdfModal').modal('show');
	});

	// Générer le PDF lorsque l'utilisateur soumet la popup
    $('#pdfModal').on('click', '#generatePdfButton', function(){
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();

        // Vérification que les dates sont renseignées
        if(startDate && endDate){
            $.ajax({
                type: 'POST',
                url: 'pdf/generer_stats.php', // Le fichier PHP qui génère le PDF
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response){
                    // Réponse de la requête AJAX
                    alert('PDF généré avec succès !');
                    // Vous pouvez également effectuer d'autres actions ici, comme afficher le PDF dans un nouvel onglet
                },
                error: function(){
                    // En cas d'erreur
                    alert('Une erreur est survenue lors de la génération du PDF.');
                }
            });

            // Cacher la popup après soumission
            $('#pdfModal').modal('hide');
        } else {
            alert('Veuillez renseigner les dates de début et de fin.');
        }
    });

// Partie formulaire
	$.ajax({
		type: 'POST',
		url: 'ajax/user-get.php',
		data: { 'id': idVendeurEdition },  // Use the variable directly
		success: function(data) {
			if (data.message2 === '0') {
				$('#messageinfo').show();
				$('#messageinfo').html('<p class="bg-danger">Impossible de trouver en base le vendeur,</p>');
				$('.container').hide();
			} else if (data.message2 === '1') {
				if (data.message1.length === 0) {
					$('#messageinfo').show();
					$('#messageinfo').html('<p class="bg-danger">Impossible de trouver en base le vendeur,</p>');
					$('.container').hide();
				} else {
					idVendeurEdition = data.message1['id'];
				}
			}
		},
		dataType: 'json',
		async: false
	});

    	/** auto complete de la liste des jeux dispo en base */
	$('#NomJeuAjout').autocomplete({
		source: 'ajax/jeux-get.php?exact=0',
		minLength: 4,
		html: true
	});

    	/** saisie d'un code barre detection enter */
	$(document).keyup(function(e) {
		var key = String.fromCharCode(e.keyCode);
		if (e.keyCode == 13) {
		}
	});

	/* disable formualire sur enter */
	$('#formulaireajoutjeu').keypress(function(e) {
		var charCode = e.charCode || e.keyCode || e.which;
		if (charCode == 13) {
			return false;
		}
	});

    /** ajout d'un jeu au vendeur**/
	$(".boutonsave").click(function() {
		var codebarre = VerifCodeBarre($('#CodeBarreAjout').val());
		var serr = '';
		if ($('#CodeBarreAjout').val() == '' || codebarre == '') {
			serr = serr + '<p class="bg-danger">Le code-barre doit être un nombre à 4-5 chiffres.</p>';
		}
		if ($('#NomJeuAjout').val() == '') {
			serr = serr + '<p class="bg-danger">Le nom du jeu doit être renseigné.</p>';
		}
		if ($('#PrixAjout').val() == '') {
			serr = serr + '<p class="bg-danger">Le prix doit être renseigné.</p>';
		}
		if (isNaN($('#PrixAjout').val())) {
			serr = serr + '<p class="bg-danger">Le prix doit être un nombre rond.</p>';
		}
		/* verifie en base si le code est deja pris */
		$.ajax({
			type: 'POST',
			url: 'ajax/codebarre-checker.php',
			data: { 'CodeBarreAjout': codebarre },
			success: function(data) {
				if (data.message2 == '0') serr = serr + data.message1;
			},
			dataType: 'json',
			async: false
		});
		if (serr != '') {
			$('#messageerreurformulaire').html('<div class="row row-centered "><div class="col-sm-4 col-centered">' + serr + '</div></div>');
		}
		else {
			$('#messageerreurformulaire').html('');
			/* recuperation du jeu en base */
			var jeu = null;
			$.ajax({
				type: 'GET',
				url: 'ajax/jeux-get.php',
				data: { 'term': $('#NomJeuAjout').val(), 'exact': '1' },
				success: function(data) {
					if (data.length > 0) jeu = data;
				},
				dataType: 'json',
				async: false
			});
			console.log(jeu);

			if (jeu != null) {
			}
			else {
				/* jeu existe pas encore => svg en base */
				$.ajax({
					type: 'POST',
					url: 'ajax/jeu-add.php',
					data: { 'nom_jeu': $('#NomJeuAjout').val() },
					success: function(data) {
						if (data.message2 == '1') {
							//console.log('jeu inséré:'+data.message1);
							jeu = [{ "id": '-' + data.message1, "label": $('#NomJeuAjout').val(), "value": $('#NomJeuAjout').val() }];
						}
					},
					dataType: 'json',
					async: false
				});
			}

			console.log(jeu);
			console.log(jeu[0].label);
			/* ajoute le jeu dans la liste du user en base */

            $.ajax({
                type: 'POST',
                url: 'ajax/jeuxliste-add.php',
                data: {
                    idVendeurEdition: idVendeurEdition,
                    nom: jeu[0].label,
                    codebarre: codebarre,
                    vigilance: idVendeurEdition == 1 ? 1 : 0,
                    statut: STATUS_JEUX_EN_STOCK,
                    vendu: $('#PrixAjout').val(),
                    ip: $('#ip').val()
                },
                success: function(data) {
                    if (data.message2 == '1') {
                        console.log('Jeu ajouté avec succès');

                        // 1. Le retour utilisateur (avec ta marge my-3)
                        $('#messageerreurformulaire').html('<div class="alert alert-success text-center py-2 my-3">✅ Le jeu <strong>' + jeu[0].label + '</strong> a été ajouté avec succès !</div>');

                        setTimeout(function() {
                            $('#messageerreurformulaire').fadeOut(500, function() {
                                $(this).html('').show();
                            });
                        }, 3000);

                        // 2. On met à jour le tableau en dessous
                        if (typeof tableJeuxEnStock !== 'undefined') {
                            tableJeuxEnStock.ajax.reload();
                        }

                        // 3. On vide les champs du formulaire pour le prochain jeu
                        $('#formulaireajoutjeu').trigger("reset");

                        // 4. LA NOUVEAUTÉ : On actualise le compteur et la case "code à 4/5 chiffres" !
                        if (typeof synchroniserCompteurID === "function") {
                            synchroniserCompteurID();
                        }
                    }
                },
                dataType: 'json',
                async: false
            });
			console.log("tableJeuxEnStock initialized", tableJeuxEnStock);
			/* ajout dans la table UI */
			tableJeuxEnStock.ajax.reload();
			/* ide les champs input pour prochaine saisie */
			$('#formulaireajoutjeu').trigger("reset");

			if (typeof synchroniserCompteurID === "function") {
				synchroniserCompteurID();
			}
		}
	});

	$(document).on('keyup', '#PrixRendu', function() {
		if ($(this).val() != '') {
			$(this).val($(this).val().replace(",", ""));
		}
		if ($(this).val() != '') {
			var prixRendu = parseInt($(this).val());
			var prix = prixRendu + Math.ceil(prixRendu*.2)
			$("#PrixAjout").val(prix);
			$('#PrixComm').val(Math.ceil(prix / 6));
		}
	});

	/* transformation en code barre 'Festival_' du champ CodeBarreAjout*/
	$(document).on('blur', '#CodeBarreAjout', function() {
		if ($(this).val() != '') {
			var codebarre = VerifCodeBarre($(this).val());
			$(this).val(codebarre);
		}
	});

	/** dblck on th = hide the table **/
	$(".reduire").click(function() {
		var tableId = $(this).data("table-id");
		$("#" + tableId).find("tbody").fadeToggle();
	});

	$("#tout-reduire").click(function() {
		$(".reductible").fadeToggle();
	});

	/* afffiche ou aps les prix bas et haut */
	$("#checkprice").click(function() {
		if (!$(this).is(':checked')) {
			$('.showprice').addClass('d-none');
		}
		else {
			$('.showprice').removeClass('d-none');
		}
	});

	console.log("L'ID de l'administrateur est :", idVendeurEdition);

	/**
	 * Télécharge les tables de la base.
	 */
	$('#getbase').on('click', function() {
		window.location.href = 'export-base.php';
	});

	/* manière différente */
	$('#getBackupSQL').on('click', function() {
		window.location.href = 'backup-sql.php';
	});

	/**
	 * Redirige l'utilisateur vers la page de téléchargement PDF.
	 */
	$('#getpdf').on("click", function() {
		if(confirm("Vous allez être redirigé vers la page de téléchargement")){
			window.location.href = "/FlipBAJ/flip_baj/main/pdf/generer_pdf.php";
		}


	});
	 /**
     * Envoie les mails de factures PDF aux vendeurs et acheteurs.
     */
	 console.log("admin.js bien chargé !");

$('#envoiMailsFactures').on('click', function () {
	console.log("Lancement AJAX vers send_mail.php");
	alert("Clic détecté !");
	if (confirm("Souhaitez-vous envoyer les factures par mail ?")) {
		$.ajax({
			url: '/FlipBAJ/flip_baj/main/ajax/send_mail.php',
			type: 'POST',
			dataType: 'json',
			data: {
				idrestitution: 1
			},
			success: function (response) {
				console.log("Réponse serveur :", response);
				alert(response.message || "Mail envoyé !");
			},
			error: function (xhr, status, error) {
				console.error("Erreur AJAX :", error);
				alert("Erreur AJAX : " + error);
			}
		});
	}
});




});

// Fonction pour synchroniser les badges et le champ d'ajout manuel
function synchroniserCompteurID() {
	fetch('ajax/gestion_id.php')
		.then(response => response.json())
		.then(data => {
			// Sécurité : on vérifie que l'élément HTML existe avant de changer son texte !
			let lastBadge = document.getElementById('lastIdBadge');
			let nextBadge = document.getElementById('nextIdBadge');

			if (lastBadge) lastBadge.textContent = data.last_id;
			if (nextBadge) nextBadge.textContent = data.next_id;

			const champManuel = document.getElementById('CodeBarreAjout');
			if(champManuel) champManuel.value = data.next_id;
		})
		.catch(error => console.error("Erreur lecture ID:", error));
}

// On englobe TOUT dans UN SEUL DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {

	// 1. On lance la synchro au démarrage
	synchroniserCompteurID();

	// 2. On attache l'action au bouton Appliquer
	const btnForce = document.getElementById('btnForceId');

	if(btnForce) {
		// On ajoute le "e" pour l'événement
		btnForce.addEventListener('click', function(e) {
			e.preventDefault(); // EMPÊCHE LA PAGE DE SE RAFRAÎCHIR SI C'EST DANS UN FORMULAIRE !

			const forceVal = document.getElementById('forceIdInput').value;
			if (!forceVal) return;

			const formData = new FormData();
			formData.append('force_id', forceVal);

			fetch('ajax/gestion_id.php', {
				method: 'POST',
				body: formData
			})
				.then(response => response.json())
				.then(data => {
					if(data.success) {
						synchroniserCompteurID();
						document.getElementById('forceIdInput').value = '';

						const demande = parseInt(forceVal);
						const reel = data.next_id;

						if (reel > demande) {
							alert("Il n'est pas possible de commencer à " + demande + ", vous commencerez au nombre le plus proche possible (" + reel + ").");
						} else {
							alert("Succès : Le compteur démarrera désormais à " + reel + ".");
						}
					} else {
						alert("Erreur serveur : " + (data.error || "Raison inconnue"));
					}
				})
				.catch(error => console.error("Erreur de communication :", error));
		});
	} else {
		console.warn("Bouton btnForceId introuvable dans le HTML.");
	}
});

// =========================================================================
// BOUTON PLANCHE VIERGE (Sécurité Ultime Anti-Doublon)
// =========================================================================
$('#btnPlancheVierge').on('click', function(e) {
	e.preventDefault();

	// 1. Demande de confirmation simple et classique
	if (!confirm("Voulez-vous générer une planche de 24 étiquettes vierges ? \n\nCela va automatiquement réserver et avancer les compteurs de 24 numéros.")) {
		return; // On arrête tout si l'utilisateur clique sur "Annuler"
	}

	const $btn = $(this);
	const texteOriginal = '<i class="bi bi-printer"></i> Imprimer planche vierge (24)';

	// 2. Interface en chargement (on désactive le bouton le temps du calcul)
	$btn.prop('disabled', true);
	$btn.html('<span class="spinner-border spinner-border-sm"></span> Création du PDF...');

	// 3. Appel au serveur
	fetch('ajax/generer_planche_vierge.php')
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				window.open('../tmp/' + data.pdf, '_blank');
				// Met à jour les compteurs sur la page si la fonction existe
				if (typeof synchroniserCompteurID === "function") {
					synchroniserCompteurID();
				}
			} else {
				alert("Erreur lors de la création de la planche : " + (data.error || "Inconnue"));
			}
		})
		.catch(error => {
			console.error("Erreur de communication :", error);
			alert("Impossible de joindre le serveur pour créer la planche.");
		})
		.finally(() => {
			// 4. Nettoyage final : on remet le bouton à son état normal
			$btn.html(texteOriginal);
			$btn.prop('disabled', false);
		});
});