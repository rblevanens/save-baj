/**
 * Ce fichier contient le code JavaScript pour gérer les interactions utilisateur
 * sur la page de réception. Il utilise jQuery et DataTables pour la manipulation
 * des données et des éléments de l'interface.
 * 
 * Les informations sur les vendeurs sont récupérées via des appels AJAX vers
 * des scripts PHP qui interagissent avec la base de données.
 * 
 * Utilise la modale de création/édition de vendeur décrite dans modalevendeur.js et modalevendeur.php :
 *  * documentReadyDeLaModale dans $(document).ready
 *  * windowOnloadDeLaModale dans window.onload
 *  * appels de ouvreModaleModification et ouvreModaleCreation
 */

$(document).ready(function() {
	$('#messageinfo').hide();

	// récupération du vendeur courant (id en paramètre d'URL)
	$.ajax({
		type: 'POST',
		url: 'ajax/user-get.php',
		data: { 'id': $('#idVendeurEdition').val() },
		success: function(data) {
			if (data.message2 == '0') {
				$('#messageinfo').show();
				$('#messageinfo').html('<p class="bg-danger">Impossible de trouver en base le vendeur,</p>');
				$('.container').hide();
			}
			if (data.message2 == '1') {
				if (data.message1.length == 0) {
					$('#messageinfo').show();
					$('#messageinfo').html('<p class="bg-danger">Impossible de trouver en base le vendeur,</p>');
					$('.container').hide();
				} else {
					$('#nomvendeur').html(data.message1['nom'] + " " + data.message1['prenom']);
					$('#idVendeurEdition').val(data.message1['id']);
				}
			}
		},
		dataType: 'json',
		async: false
	});

	// table des jeux en stock
	var tablejeuxenstock = $('#jeuxenstock').DataTable({
		paging: false,
		searching: false,
		info: false,
		processing: true,
		serverSide: false,
		language: {
			"url": "Json/fr-FR.json"
		},
		ajax: {
			type: 'POST',
			url: 'ajax/jeuxliste-get.php',
			data: {
				idVendeurEdition: $('#idVendeurEdition').val(),
				statutJeu: STATUS_JEUX_EN_STOCK
			},
		},
		columns: [
			{ data: "Code", name: "code_barre", title: "Code", width: "93px", className: "jeu-codeBarre" },
			{ data: "Jeu", name: "nj", title: "Jeu", width: "310px" },
			{ data: "Vendu", name: "Vendu", title: "Vendu", orderable: false, className: "jeu-prix-vendu", render: function(data, type, row) { return data + ' €'; } },
			{ data: "Rendu", name: "Rendu", title: "Rendu", orderable: false, className: "jeu-prix-rendu", render: function(data, type, row) { return data + ' €'; } },
			{ data: "vigilance", name: "vigilance", title: "vigilance", visible: false },
			{
				data: "Action", name: "Action", title: "Action", width: "158px", orderable: false, render: function(data, type, row) {
					var actionButtons = '<span class="showprice d-none"><i class="bi bi-arrow-down-short" style="color: red;"></i>' + row.minprix + '&euro;&nbsp;<i class="bi bi-arrow-up-short" style="color: green;"></i>' + row.maxprix + ' &euro;</span > '
						+ '<a data-jeu="' + row.DT_RowId + '" class="delJeu" href="#" alt="Supprimer le jeu de code ' + VraiCodeBarre(row.Code) + '" title="Supprimer le code du jeu ' + VraiCodeBarre(row.Code) + '"><i class="bi bi-trash"></i></a>';

					if ($('#idVendeurEdition').val() != 1) {
						actionButtons += '&nbsp;&nbsp;&nbsp;&nbsp;<a data-jeu="' + row.DT_RowId + '" class="toFestival" href="#" alt="Donner le jeu de code ' + VraiCodeBarre(row.Code) + ' au festival" title="Donner le jeu de code ' + VraiCodeBarre(row.Code) + ' au festival"><i class="bi bi-gift"></i></a>';
					}
					if (row.vigilance != "Oui") {
						actionButtons += '&nbsp;&nbsp;&nbsp;&nbsp;<a data-jeu="' + row.DT_RowId + '" class="toLost" href="#" alt="Flagger le jeu de code ' + VraiCodeBarre(row.Code) + ' comme non retrouvé" title="Flagger le jeu de code ' + VraiCodeBarre(row.Code) + ' comme suspect ?"><i class="bi bi-exclamation-circle"></i></a>';
					}
					return actionButtons;
				}
			}
		],
		createdRow: function(row, data, rowIndex) {
			var tab = Object.getOwnPropertyNames(data).sort();
			for (var i = 0; i < tab.length; i++) {
				$(row).attr('data-' + tab[i], data[tab[i]]);
			}
			if (data.vigilance === "Oui") {
				$(row).addClass('fst-italic');
			}
			$(row).attr('data-row-id', data["DT_RowId"]);
		},
		drawCallback: function(settings) {
			$('#nbJeuxStockVendeurSelectionne').html(tablejeuxenstock.rows().count());

			$('#jeuxenstock tbody td.jeu-prix-rendu').editable('ajax/jeuxprixrendu-update.php', {
				data: function(value, settings) {
					return value.replace(/ €/gi, '');
				},
				event: 'dblclick',
				submitdata: function(value, settings) {
					return { id: this.parentNode.getAttribute('id') };
				},
				callback: function(sValue, y) {
					tablejeuxenstock.ajax.reload();
				}
			});

			$('#jeuxenstock tbody td.jeu-prix-vendu').editable('ajax/jeuxprixvendu-update.php', {
				data: function(value, settings) {
					return value.replace(/ €/gi, '');
				},
				event: 'dblclick',
				submitdata: function(value, settings) {
					return { id: this.parentNode.getAttribute('id') };
				},
				callback: function(sValue, y) {
					tablejeuxenstock.ajax.reload();
				}
			});

			$('#jeuxenstock tbody td.jeu-codeBarre').editable('ajax/jeuxliste-checkandupdate.php', {
				data: function(value, settings) {
					if (!/Festival_\d{4}/.test(value)) {
						return '';
					}
					else return value.replace('Festival_', '');
				},
				event: 'dblclick',
				submitdata: function(value, settings) {
					return {
						'id': this.parentNode.getAttribute('id'),
						'statut': '2',
						'ip': $('#ip').val(),
						'old_id_statut': 1
					};
				},
				callback: function(sValue, y) {
					if (sValue != "Code déjà pris") {
						tablejeuxenstock.ajax.reload();
					}
					else {
						$(this).html(sValue);
					}
				}
			});

		}
	});

	var tableJeuxNonRecus = $('#jeuxnonrecus').DataTable({
		paging: false,
		searching: false,
		info: false,
		processing: true,
		serverSide: true,
		language: {
			"url": "Json/fr-FR.json"
		},
		ajax: {
			type: 'POST',
			url: 'ajax/jeuxliste-get.php',
			data: {
				idVendeurEdition: $('#idVendeurEdition').val(),
				statutJeu: STATUS_JEUX_PASRECU
			},
		},
		columns: [
			{ data: "Code", name: "code_barre", title: "Code", width: "93px", className: "jeu-codeBarre" },
			{ data: "Jeu", name: "nj", title: "Jeu", width: "310px" },
			{ data: "Vendu", name: "Vendu", title: "Vendu", orderable: false, className: "jeu-prix", render: function(data, type, row) { return data + ' €'; } },
			{ data: "Rendu", name: "Rendu", title: "Rendu", orderable: false, className: "colonneeditable", render: function(data, type, row) { return data + ' €'; } },
			{ data: "vigilance", name: "vigilance", title: "vigilance", visible: false },
			{
				data: "Action", name: "Action", title: "Action", width: "158px", orderable: false, render: function(data, type, row) {
					return '<span class="showprice d-none"><i class="bi bi-arrow-down-short" style="color: red;"></i>' + row.minprix + '&euro;&nbsp;<i class="bi bi-arrow-up-short" style="color: green;"></i>' + row.maxprix + ' &euro;</span > '
						+ '<a data-jeu="' + row.DT_RowId + '" class="delJeu" href="#" alt="Supprimer le jeu de code ' + VraiCodeBarre(row.Code) + '" title="Supprimer le jeu de code ' + VraiCodeBarre(row.Code) + '"><i class=" bi bi-trash"></i></a>';
				}
			}
		],
		drawCallback: function() {
			$('#jeuxnonrecus tbody td.jeu-codeBarre').editable('ajax/jeuxliste-checkandupdate.php', {
				data: function(value, settings) {
					if (!/Festival_\d{4}/.test(value)) {
						return '';
					}
					else return value.replace('Festival_', '');
				},
				placeholder: '<span class="holder">Double-clique pour éditer</span>',
				event: 'dblclick',
				submitdata: function(value, settings) {
					return {
						'id': this.parentNode.getAttribute('id'),
						'statut': '2',
						'ip': $('#ip').val(),
						'old_id_statut': 1
					};
				},
				callback: function(sValue, y) {
					sValue = sValue.replaceAll('"', '');
					if (sValue != "Code déjà pris") {
						console.log(sValue);
						tableJeuxNonRecus.ajax.reload();
						tablejeuxenstock.ajax.reload();
					}
					else {
						$(this).html(sValue);
					}
				}
			});

			$('#nbJeuxPasRecusVendeurSelectionne').html(tableJeuxNonRecus.rows().count());
		},
		createdRow: function(row, data, rowIndex) {
			var tab = Object.getOwnPropertyNames(data).sort();
			for (var i = 0; i < tab.length; i++) {
				$(row).attr('data-' + tab[i], data[tab[i]]);
			}
			if (data.vigilance === "Oui") {
				$(row).addClass('fst-italic');
			}
		}
	});

	// table des jeux vendus
	var tablejeuxnvendus = $('#jeuxvendus').DataTable({
		paging: false,
		searching: false,
		info: false,
		processing: true,
		serverSide: true,
		language: {
			"url": "Json/fr-FR.json"
		},
		ajax: {
			type: 'POST',
			url: 'ajax/jeuxliste-get.php',
			data: {
				idVendeurEdition: $('#idVendeurEdition').val(),
				statutJeu: STATUS_JEUX_VENDUS
			},
		},
		columns: [
			{ data: "Code", name: "code_barre", title: "Code", width: "93px", className: "jeu-codeBarre" },
			{ data: "Jeu", name: "nj", title: "Jeu", width: "310px" },
			{ data: "Vendu", name: "Vendu", title: "Vendu", orderable: false, className: "colonneeditable", render: function(data, type, row) { return data + ' €'; } },
			{ data: "Rendu", name: "Rendu", title: "Rendu", orderable: false, className: "colonneeditable", render: function(data, type, row) { return data + ' €'; } },
			{ data: "vigilance", name: "vigilance", title: "Vigilance", visible: false },
			{
				data: "DateSortieStock", name: "date_sortie_stock", title: "Vente", width: "158px", render: function(data) {
					var madate = mysqlTimeStampToDate(data);
					var month = madate.getMonth() + 1;
					var NomDuJour = "Inconnu";
					switch (madate.getDay()) {
						case 1: NomDuJour = "Lundi"; break;
						case 2: NomDuJour = "Mardi"; break;
						case 3: NomDuJour = "Mercredi"; break;
						case 4: NomDuJour = "Jeudi"; break;
						case 5: NomDuJour = "Vendredi"; break;
						case 6: NomDuJour = "Samedi"; break;
						case 0: NomDuJour = "Dimanche"; break;
					}
					return NomDuJour + " " + madate.getDate() + "/" + (month.length > 1 ? month : "0" + month) + "/" + madate.getFullYear() + " à " + madate.getHours() + "h" + (madate.getMinutes() > 9 ? madate.getMinutes() : "0" + madate.getMinutes());
				}
			}
		],
		createdRow: function(row, data, rowIndex) {
			var tab = Object.getOwnPropertyNames(data).sort();
			for (var i = 0; i < tab.length; i++) {
				$(row).attr('data-' + tab[i], data[tab[i]]);
			}
			if (data.vigilance === "Oui") {
				$(row).addClass('fst-italic');
			}
		},
		drawCallback: function() {
			$('#nbJeuxVendusVendeurSelectionne').html(tablejeuxnvendus.rows().count());
		}
	});


	// table des jeux rendus
	var tablejeuxnrendus = $('#jeuxrendus').DataTable({
		paging: false,
		searching: false,
		info: false,
		processing: true,
		serverSide: true,
		language: {
			"url": "Json/fr-FR.json"
		},
		ajax: {
			type: 'POST',
			url: 'ajax/jeuxliste-get.php',
			data: {
				idVendeurEdition: $('#idVendeurEdition').val(),
				statutJeu: STATUS_JEUX_RENDUS
			},
		},
		columns: [
			{ data: "Code", name: "code_barre", title: "Code", width: "93px", className: "jeu-codeBarre" },
			{ data: "Jeu", name: "nj", title: "Jeu", width: "310px" },
			{ data: "Vendu", name: "Vendu", title: "Vendu", orderable: false, className: "colonneeditable", render: function(data, type, row) { return data + ' €'; } },
			{ data: "Rendu", name: "Rendu", title: "Rendu", orderable: false, className: "colonneeditable", render: function(data, type, row) { return data + ' €'; } },
			{ data: "vigilance", name: "vigilance", title: "Vigilance", visible: false },
			{
				data: "DateSortieStock", name: "date_sortie_stock", title: "Restitution", width: "158px", render: function(data) {
					var madate = mysqlTimeStampToDate(data);
					var month = madate.getMonth() + 1;
					var NomDuJour = "Inconnu";
					switch (madate.getDay()) {
						case 1: NomDuJour = "Lundi"; break;
						case 2: NomDuJour = "Mardi"; break;
						case 3: NomDuJour = "Mercredi"; break;
						case 4: NomDuJour = "Jeudi"; break;
						case 5: NomDuJour = "Vendredi"; break;
						case 6: NomDuJour = "Samedi"; break;
						case 0: NomDuJour = "Dimanche"; break;
					}
					return NomDuJour + " " + madate.getDate() + "/" + (month.length > 1 ? month : "0" + month) + "/" + madate.getFullYear() + " à " + madate.getHours() + "h" + (madate.getMinutes() > 9 ? madate.getMinutes() : "0" + madate.getMinutes());
				}
			}
		],
		createdRow: function(row, data, rowIndex) {
			var tab = Object.getOwnPropertyNames(data).sort();
			for (var i = 0; i < tab.length; i++) {
				$(row).attr('data-' + tab[i], data[tab[i]]);
			}
			if (data.vigilance === "Oui") {
				$(row).addClass('fst-italic');
			}
		},
		drawCallback: function() {
			$('#nbJeuxRendusVendeurSelectionne').html(tablejeuxnrendus.rows().count());
		}
	});

	// table des jeux donnés
	var tablejeuxndonnes = $('#jeuxdonnes').DataTable({
		paging: false,
		searching: false,
		info: false,
		processing: true,
		serverSide: true,
		language: {
			"url": "Json/fr-FR.json"
		},
		ajax: {
			type: 'POST',
			url: 'ajax/jeuxliste-get.php',
			data: {
				idVendeurEdition: $('#idVendeurEdition').val(),
				statutJeu: '6'
			},
		},
		columns: [
			{ data: "Code", name: "code_barre", title: "Code", width: "93px", className: "jeu-codeBarre" },
			{ data: "Jeu", name: "nj", title: "Jeu", width: "310px" },
			{ data: "Vendu", name: "Vendu", title: "Vendu", orderable: false, render: function(data, type, row) { return data + ' €'; } },
			{ data: "Rendu", name: "Rendu", title: "Rendu", orderable: false, render: function(data, type, row) { return data + ' €'; } },
			{ data: "vigilance", name: "vigilance", title: "Vigilance", visible: false },
			{
				data: "DateSortieStock", name: "date_sortie_stock", title: "Don", width: "158px", render: function(data) {
					var madate = mysqlTimeStampToDate(data);
					var month = madate.getMonth() + 1;
					var NomDuJour = "Inconnu";
					switch (madate.getDay()) {
						case 1: NomDuJour = "Lundi"; break;
						case 2: NomDuJour = "Mardi"; break;
						case 3: NomDuJour = "Mercredi"; break;
						case 4: NomDuJour = "Jeudi"; break;
						case 5: NomDuJour = "Vendredi"; break;
						case 6: NomDuJour = "Samedi"; break;
						case 0: NomDuJour = "Dimanche"; break;
					}
					return NomDuJour + " " + madate.getDate() + "/" + (month.length > 1 ? month : "0" + month) + "/" + madate.getFullYear() + " à " + madate.getHours() + "h" + (madate.getMinutes() > 9 ? madate.getMinutes() : "0" + madate.getMinutes());
				}
			}
		],
		createdRow: function(row, data, rowIndex) {
			var tab = Object.getOwnPropertyNames(data).sort();
			for (var i = 0; i < tab.length; i++) {
				$(row).attr('data-' + tab[i], data[tab[i]]);
			}
			if (data.vigilance === "Oui") {
				$(row).addClass('fst-italic');
			}
		},
		drawCallback: function() {
			$('#nbJeuxDonnesVendeurSelectionne').html(tablejeuxndonnes.rows().count());
		}
	});

    
	documentReadyDeLaModale();

    $("#showModal").click(function() {
    const idVendeur = $('#idVendeurEdition').val();
    if (idVendeur) {
        ouvreModaleModification(idVendeur);
    } else {
        alert("ID vendeur manquant !");
    }
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

	$('tbody').on('click', 'a.toFestival', function() {
		var ligne = $(this).closest('tr');
		if (AfficherPopUp("Voulez-vous vraiment donner ce jeu au festival ?", confirmation)) {
			$.ajax({
				type: 'POST',
				url: 'ajax/jeuxliste-updatefestival.php',
				data: {
					'id': ligne.data("dt_rowid"),
					ip: $('#ip').val()
				},
				success: function(data) {
					if (data.message2 == '0') AfficherPopUp("Operation impossible", alerte);;
					tablejeuxenstock.ajax.reload();
					tablejeuxndonnes.ajax.reload();
				},
				error: function(data) {
					alert('Exception:', data.message1);
				},
				dataType: 'json',
				async: false
			});
		}
	});

	$('tbody').on('click', 'a.delJeu', function() {
		var parentRow = $(this).closest('tr');
		var status;
		if (parentRow.closest('#jeuxenstock').length > 0) {
			status = '2';
		} else if (parentRow.closest('#jeuxnonrecus').length > 0) {
			status = '1';
		} else {
			console.warn("Tableau non reconnu.");
			return;
		}
		if (status != null && status != '') {
			var message = status == '1' ? "Voulez-vous vraiment supprimer ce jeu ?" : "Voulez-vous vraiment repasser ce jeu dans les jeux non-reçus ? Ceci effacera son code-barre.";
			if (AfficherPopUp(message, confirmation)) {
				$.ajax({
					type: 'POST',
					url: status == '1' ? 'ajax/jeuxliste-del.php' : 'ajax/jeuxliste-update.php',
					data: { 'id': parentRow.data("dt_rowid"), 'statut': status == '1' ? null : '1', 'old_id_statut': '2', 'codebarre': status == '1' ? null : '' },
					success: function(data) {
						if (data.message2 == '0') AfficherPopUp("Operation impossible", alerte);
						tableJeuxNonRecus.ajax.reload();
						tablejeuxenstock.ajax.reload();
					},
					dataType: 'json',
					async: true
				});
			}
		}
	});

	$('tbody').on('click', 'a.toLost', function() {
		var ligne = $(this).closest('tr');
		if (AfficherPopUp("Voulez-vous vraiment passer ce jeu en suspect ?", confirmation)) {
			$.ajax({
				type: 'POST',
				url: 'ajax/jeuxliste-setvigilance.php',
				data: { 'id': ligne.data("dt_rowid") },
				success: function(data) {
					if (data.message2 == '0') AfficherPopUp("Operation impossible", alerte);;
					tablejeuxenstock.ajax.reload();
				},
				dataType: 'json',
				async: false
			});
		}
	});

	/** ajout d'un jeu au vendeur**/
	$(".boutonsave").click(function() {
		var codebarre = VerifCodeBarre($('#CodeBarreAjout').val());
		var serr = '';
		if ($('#CodeBarreAjout').val() == '' || codebarre == '') {
			serr = serr + '<p class="bg-danger">Le code-barre doit être un nombre à 4 ou 5 chiffres.</p>';
		}
		if ($('#NomJeuAjout').val() == '') {
			serr = serr + '<p class="bg-danger">Le nom du jeu doit être renseigné.</p>';
		}
		var prixStr = $('#PrixAjout').val().trim();
		prixStr = prixStr.replace(",", ".").replace("€", "").trim();
		var prix = parseFloat(prixStr);

		if (prixStr === '') {
			serr += '<p class="bg-danger">Le prix doit être renseigné.</p>';
		} else if (isNaN(prix)) {
			serr += '<p class="bg-danger">Le prix doit être un nombre valide.</p>';
		} else if (prix < 0) {
			serr += '<p class="bg-danger">Le prix ne peut pas être négatif.</p>';
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
					idVendeurEdition: $('#idVendeurEdition').val(),
					nom: jeu[0].label,
					codebarre: $('#CodeBarreAjout').val(),
					vigilance: $('#idVendeurEdition').val() == 1 ? 1 : 0,
					statut: STATUS_JEUX_EN_STOCK,
					vendu: $('#PrixAjout').val(),
					ip: $('#ip').val()
				},
				success: function(data) {
					console.log("Réponse AJAX :", data);
				
					if (data.message2 === '1') {
						console.log('Jeu ajouté avec succès');
						tablejeuxenstock.ajax.reload();
						$('#formulaireajoutjeu').trigger("reset");
						$('#messageerreurformulaire').html('');
					} else {
						console.warn('Erreur retournée :', data.message1);
						$('#messageerreurformulaire').html('<div class="alert alert-danger">' + data.message1 + '</div>');
					}
				},
				error: function(xhr, status, error) {
					console.error('Erreur AJAX :', error);
					console.log('Réponse complète :', xhr.responseText);
				},
				
				dataType: 'json',
				async: false
			});
			
			/* ajout dans la table UI */
			tablejeuxenstock.ajax.reload();
			/* ide les champs input pour prochaine saisie */
			$('#formulaireajoutjeu').trigger("reset");
		}
	});

	/* transformation de la , en point du champ PrixAjout*/
	$(document).on('keyup', '#PrixAjout', function() {
		if ($(this).val() != '') {
			$(this).val($(this).val().replace(",", ""));
		}
		if ($(this).val() != '') {
			var prix = parseInt($(this).val());
			$("#PrixRendu").val(prix - Math.ceil(prix / 6));
			$('#PrixComm').val(Math.ceil(prix / 6));
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

// Fonction utilitaire pour extraire un paramètre de l'URL
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

$(document).ready(function () {
    //  Injecte dynamiquement l’ID vendeur depuis l’URL dans le champ caché
    const idFromUrl = getQueryParam('id');
    if (idFromUrl) {
        $('input[name="idVendeurImport"]').val(idFromUrl);
        console.log(" ID vendeur extrait de l'URL :", idFromUrl);
    } else {
        console.warn("Aucun ID trouvé dans l'URL.");
    }

    //  Gestion de l'envoi du formulaire
    $('#formulaireImportCSV').on('submit', function (e) {
        e.preventDefault();

        const fichier = $('#fileCSV')[0].files[0];
        const idVendeur = $('input[name="idVendeurImport"]').val();

        console.log("Fichier sélectionné :", fichier);
        console.log(" ID Vendeur :", idVendeur);

        if (!fichier || !idVendeur) {
            $('#importResultat').html("<div class='alert alert-danger'> Fichier ou identifiant vendeur manquant.</div>");
            return;
        }

        const formData = new FormData();
        formData.append('fileCSV', fichier);
        formData.append('idVendeurImport', idVendeur);

        $.ajax({
            url: 'ajax/import-jeux-csv.php', //  chemin correct relatif
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                console.log(" Réponse brute du serveur :", response);
                try {
                    const res = typeof response === "object" ? response : JSON.parse(response);
                    const colorClass = res.message2 === '1' ? 'alert-success' : 'alert-warning';
                    $('#importResultat').html(`<div class="alert ${colorClass}">${res.message1}</div>`);

                    //  Recharge la page après 1,5 sec si import réussi
                    if (res.message2 === '1') {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                if (res.pdf) {
				const url = `../../flip_baj/main/tmp/${res.pdf}`;
				const link = document.createElement('a');
				link.href = url;
				link.download = res.pdf;
				document.body.appendChild(link);
				console.log("URL finale de téléchargement :", url);

				link.click();
				document.body.removeChild(link);
				console.log("Téléchargement du PDF :", url);

				
				setTimeout(() => {
					location.reload();
				}, 2000);
			}




                } catch (e) {
                    console.error(" Erreur JSON :", e);
                    $('#importResultat').html("<div class='alert alert-danger'> Réponse serveur invalide.</div>");
                }
            },
            error: function (xhr, status, error) {
                console.error(" AJAX error :", error);
                console.log(" Status :", status);
                console.log(" xhr.responseText :", xhr.responseText);
                $('#importResultat').html("<div class='alert alert-danger'> Erreur AJAX lors de l’envoi du fichier.</div>");
            }
        });
    });
});





});

/**
 * Fonction exécutée lors du chargement complet de la page.
 * Elle vérifie si les informations du vendeur sont complètes et ouvre la modale de modification si nécessaire.
 */
window.onload = function() {
	if ($("#attestation_signee").val() != 'True' && $("#denomination_sociale").val() == '') {
		ouvreModaleModification($_GET('id')); //Ouvre la modale si les infos du vendeur ne sont pas complètes
	}
}