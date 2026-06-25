// Détection et affichage dynamique du nom du fichier sélectionné
document.getElementById('csvJassMeux').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const badge = document.getElementById('fileSelectedBadge');
    const nameDisplay = document.getElementById('fileNameDisplay');

    if (file) {
        nameDisplay.textContent = file.name;
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
});

// Traitement du formulaire
document.getElementById('formJassMeux').addEventListener('submit', function(e) {
    e.preventDefault();

    const fileInput = document.getElementById('csvJassMeux');
    const file = fileInput.files[0];
    if (!file) return;

    // --- SI C'EST UN FICHIER EXCEL (.xlsx) ---
    if (file.name.endsWith('.xlsx')) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const data = new Uint8Array(event.target.result);

            // On décode le fichier Excel avec la librairie SheetJS
            const workbook = XLSX.read(data, {type: 'array'});
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

            // On le convertit en texte CSV pur
            const csvString = XLSX.utils.sheet_to_csv(firstSheet, { FS: ";" });

            // On crée un faux fichier CSV en mémoire pour tromper le serveur
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const fileAEnvoyer = new File([blob], file.name.replace('.xlsx', '.csv'), { type: 'text/csv' });

            // On lance la suite du traitement
            lancerImportation(csvString, fileAEnvoyer);
        };
        // Attention : on lit l'Excel sous forme de buffer binaire, pas de texte !
        reader.readAsArrayBuffer(file);
    }
    // --- SI C'EST DÉJÀ UN FICHIER CSV ---
    else {
        const reader = new FileReader();
        reader.onload = function(event) {
            const csvString = event.target.result;
            lancerImportation(csvString, file);
        };
        reader.readAsText(file);
    }
});


/**
 * Fonction qui s'occupe de compter les lignes et d'envoyer la requête au serveur.
 * Elle est appelée que le fichier d'origine soit un CSV ou un XLSX converti.
 */
function lancerImportation(csvText, fileObject) {
    const fileInput = document.getElementById('csvJassMeux');
    const btn = document.getElementById('btnImport');
    const resultDiv = document.getElementById('jassmeuxResult');

    // On compte le nombre de jeux pour la sécurité
    const lines = csvText.split('\n').filter(line => line.trim() !== '');
    const nbJeux = lines.length - 3; // On déduit tes lignes d'en-tête

    if (nbJeux > 50) {
        const confirmImport = confirm(`Attention : Ce fichier contient un volume de ${nbJeux} jeux. Êtes-vous sûr de vouloir lancer cet import massif ?`);
        if (!confirmImport) {
            fileInput.value = '';
            document.getElementById('fileSelectedBadge').classList.add('d-none');
            return;
        }
    }

    // --- ANIMATION DE CHARGEMENT ---
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement et intégration en base...`;
    resultDiv.innerHTML = '';

    const formData = new FormData();
    // On attache notre fichier (le vrai CSV, ou l'Excel converti en CSV)
    formData.append('fileCSV', fileObject);

    fetch('jassmeux/process_import.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            // Remise à zéro du bouton
            btn.disabled = false;
            btn.innerHTML = `<i class="bi bi-lightning-charge-fill"></i> Lancer l'importation de masse`;

            if (data.message2 === '1') {
                resultDiv.innerHTML = `
            <div class="alert alert-success border-0 rounded-4 p-4 shadow-sm">
                <h5 class="fw-bold text-success mb-2"><i class="bi bi-check-circle-fill"></i> Importation complétée !</h5>
                <p class="mb-3 text-secondary">${data.message1}</p>
                ${data.pdf ? `
                    <a href="../tmp/${data.pdf}" target="_blank" class="btn btn-success btn-md d-inline-flex align-items-center gap-2 shadow-sm fw-bold rounded-3">
                        <i class="bi bi-printer"></i> Imprimer les étiquettes PDF
                    </a>
                ` : ''}
            </div>`;
            } else {
                resultDiv.innerHTML = `
            <div class="alert alert-danger border-0 rounded-4 p-4 shadow-sm">
                <h5 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-octagon-fill"></i> Erreur lors du traitement</h5>
                <p class="mb-0 text-dark">${data.message1}</p>
            </div>`;
            }
        })
        .catch(error => {
            console.error('Erreur JassMeux:', error);
            btn.disabled = false;
            btn.innerHTML = `<i class="bi bi-lightning-charge-fill"></i> Lancer l'importation de masse`;
            resultDiv.innerHTML = `<div class="alert alert-danger rounded-4">Une erreur réseau ou système est survenue.</div>`;
        });
}