<?php
require_once '../conn.php';
startSecureSession();
requireLogin();
$current_page = 'Mahasiswa ' . basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahasiswa</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="../menu.css">
</head>

<body class="container mt-5">
    <?php
    require "../menu.html";
    ?>

    <h2 class="mb-4">Data Mahasiswa</h2>
    <button class="btn btn-primary mb-3" id="btnAdd">Tambah Mahasiswa</button>
    <button class="btn btn-danger mb-3" onclick="window.location.href='generate_pdf.php'">Cetak PDF</button>
    <div class="form-group">
        <input type="text" class="form-control" id="search" placeholder="Cari mahasiswa...">
    </div>

    <div class="form-group">
        <label for="recordsPerPage">Records per page:</label>
        <select id="recordsPerPage" class="form-control" style="width: auto; display: inline-block;">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="20">20</option>
        </select>
    </div>


    <table class="table table-bordered">
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Foto</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="dataMahasiswa"></tbody>
    </table>

    <div id="pagination" class="mb-3" style="display: flex; gap:5px"></div>

    <div class="modal fade" id="mahasiswaModal" tabindex="-1" role="dialog" aria-labelledby="mahasiswaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mahasiswaModalLabel">Tambah/Edit Mahasiswa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formMahasiswa" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="id">
                        <div class="form-group">
                            <label for="nim">NIM:</label>
                            <input type="text" class="form-control" name="nim" id="nim" required>
                        </div>
                        <div class="form-group">
                            <label for="nama">Nama:</label>
                            <input type="text" class="form-control" name="nama" id="nama" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="text" class="form-control" name="email" id="email" required>
                        </div>
                        <div class="form-group">
                            <label for="foto">Foto:</label>
                            <input type="file" class="form-control-file" name="foto" id="foto" accept="image/*">
                        </div>
                        <div id="previewContainer" class="mb-3" style="display: none;">
                            <img id="imagePreview" src="#" alt="Preview" class="mahasiswa-img">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSave">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <div id="currentPageInfo" class="text-right mt-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            let currentPage = 1;
            let recordsPerPage = $('#recordsPerPage').val();

            // Handle dropdown change
            $('#recordsPerPage').change(function() {
                recordsPerPage = $(this).val();
                currentPage = 1;
                loadData($('#search').val(), currentPage, recordsPerPage);
            });

            // Search input handler
            $('#search').on('input', function() {
                loadData($(this).val(), currentPage, recordsPerPage);
            });

            // Load data function
            function loadData(query = '', page = 1, limit = recordsPerPage) {
                $.ajax({
                    url: 'proses.php',
                    method: 'POST',
                    data: {
                        action: 'load',
                        query: query,
                        page: page,
                        limit: limit
                    },
                    dataType: 'json',
                    success: function(response) {
                        renderTable(response.data);
                        setupPagination(response.totalPages, page);
                    }
                });
            }

            function renderTable(data) {
                let html = '';
                if (data.length > 0) {
                    data.forEach(function(row) {
                        html += `
                    <tr>
                        <td>${escapeHtml(row.nim)}</td>
                        <td>${escapeHtml(row.nama)}</td>
                        <td>${escapeHtml(row.email)}</td>
                        <td><img src="../photo/${escapeHtml(row.foto)}" class="mahasiswa-img" height="90px"></td>
                        <td>
                            <button class="btn btn-sm btn-primary edit" data-id="${row.id}">Edit</button>
                            <button class="btn btn-sm btn-danger delete" data-id="${row.id}">Hapus</button>
                        </td>
                    </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="5" class="text-center">Tidak ada data ditemukan</td></tr>';
                }
                $('#dataMahasiswa').html(html);
            }

            function setupPagination(totalPages, currentPage) {
                let paginationHtml = '';
                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `<button class="btn btn-link page-link ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
                }
                $('#pagination').html(paginationHtml);

                // Perbarui nomor halaman saat ini
                $('#currentPageInfo').text(`Anda berada di halaman ${currentPage} dari ${totalPages} halaman`);

                // Tambahkan event listener untuk tombol pagination
                $('.page-link').click(function() {
                    currentPage = $(this).data('page');
                    loadData($('#search').val(), currentPage, recordsPerPage);
                });
            }


            // Initial load
            loadData();

            $(document).on('click', '.page-link', function() {
                currentPage = $(this).data('page');
                loadData($('#search').val(), currentPage);
            });

            // Search functionality
            $('#search').on('keyup', function() {
                var query = $(this).val();
                loadData(query, currentPage);
            });

            // NIM auto-formatting
            $('#nim').on('input', function() {
                let nim = $(this).val().replace(/[^A-Za-z0-9]/g, '');
                if (nim.length > 3 && nim.length <= 7) {
                    nim = nim.slice(0, 3) + '.' + nim.slice(3);
                } else if (nim.length > 7) {
                    nim = nim.slice(0, 3) + '.' + nim.slice(3, 7) + '.' + nim.slice(7, 12);
                }
                $(this).val(nim);
            });

            // NIM validation and duplication check
            $('#nim').on('blur', function() {
                var nim = $(this).val();
                var nimPattern = /^[A-Za-z0-9]{3}\.[0-9]{4}\.[0-9]{5}$/;

                if (!nimPattern.test(nim)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'NIM harus dalam format A12.2022.06905!'
                    });
                    $('#nama, #email').prop('disabled', true);
                    return;
                }

                $.ajax({
                    url: 'proses.php',
                    method: 'POST',
                    data: {
                        action: 'check_nim',
                        nim: nim
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'NIM sudah ada!'
                            });
                            $('#nama, #email').prop('disabled', true);
                        } else {
                            $('#nama, #email').prop('disabled', false);
                        }
                    }
                });
            });

            // Reset form fields
            function resetForm() {
                $('#formMahasiswa')[0].reset();
                $('#id').val('');
                $('#previewContainer').hide();
                $('#nim').prop('disabled', false);
            }

            // Deletion functionality
            $(document).on('click', '.delete', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda tidak dapat mengembalikan data ini!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'proses.php',
                            method: 'POST',
                            data: {
                                action: 'delete',
                                id: id
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.error
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Sukses',
                                        text: response.message
                                    });
                                    loadData();
                                }
                            }
                        });
                    }
                });
            });

            // Utility function to escape HTML
            function escapeHtml(text) {
                return text.replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>


</body>

</html>