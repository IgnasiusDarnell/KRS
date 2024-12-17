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
    <title>Data Mata Kuliah</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../menu.css">
</head>

<body>
    <?php
    require "../menu.html";
    ?>

    <div class="container mt-5">
        <h1 class="mb-4">Manajemen Data Mata Kuliah</h1>
        <button class="btn btn-primary mb-2" id="add-new">Tambah Data Baru</button>
        <button class="btn btn-danger mb-2" onclick="window.location.href='generate_pdf.php'">Cetak PDF</button>
        <div class="mb-3">
            <input type="text" id="search" class="form-control" placeholder="Cari mata kuliah berdasarkan ID atau nama...">
        </div>

        <div class="form-group">
            <label for="recordsPerPage">Records per page:</label>
            <select id="recordsPerPage" class="form-control" style="width: auto; display: inline-block;">
                <option value="5" selected>5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
            </select>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Mata Kuliah</th>
                    <th>Nama Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Jenis</th>
                    <th>Semester</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="data-table">
                <!-- Data will be loaded here via AJAX -->
            </tbody>
        </table>

        <nav>
            <ul class="pagination justify-content-center" id="pagination">
                <!-- Pagination buttons will be added here -->
            </ul>
        </nav>

        <!-- Modal for Adding/Editing Data -->
        <div class="modal fade" id="dataModal" tabindex="-1" role="dialog" aria-labelledby="dataModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dataModalLabel">Tambah/Edit Data Mata Kuliah</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="data-form">
                            <div class="form-group">
                                <label for="idmatkul">ID Mata Kuliah</label>
                                <input type="text" id="idmatkul" name="idmatkul" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="namamatkul">Nama Mata Kuliah</label>
                                <input type="text" id="namamatkul" name="namamatkul" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="sks">SKS</label>
                                <input type="number" id="sks" name="sks" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="jns">Jenis</label>
                                <input type="text" id="jns" name="jns" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="smt">Semester</label>
                                <input type="number" id="smt" name="smt" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-success">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const apiUrl = 'proses.php';
            let currentPage = 1;
            let recordsPerPage = $('#recordsPerPage').val();

            function loadTable(query = '', page = 1, limit = recordsPerPage) {
                $.post(apiUrl, {
                    action: 'load',
                    query,
                    page,
                    limit
                }, function(response) {
                    const rows = response.data.map(row => `
                        <tr>
                            <td>${row.idmatkul}</td>
                            <td>${row.namamatkul || ''}</td>
                            <td>${row.sks || ''}</td>
                            <td>${row.jns || ''}</td>
                            <td>${row.smt || ''}</td>
                            <td>
                                <button class="btn btn-warning btn-sm edit" data-idmatkul="${row.idmatkul}">Edit</button>
                                <button class="btn btn-danger btn-sm delete" data-idmatkul="${row.idmatkul}">Hapus</button>
                            </td>
                        </tr>
                    `);

                    $('#data-table').html(rows);
                    renderPagination(response.totalPages, page);
                }, 'json');
            }

            function renderPagination(totalPages, currentPage) {
                let paginationHTML = '';

                for (let i = 1; i <= totalPages; i++) {
                    paginationHTML += `
                        <li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }

                $('#pagination').html(paginationHTML);
            }

            $('#pagination').on('click', '.page-link', function(e) {
                e.preventDefault();
                currentPage = $(this).data('page');
                const query = $('#search').val();
                loadTable(query, currentPage, recordsPerPage);
            });

            $('#recordsPerPage').change(function() {
                recordsPerPage = $(this).val();
                loadTable($('#search').val(), 1, recordsPerPage);
            });

            $('#search').on('input', function() {
                const query = $(this).val();
                loadTable(query, 1, recordsPerPage);
            });

            $('#add-new').click(function() {
                $('#data-form')[0].reset();
                $('#idmatkul').prop('readonly', false);
                $('#dataModal').modal('show');
            });

            $('#data-table').on('click', '.edit', function() {
                const idmatkul = $(this).data('idmatkul');
                $.post(apiUrl, {
                    action: 'get_data',
                    idmatkul
                }, function(response) {
                    $('#idmatkul').val(response.idmatkul).prop('readonly', true);
                    $('#namamatkul').val(response.namamatkul);
                    $('#sks').val(response.sks);
                    $('#jns').val(response.jns);
                    $('#smt').val(response.smt);
                    $('#dataModal').modal('show');
                }, 'json');
            });

            $('#data-table').on('click', '.delete', function() {
                if (confirm('Yakin ingin menghapus data ini?')) {
                    const idmatkul = $(this).data('idmatkul');
                    $.post(apiUrl, {
                        action: 'delete',
                        idmatkul
                    }, function(response) {
                        alert(response.message);
                        loadTable($('#search').val(), currentPage, recordsPerPage);
                    }, 'json');
                }
            });

            $('#data-form').submit(function(e) {
                e.preventDefault();

                const idmatkul = $('#idmatkul').val().trim();
                const namamatkul = $('#namamatkul').val().trim();
                const sks = parseInt($('#sks').val());
                const jns = $('#jns').val().trim();
                const smt = parseInt($('#smt').val());
                const isEdit = $('#idmatkul').prop('readonly'); // Use readonly to determine if editing

                // Frontend validation
                if (!idmatkul || !namamatkul || sks <= 0 || !jns || smt <= 0) {
                    alert('Semua field harus diisi dengan benar.');
                    return;
                }

                // Submit the form via AJAX
                $.post(apiUrl, {
                    action: 'save',
                    idmatkul,
                    namamatkul,
                    sks,
                    jns,
                    smt,
                    is_edit: isEdit // Send the edit flag
                }, function(response) {
                    alert(response.message);
                    $('#dataModal').modal('hide');
                    loadTable($('#search').val(), currentPage, recordsPerPage);
                }, 'json').fail(function(xhr) {
                    alert(xhr.responseJSON.error || 'Terjadi kesalahan saat menyimpan data.');
                });
            });


            loadTable();
        });
    </script>
</body>

</html>