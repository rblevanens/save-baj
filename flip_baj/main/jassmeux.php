<?php
namespace flip_baj\main;
include 'header.php';
?>

    <style>
        .jassmeux-dropzone {
            border: 2px dashed #0096d6;
            background-color: #f8f9fa;
            transition: all 0.2s ease-in-out;
        }
        .jassmeux-dropzone:hover {
            background-color: #e2e6ea;
            border-color: #007bb5;
            transform: scale(1.01);
        }
        .btn-jassmeux {
            background-color: #0096d6;
            color: white;
            border: none;
        }
        .btn-jassmeux:hover {
            background-color: #007bb5;
            color: white;
        }
        ul.filariane li:nth-child(3n) a {
            background: #0d6efd;
        }
    </style>

    <ul class="filariane ms-3 mt-3">
        <li><a href="index.php">Home</a></li>
        <li><a href="admin.php">Admin</a></li>
        <li><a href="jassmeux.php">Module JassMeux</a></li>
    </ul>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="card shadow border-0 rounded-4">
                    <div class="card-header text-white text-center py-4 rounded-top-4" style="background-color: #0096d6;">
                        <h3 class="mb-1 fw-bold"><i class="bi bi-box-arrow-in-down-left"></i> Module JassMeux</h3>
                        <p class="mb-0 opacity-75">Importation automatique de jeux & Génération d'étiquettes</p>
                    </div>

                    <div class="card-body p-5">

                        <div class="alert alert-info border-0 rounded-3 mb-4 d-flex align-items-start shadow-sm">
                            <i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Structure du fichier attendu (.csv/.xlsx) :</h6>
                                <small class="text-muted d-block mb-1">
                                    <strong>Ligne 1 :</strong> Coordonnées du vendeur.
                                </small>
                                <small class="text-muted d-block">
                                    <strong>Ligne 4 et + :</strong> Liste des jeux. Les prix doivent être <strong>ronds</strong>.
                                </small>
                            </div>
                        </div>

                        <form id="formJassMeux" class="text-center">
                            <div class="jassmeux-dropzone p-5 mb-4 rounded-4 position-relative" id="dropZone">
                                <i class="bi bi-file-earmark-excel-fill text-primary display-3 mb-3 d-block"></i>
                                <span class="fs-5 fw-semibold d-block text-dark mb-2">Glissez votre fichier ici ou cliquez pour parcourir</span>
                                <span class="text-muted small d-block mb-4">Format CSV uniquement (séparateur point-virgule)</span>

                                <input type="file" id="csvJassMeux" accept=".csv" required
                                       class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" style="cursor: pointer;">

                                <div id="fileSelectedBadge" class="d-none">
                                <span class="badge bg-success p-2 fs-6 rounded-pill shadow-sm">
                                    <i class="bi bi-file-check"></i> <span id="fileNameDisplay">Aucun fichier</span>
                                </span>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" id="btnImport" class="btn btn-jassmeux btn-lg py-3 rounded-3 shadow-sm fw-bold">
                                    <i class="bi bi-lightning-charge-fill"></i> Lancer l'importation de masse
                                </button>
                            </div>
                        </form>

                        <div id="jassmeuxResult" class="mt-4"></div>

                    </div>
                </div>

            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="jassmeux/jassmeux.js"></script>

    <?php
include 'footer.php';
?>