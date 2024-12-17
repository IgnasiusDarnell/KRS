<?php
session_start();
require '../conn.php';
$conn = getDbConnection();

$current_page = 'Kultawar ' . basename($_SERVER['PHP_SELF']);


// Run the SQL query to fetch data
$sql = "SELECT * FROM kultawar";
$result = $conn->query($sql);

// Cek jika ada pesan dalam session
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Kultawar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../menu.css">
</head>
<?php
require "../menu.html";
?>

<body>
    <div class="container mt-5">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h1 class="text-center mb-4">Data Kultawar</h1>
        <a href="input.php" class="btn btn-primary mb-3">Tambah Data</a>
        <button class="btn btn-danger mb-3" onclick="window.location.href='generate_pdf.php'">Cetak PDF</button>

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
                            <td><?= $row['klp'] ?></td>
                            <td><?= $row['hari'] ?></td>
                            <td><?= $row['jamkul'] ?></td>
                            <td><?= $row['ruang'] ?></td>
                            <td><?= $row['idmatkul'] ?></td>
                            <td><?= $row['npp'] ?></td>
                            <td>
                                <!-- Tombol Edit -->
                                <button
                                    class="btn btn-warning btn-edit"
                                    data-id="<?= $row['idkultawar'] ?>"
                                    data-klp="<?= $row['klp'] ?>"
                                    data-hari="<?= $row['hari'] ?>"
                                    data-jamkul="<?= $row['jamkul'] ?>"
                                    data-ruang="<?= $row['ruang'] ?>"
                                    data-idmatkul="<?= $row['idmatkul'] ?>"
                                    data-npp="<?= $row['npp'] ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal">
                                    Edit
                                </button>

                                <!-- Tombol Hapus -->
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
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="proses.php" method="POST" id="form-edit">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Data Kultawar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="idkultawar" id="edit-idkultawar">
                        <input type="hidden" name="action" value="edit">
                        <div class="mb-3">
                            <label for="edit-klp" class="form-label">Kelompok</label>
                            <input type="text" name="klp" id="edit-klp" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-hari">Hari</label>
                            <select id="edit-hari" name="hari" class="form-control" required>
                                <option value="">Pilih Hari</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-jamkul">Jam Kuliah</label>
                            <select id="edit-jamkul" name="jamkul" class="form-control" required>
                                <option value="">Pilih Jam</option>
                                <?php
                                for ($hour = 8; $hour <= 18; $hour++) {
                                    $start = sprintf('%02d:00', $hour);
                                    $end = sprintf('%02d:00', $hour + 2);
                                    echo "<option value='{$start}-{$end}'>{$start} - {$end}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-ruang" class="form-label">Ruang</label>
                            <input type="text" name="ruang" id="edit-ruang" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-idmatkul" class="form-label">ID Matkul</label>
                            <input type="text" name="idmatkul" id="edit-idmatkul" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-npp" class="form-label">NPP</label>
                            <input type="text" name="npp" id="edit-npp" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Isi data di form modal saat tombol edit diklik
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit-idkultawar').value = this.dataset.id;
                document.getElementById('edit-klp').value = this.dataset.klp;

                const editHari = document.getElementById('edit-hari');
                editHari.value = this.dataset.hari;

                const editJamkul = document.getElementById('edit-jamkul');
                editJamkul.value = this.dataset.jamkul;

                document.getElementById('edit-ruang').value = this.dataset.ruang;
                document.getElementById('edit-idmatkul').value = this.dataset.idmatkul;
                document.getElementById('edit-npp').value = this.dataset.npp;
            });
        });
    </script>

</body>

</html>