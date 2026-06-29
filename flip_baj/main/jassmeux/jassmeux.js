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

    const reader = new FileReader();
    reader.onload = function(event) {
        const data = new Uint8Array(event.target.result);

        // 1. SHEETJS LIT TOUT (XLSX, CSV, peu importe le séparateur d'origine)
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

        // 2. ON TRANSFORME EN TABLEAU JS (Pour lire les colonnes C, D, E proprement)
        const rows = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' });

        // 3. ON COMPTE EXACTEMENT COMME TON PHP
        let nbJeux = 0;

        // La boucle commence à l'index 3 (ce qui correspond à la ligne 4 d'Excel)
        for (let i = 3; i < rows.length; i++) {
            const row = rows[i];

            // On s'assure que la ligne a au moins des données jusqu'à la colonne Prix
            if (row.length >= 4) {
                const nomJeu = String(row[2]).trim(); // Colonne C (Index 2)
                const prixBrut = String(row[3]).trim(); // Colonne D (Index 3)
                const quantiteBrut = String(row[4] || '1').trim(); // Colonne E (Index 4)

                // Si le Nom et le Prix sont présents
                if (nomJeu !== '' && prixBrut !== '') {
                    // On vérifie que le prix est un chiffre valide (comme ton PHP)
                    const prix = parseInt(prixBrut, 10);
                    if (!isNaN(prix) && prix > 0) {
                        let qte = parseInt(quantiteBrut, 10);
                        if (isNaN(qte) || qte <= 0) qte = 1;

                        // On ajoute la quantité réelle au compteur de jeux
                        nbJeux += qte;
                    }
                }
            }
        }

        // 4. SÉCURITÉ ET ALERTE
        if (nbJeux === 0) {
            alert("Aucun jeu valide n'a été trouvé. Vérifiez que les colonnes 'Nom du jeu' et 'Prix' sont bien remplies à partir de la ligne 4.");
            fileInput.value = '';
            document.getElementById('fileSelectedBadge').classList.add('d-none');
            return;
        }

        if (nbJeux > 50) {
            const confirmImport = confirm(`Attention : Ce fichier contient un volume de ${nbJeux} jeux. Êtes-vous sûr de vouloir lancer cet import massif ?`);
            if (!confirmImport) {
                fileInput.value = '';
                document.getElementById('fileSelectedBadge').classList.add('d-none');
                return;
            }
        }

        // 5. ON GÉNÈRE LE CSV PARFAIT POUR LE PHP (on force le point-virgule)
        const csvString = XLSX.utils.sheet_to_csv(firstSheet, { FS: ";" });
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const nomFichier = file.name.replace(/\.[^/.]+$/, "") + ".csv";
        const fileAEnvoyer = new File([blob], nomFichier, { type: 'text/csv' });

        // 6. ENVOI AU SERVEUR
        envoyerAuServeur(fileAEnvoyer);
    };

    // On lit en binaire : ça marche pour Excel ET pour CSV !
    reader.readAsArrayBuffer(file);
});


function envoyerAuServeur(fileObject) {
    const btn = document.getElementById('btnImport');
    const resultDiv = document.getElementById('jassmeuxResult');

    // --- ANIMATION DE CHARGEMENT ---
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement et intégration en base...`;
    resultDiv.innerHTML = '';

    const formData = new FormData();
    formData.append('fileCSV', fileObject);

    fetch('jassmeux/process_import.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
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