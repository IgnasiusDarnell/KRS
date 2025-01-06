<?php
require_once '../conn.php';
startSecureSession();
requireLogin();
$current_page = 'Kultawar ' . basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kultawar</title>
    <!-- CSS Dependencies -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../menu.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require "../menu.html"; ?>

    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="loadingSpinner">
        <div class="loading-message">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading...</p>
        </div>
    </div>

    <div class="container mt-5">
        <div class="card p-4">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-calendar-alt mr-2 text-primary"></i>
                    Data Kultawar
                </h2>
                <div>
                    <button class="btn btn-primary" id="btnAdd">
                        <i class="fas fa-plus mr-2"></i>Tambah Kultawar
                    </button>
                    <button class="btn btn-danger" id="btnGeneratePDF">
                        <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                    </button>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               class="form-control" 
                               id="search" 
                               placeholder="Cari berdasarkan mata kuliah atau dosen..."
                               autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterKlp" class="form-control">
                        <option value="">Semua Kelompok</option>
                        <option value="A12.6201">A12.62</option>
                        <option value="A12.6301">A12.63</option>
                        <option value="A12.6701">A12.67</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="recordsPerPage" class="form-control">
                        <option value="5">5 records per page</option>
                        <option value="10">10 records per page</option>
                        <option value="15">15 records per page</option>
                        <option value="20">20 records per page</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterHari" class="form-control">
                        <option value="">Semua Hari</option>
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                        <option value="Rabu">Rabu</option>
                        <option value="Kamis">Kamis</option>
                        <option value="Jumat">Jumat</option>
                    </select>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mata Kuliah</th>
                            <th>Dosen</th>
                            <th>Kelompok</th>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Ruang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="dataKultawar">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination Section -->
            <div class="mt-4">
                <div class="pagination-container" id="pagination"></div>
                <div id="currentPageInfo" class="page-info"></div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="kultawarModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kultawarModalLabel">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span id="modalTitle">Tambah Kultawar</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formKultawar" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="idkultawar" id="idkultawar">
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-book mr-2"></i>Mata Kuliah:
                            </label>
                            <select class="form-control" id="idmatkul" name="idmatkul" required>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-user-tie mr-2"></i>Dosen:
                            </label>
                            <select class="form-control" id="npp" name="npp" required>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-users mr-2"></i>Kelompok:
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="klp" 
                                   id="klp" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar-day mr-2"></i>Hari:
                            </label>
                            <select class="form-control" id="hari" name="hari" required>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-clock mr-2"></i>Jam:
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="jamkul" 
                                   id="jamkul" 
                                   placeholder="e.g., 07.00-08.40"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-building mr-2"></i>Ruang:
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="ruang" 
                                   id="ruang" 
                                   placeholder="e.g., H.5.5">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="btnSave">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="main.js"></script>

    <script>
        $(document).ready(function() {
            // Show loading spinner
            function showLoadingSpinner() {
                $('#loadingSpinner').show();
            }

            // Hide loading spinner
            function hideLoadingSpinner() {
                $('#loadingSpinner').hide();
            }

            // Handle Generate PDF button click
            $('#btnGeneratePDF').click(function() {
                Swal.fire({
                    title: 'Pilih Opsi PDF',
                    text: 'Apakah Anda ingin melihat atau mengunduh PDF?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-eye mr-2"></i>Lihat PDF',
                    cancelButtonText: '<i class="fas fa-download mr-2"></i>Unduh PDF',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // View PDF in the browser
                        showLoadingSpinner();
                        window.open('generate_pdf.php?action=view', '_blank');
                        hideLoadingSpinner();
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Download PDF
                        showLoadingSpinner();
                        window.location.href = 'generate_pdf.php?action=download';
                        hideLoadingSpinner();
                    }
                });
            });
        });
    </script>
</body>
</html>