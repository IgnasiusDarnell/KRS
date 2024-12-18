<?php
require '../conn.php';
$conn = getDbConnection();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Input Kuliah Tawar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4">
        <h2 class="text-center">Form Input Kuliah Tawar</h2>
        <div class="card mt-4">
            <div class="card-body">
                <form id="form-kultawar">
                    <!-- Hidden Action Field -->
                    <input type="hidden" name="action" value="add">

                    <div id="message" class="alert d-none"></div>

                    <div class="form-group">
                        <label for="klp">Kelompok</label>
                        <input type="text" id="klp" name="klp" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hari">Hari</label>
                        <select id="hari" name="hari" class="form-control" required>
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
                        <label for="jamkul">Jam Kuliah</label>
                        <select id="jamkul" name="jamkul" class="form-control" required>
                            <option value="">Pilih Jam</option>
                            <?php
                            for ($hour = 8; $hour <= 18; $hour++) {
                                $start = sprintf('%02d:00', $hour);
                                $end = sprintf('%02d:00', $hour + 2);
                                echo "<option value='" . htmlspecialchars("{$start}-{$end}") . "'>" . htmlspecialchars("{$start} - {$end}") . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ruang">Ruang</label>
                        <input type="text" id="ruang" name="ruang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="idmatkul">Mata Kuliah</label>
                        <select id="idmatkul" name="idmatkul" class="form-control" required>
                            <option value="">Pilih Mata Kuliah</option>
                            <?php
                            $matkul = $conn->query("SELECT idmatkul, namamatkul FROM matkul");
                            while ($row = $matkul->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['idmatkul']) . "'>" . htmlspecialchars($row['namamatkul']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="npp">Dosen</label>
                        <select id="npp" name="npp" class="form-control" required>
                            <option value="">Pilih Dosen</option>
                            <?php
                            $dosen = $conn->query("SELECT npp, namadosen FROM dosen");
                            while ($row = $dosen->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['npp']) . "'>" . htmlspecialchars($row['namadosen']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="button" id="saveBtn" class="btn btn-success">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#saveBtn").click(function() {
                const formData = $("#form-kultawar").serialize();
                const messageBox = $("#message");

                $("#saveBtn").attr("disabled", true).text("Menyimpan...");

                $.post("proses.php", formData, function(response) {
                    if (response.error) {
                        messageBox.removeClass("d-none alert-success").addClass("alert-danger").text(response.error);
                        $("#saveBtn").attr("disabled", false).text("Simpan");
                    } else {
                        messageBox.removeClass("d-none alert-danger").addClass("alert-success").text(response.message);
                        if (response.message.includes("berhasil")) {
                            setTimeout(() => {
                                window.location.href = "index.php";
                            }, 2000);
                        } else {
                            $("#saveBtn").attr("disabled", false).text("Simpan");
                        }
                    }
                }, "json").fail(function() {
                    messageBox.removeClass("d-none alert-success").addClass("alert-danger").text("Terjadi kesalahan pada server.");
                    $("#saveBtn").attr("disabled", false).text("Simpan");
                });
            });
        });
    </script>
</body>

</html>