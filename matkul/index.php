<?php
require_once '../conn.php';
startSecureSession();
requireLogin();
$current_page = 'Matkul ' . basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Mata Kuliah</title>
    <!-- CSS Dependencies -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../menu.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require "../menu.html"; ?>

    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="loadingSpinner" style="display: none;">
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
                    <i class="fas fa-book mr-2 text-primary"></i>
                    Data Mata Kuliah
                </h2>
                <div>
                    <button class="btn btn-primary" id="btnAdd">
                        <i class="fas fa-plus mr-2"></i>Tambah Mata Kuliah
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
                               placeholder="Cari berdasarkan ID Matkul atau nama..."
                               autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="recordsPerPage" class="form-control">
                        <option value="5">5 records per page</option>
                        <option value="10">10 records per page</option>
                        <option value="15">15 records per page</option>
                        <option value="20">20 records per page</option>
                    </select>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table table-hover">
                   <thead>
                    <tr>
                        <th>ID Matkul</th>
                        <th>Nama Matkul</th>
                        <th>SKS</th>
                        <th>Jenis</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                    <tbody id="dataMatkul">
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
    <div class="modal fade" id="matkulModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="matkulModalLabel">
                        <i class="fas fa-book mr-2"></i>
                        <span id="modalTitle">Tambah Mata Kuliah</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formMatkul" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="id">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-id-card mr-2"></i>ID Matkul:
                            </label>
                            <input type="text" 
                                class="form-control" 
                                name="idmatkul" 
                                id="idmatkul" 
                                required 
                                pattern="A(11|12)\.\d{5}"
                                placeholder="e.g., A12.56101"
                                autocomplete="off">
                            <small class="form-text text-muted">Format: A12.56101</small>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-book mr-2"></i>Nama Matkul:
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="namamatkul" 
                                   id="namamatkul" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-hashtag mr-2"></i>SKS:
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   name="sks" 
                                   id="sks" 
                                   min="1" 
                                   max="6" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-tag mr-2"></i>Jenis:
                            </label>
                            <select class="form-control" id="jns" name="jns" required>
                                <option value="T">Teori (T)</option>
                                <option value="P">Praktek (P)</option>
                                <option value="T/P">Teori/Praktik (T/P)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar-alt mr-2"></i>Semester:
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   name="smt" 
                                   id="smt" 
                                   min="1" 
                                   max="8" 
                                   required>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bs-custom-file-input/1.3.4/bs-custom-file-input.min.js"></script>
    <script src="main.js"></script>

</body>
</html>