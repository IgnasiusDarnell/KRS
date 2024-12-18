<?php
session_start();
require '../conn.php';
$conn = getDbConnection();

// Set the page title
$current_page = 'Kultawar ' . basename($_SERVER['PHP_SELF']);

// Pagination Variables
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10; // Default records per page
$current_page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Default current page

// Get total records
$total_records_sql = "SELECT COUNT(*) AS total FROM kultawar";
$total_result = $conn->query($total_records_sql);
$total_records = $total_result->fetch_assoc()['total'];

// Calculate pagination values
$total_pages = ceil($total_records / $records_per_page);
$offset = ($current_page_num - 1) * $records_per_page;

// Fetch records for the current page
$sql = "SELECT * FROM kultawar ORDER BY idkultawar ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Check for messages in session
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Kultawar</title>
    <link rel="stylesheet" href="../menu.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .custom-alert {
            position: fixed;
            top: 10%;
            left: 50%;
            transform: translate(-50%, 0);
            z-index: 1050;
            display: none;
            animation: fade-in 1s ease-in-out forwards, fade-out 1s ease-in-out 2.5s forwards;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fade-out {
            to {
                opacity: 0;
            }
        }
    </style>
</head>
<?php require "../menu.html"; ?>

<body>
    <div class="container mt-5">
        <!-- Display Success or Error Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success custom-alert" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <h1 class="text-center mb-4">Data Kultawar</h1>
        <a href="input.php" class="btn btn-primary mb-3">Tambah Data</a>
        <button class="btn btn-danger mb-3" onclick="window.location.href='generate_pdf.php'">Cetak PDF</button>
        <input type="text" id="liveSearch" class="form-control" placeholder="Cari Data..." style="width: 300px;">

        <div class="mb-3">
            <!-- Pagination Controls -->
            <form method="GET" class="d-flex align-items-center">
                <label for="perPage" class="me-2">Tampilkan:</label>
                <select name="per_page" id="perPage" class="form-select me-3" style="width: auto;" onchange="this.form.submit()">
                    <option value="5" <?= $records_per_page == 5 ? 'selected' : '' ?>>5</option>
                    <option value="10" <?= $records_per_page == 10 ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= $records_per_page == 20 ? 'selected' : '' ?>>20</option>
                </select>
                <input type="hidden" name="page" value="1">
            </form>
        </div>

        <!-- Data Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kelompok</th>
                    <th>Hari</th>
                    <th>Jam Kuliah</th>
                    <th>Ruang</th>
                    <th>ID Matkul</th>
                    <th>NPP</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['idkultawar'] ?></td>
                            <td><?= htmlspecialchars($row['klp']) ?></td>
                            <td><?= htmlspecialchars($row['hari']) ?></td>
                            <td><?= htmlspecialchars($row['jamkul']) ?></td>
                            <td><?= htmlspecialchars($row['ruang']) ?></td>
                            <td><?= htmlspecialchars($row['idmatkul']) ?></td>
                            <td><?= htmlspecialchars($row['npp']) ?></td>
                            <td>
                                <button class="btn btn-warning btn-edit"
                                    data-id="<?= $row['idkultawar'] ?>"
                                    data-klp="<?= htmlspecialchars($row['klp']) ?>"
                                    data-hari="<?= htmlspecialchars($row['hari']) ?>"
                                    data-jamkul="<?= htmlspecialchars($row['jamkul']) ?>"
                                    data-ruang="<?= htmlspecialchars($row['ruang']) ?>"
                                    data-idmatkul="<?= htmlspecialchars($row['idmatkul']) ?>"
                                    data-npp="<?= htmlspecialchars($row['npp']) ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    Edit
                                </button>
                                <form action="proses.php" method="POST" class="d-inline">
                                    <input type="hidden" name="idkultawar" value="<?= $row['idkultawar'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada data tersedia</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination Links -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $current_page_num <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page_num - 1 ?>&per_page=<?= $records_per_page ?>">Sebelumnya</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $current_page_num ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&per_page=<?= $records_per_page ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $current_page_num >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page_num + 1 ?>&per_page=<?= $records_per_page ?>">Berikutnya</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Modal for Editing -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Data Kultawar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="proses.php">
                        <input type="hidden" name="idkultawar" id="editId">
                        <input type="hidden" name="action" value="edit">
                        <div class="mb-3">
                            <label for="editKlp" class="form-label">Kelompok</label>
                            <input type="text" class="form-control" id="editKlp" name="klp" required>
                        </div>
                        <div class="mb-3">
                            <label for="editHari" class="form-label">Hari</label>
                            <input type="text" class="form-control" id="editHari" name="hari" required>
                        </div>
                        <div class="mb-3">
                            <label for="editJamkul" class="form-label">Jam Kuliah</label>
                            <input type="text" class="form-control" id="editJamkul" name="jamkul" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRuang" class="form-label">Ruang</label>
                            <input type="text" class="form-control" id="editRuang" name="ruang" required>
                        </div>
                        <div class="mb-3">
                            <label for="editIdmatkul" class="form-label">ID Matkul</label>
                            <input type="text" class="form-control" id="editIdmatkul" name="idmatkul" required>
                        </div>
                        <div class="mb-3">
                            <label for="editNpp" class="form-label">NPP</label>
                            <input type="text" class="form-control" id="editNpp" name="npp" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Modal Handling -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript for Live Search -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#liveSearch").on("input", function() {
                const query = $(this).val();

                $.ajax({
                    url: "search.php",
                    method: "GET",
                    data: {
                        search: query
                    },
                    dataType: "json",
                    success: function(data) {
                        const tbody = $("table tbody");
                        tbody.empty();

                        if (data.length === 0) {
                            tbody.append("<tr><td colspan='8' class='text-center'>Tidak ada data ditemukan</td></tr>");
                        } else {
                            data.forEach(row => {
                                tbody.append(`
                                    <tr>
                                        <td>${row.idkultawar}</td>
                                        <td>${row.klp}</td>
                                        <td>${row.hari}</td>
                                        <td>${row.jamkul}</td>
                                        <td>${row.ruang}</td>
                                        <td>${row.idmatkul}</td>
                                        <td>${row.npp}</td>
                                        <td>
                                            <button class="btn btn-warning btn-edit"
                                                data-id="${row.idkultawar}"
                                                data-klp="${row.klp}"
                                                data-hari="${row.hari}"
                                                data-jamkul="${row.jamkul}"
                                                data-ruang="${row.ruang}"
                                                data-idmatkul="${row.idmatkul}"
                                                data-npp="${row.npp}"
                                                data-bs-toggle="modal" data-bs-target="#editModal">
                                                Edit
                                            </button>
                                            <form action="proses.php" method="POST" class="d-inline">
                                                <input type="hidden" name="idkultawar" value="${row.idkultawar}">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                    },
                    error: function() {
                        alert("Terjadi kesalahan pada server.");
                    }
                });
            });
        });
    </script>

    <script>
        const editButtons = document.querySelectorAll('.btn-edit');
        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('editId').value = button.dataset.id;
                document.getElementById('editKlp').value = button.dataset.klp;
                document.getElementById('editHari').value = button.dataset.hari;
                document.getElementById('editJamkul').value = button.dataset.jamkul;
                document.getElementById('editRuang').value = button.dataset.ruang;
                document.getElementById('editIdmatkul').value = button.dataset.idmatkul;
                document.getElementById('editNpp').value = button.dataset.npp;
            });
        });

        // Show alert for 3 seconds
        const alertBox = document.querySelector('.custom-alert');
        if (alertBox) {
            alertBox.style.display = 'block';
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 3000);
        }
    </script>
</body>

</html>