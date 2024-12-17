<?php
session_start();
require '../conn.php';
$conn = getDbConnection();

// Cek apakah request menggunakan POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action == 'edit') {
        $id = $_POST['idkultawar'];
        $klp = $_POST['klp'];
        $hari = $_POST['hari'];
        $jamkul = $_POST['jamkul'];
        $ruang = $_POST['ruang'];
        $idmatkul = $_POST['idmatkul'];
        $npp = $_POST['npp'];

        $sql = "UPDATE kultawar SET klp='$klp', hari='$hari', jamkul='$jamkul', ruang='$ruang', idmatkul='$idmatkul', npp='$npp' WHERE idkultawar='$id'";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Data berhasil diperbarui.";
        } else {
            $_SESSION['message'] = "Gagal memperbarui data: " . $conn->error;
        }
        header("Location: index.php");
        exit();
    } elseif ($action == 'delete') {
        $id = $_POST['idkultawar'];
        $sql = "DELETE FROM kultawar WHERE idkultawar='$id'";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Data berhasil dihapus.";
        } else {
            $_SESSION['message'] = "Gagal menghapus data: " . $conn->error;
        }
        header("Location: index.php");
        exit();
    } elseif ($action == 'add') {
        $klp = $_POST['klp'];
        $hari = $_POST['hari'];
        $jamkul = $_POST['jamkul'];
        $ruang = $_POST['ruang'];
        $idmatkul = $_POST['idmatkul'];
        $npp = $_POST['npp'];

        $sql = "INSERT INTO kultawar (klp, hari, jamkul, ruang, idmatkul, npp) VALUES ('$klp', '$hari', '$jamkul', '$ruang', '$idmatkul', '$npp')";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Data berhasil ditambahkan.";
        } else {
            $_SESSION['message'] = "Gagal menambahkan data: " . $conn->error;
        }
        header("Location: index.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Metode request tidak valid.";
    header("Location: index.php");
    exit();
}
?>
