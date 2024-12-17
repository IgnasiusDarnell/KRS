<?php
require_once '../conn.php';
startSecureSession();
requireLogin();
$current_page = 'Dosen ' . basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'];
function getHomebaseOptions()
{
    return [
        'TI' => 'A11',
        'SI' => 'A12',
    ];
}

$homebaseOptions = getHomebaseOptions();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dosen</title>
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
        <h1 class="mb-4">Manajemen Data Dosen</h1>
        <button class="btn btn-primary mb-2" id="add-new">Tambah Data Baru</button>
        <button class="btn btn-danger mb-2" onclick="window.location.href='generate_pdf.php'">Cetak PDF</button>
        <div class="mb-3">
            <input type="text" id="search" class="form-control" placeholder="Cari dosen berdasarkan NPP atau nama...">
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
                    <th>NPP</th>
                    <th>Nama Dosen</th>
                    <th>Homebase</th>
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
                        <h5 class="modal-title" id="dataModalLabel">Tambah/Edit Data Dosen</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="data-form">
                            <input type="hidden" id="id">

                            <div class="form-group">
                                <label for="npp">NPP</label>
                                <input type="text" id="npp" name="npp" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="namadosen">Nama Dosen</label>
                                <input type="text" id="namadosen" name="namadosen" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="homebase">Homebase</label>
                                <select id="homebase" name="homebase" class="form-control">
                                    <option value="">Pilih Homebase</option>
                                    <?php foreach ($homebaseOptions as $code => $name): ?>
                                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                            <td>${row.npp}</td>
                            <td>${row.namadosen || ''}</td>
                            <td>${row.homebase || ''}</td>
                            <td>
                                <button class="btn btn-warning btn-sm edit" data-npp="${row.npp}">Edit</button>
                                <button class="btn btn-danger btn-sm delete" data-npp="${row.npp}">Hapus</button>
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
                $('#id').val('');
                $('#npp').prop('readonly', false);
                $('#dataModal').modal('show');
            });

            $('#data-table').on('click', '.edit', function() {
                const npp = $(this).data('npp');
                $.post(apiUrl, {
                    action: 'get_data',
                    npp
                }, function(response) {
                    $('#id').val(response.npp);
                    $('#npp').val(response.npp).prop('readonly', true);
                    $('#namadosen').val(response.namadosen);
                    $('#homebase').val(response.homebase);
                    $('#dataModal').modal('show');
                }, 'json');
            });

            $('#data-table').on('click', '.delete', function() {
                if (confirm('Yakin ingin menghapus data ini?')) {
                    const npp = $(this).data('npp');
                    $.post(apiUrl, {
                        action: 'delete',
                        npp
                    }, function(response) {
                        alert(response.message);
                        loadTable($('#search').val(), currentPage, recordsPerPage);
                    }, 'json');
                }
            });

            $('#data-form').submit(function(e) {
                e.preventDefault();

                const isEdit = $('#id').val() !== ''; // Check if we're in edit mode
                const npp = $('#npp').val();
                const namadosen = $('#namadosen').val();
                const homebase = $('#homebase').val();

                // Clear previous validation messages
                $('.validation-error').remove();

                // Perform custom validation
                let hasError = false;

                if (isEdit) {
                    if (!npp) {
                        $('#npp').after('<div class="text-danger validation-error">NPP is required.</div>');
                        hasError = true;
                    }

                    if (!namadosen) {
                        $('#namadosen').after('<div class="text-danger validation-error">Nama Dosen is required.</div>');
                        hasError = true;
                    }

                    if (!homebase) {
                        $('#homebase').after('<div class="text-danger validation-error">Homebase is required.</div>');
                        hasError = true;
                    }
                }

                if (hasError) {
                    return;
                }

                const formData = {
                    action: 'save',
                    id: $('#id').val(),
                    npp,
                    namadosen,
                    homebase,
                };

                $.post(apiUrl, formData, function(response) {
                    alert(response.message);
                    $('#dataModal').modal('hide');
                    loadTable($('#search').val(), currentPage, recordsPerPage);
                }, 'json').fail(function(xhr) {
                    alert(xhr.responseJSON.error);
                });
            });

            loadTable();
        });
    </script>
</body>

</html>