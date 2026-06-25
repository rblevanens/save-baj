/**
 * Définition des constantes pour les différents statuts des jeux.
 */
var STATUS_JEUX_PASRECU = '1';
var STATUS_JEUX_EN_STOCK = '2';
var STATUS_JEUX_VENDUS = '3';
var STATUS_JEUX_RENDUS = '4';
var STATUS_JEUX_DONNES = '6';
var STATUS_JEUX_NONRETROUVES = '7';
var STATUS_JEUX_AUBOX = '8';
var STATUS_JEUX_SUPPRIMES = '9';

/* variable pour le fichier admin */
var tableJeuxEnStock;
var optionsStatut;

/**
 * Définition des constantes pour les types d'alertes.
 */
var alerte = 'Alert';
var confirmation = 'Confirm';

/**
 * Définition des types de transaction.
 */
var TYPE_TRANS_CHEQUE = 'chèque';
var TYPE_TRANS_PAYPAL = 'paypal';
var TYPE_TRANS_CARTE = 'cb';
var TYPE_TRANS_ESPECES = 'espèces';
var TYPE_TRANS_VIREMENT = 'virement';

/**
 * Fonction pour récupérer les paramètres de la requête GET.
 *
 * @param {string} param - Le paramètre à récupérer.
 * @returns {string|null} - La valeur du paramètre si trouvé, sinon null.
 */
function $_GET(param) {
	var vars = {};
	window.location.href.replace(location.hash, '').replace(
		/[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
		function(m, key, value) { // callback
			vars[key] = value !== undefined ? value : '';
		}
	);

	if (param) {
		return vars[param] ? vars[param] : null;
	}
	return vars;
}

/**
 * Supprime le préfixe "Festival_" du code-barres.
 *
 * @param {string} codeBarreFestival - Le code-barres avec le préfixe.
 * @returns {string} - Le code-barres sans le préfixe.
 */
function VraiCodeBarre(codeBarreFestival) {
	return codeBarreFestival.replace('Festival_', '')
}



/**
 * Vérifie la validité d'un code-barres.
 *
 * @param {string} codeBarreAVerifier - Le code-barres à vérifier.
 * @returns {string} - Le code-barres valide ou une chaîne vide si invalide.
 */
function VerifCodeBarre(codeBarreAVerifier) {
	var Resultat = '';
	var i;
	var chiffre;
	var code_barre_len;
	code_barre_len = 'Festival_nnnnn'.length;

	codeBarreAVerifier = codeBarreAVerifier.trim();
	if (codeBarreAVerifier.length <= 5) {
		while (codeBarreAVerifier.length < 5) { codeBarreAVerifier = '0' + codeBarreAVerifier; }
		codeBarreAVerifier = 'Festival_' + codeBarreAVerifier;
	}
	else {
		if (codeBarreAVerifier.length != code_barre_len) { return Resultat; }
	}

	if (codeBarreAVerifier.substr(0, code_barre_len - 5) != 'Festival_') { return Resultat; }

	for (i = (code_barre_len - 5); i < code_barre_len; i++) {
		chiffre = codeBarreAVerifier.substr(i, 1);

		if ((chiffre != '0') &&
			(chiffre != '1') &&
			(chiffre != '2') &&
			(chiffre != '3') &&
			(chiffre != '4') &&
			(chiffre != '5') &&
			(chiffre != '6') &&
			(chiffre != '7') &&
			(chiffre != '8') &&
			(chiffre != '9')) { return Resultat; }
	}

	Resultat = codeBarreAVerifier;
	return Resultat;

}

/**
 * Affiche une pop-up d'alerte ou de confirmation.
 *
 * @param {string} message - Le message à afficher.
 * @param {string} typeAlert - Le type de pop-up (Alert ou Confirm).
 * @returns {boolean} - true si alerte, sinon résultat de la confirmation.
 */
function AfficherPopUp(message, typeAlert) {
	var resultat;
	if (typeAlert == "Alert") {
		alert(message);
		resultat = true;
	}
	if (typeAlert == "Confirm") {
		resultat = confirm(message);
	}
	return resultat;
}

/**
 * Convertit un timestamp MySQL en objet Date JavaScript.
 *
 * @param {string} timestamp - Le timestamp MySQL.
 * @returns {Date} - L'objet Date JavaScript.
 */
function mysqlTimeStampToDate(timestamp) {
	//function parses mysql datetime string and returns javascript Date object
	//input has to be in this format: 2007-06-05 15:26:02
	var regex = /^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/;
	var parts = "      ";
	if (timestamp != null) {
		parts = timestamp.replace(regex, "$1 $2 $3 $4 $5 $6").split(' ');
	}
	else {
		return new Date();
	}
	return new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]);
}

// Fonction permettant de recuperer l'url en parametre

function getUrlParameter(name) {
	name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
	var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
	var results = regex.exec(location.search);
	return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}
