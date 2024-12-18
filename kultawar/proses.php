<?php
session_start();
require '../conn.php';
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $response = [];

    try {
        if ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE kultawar SET klp = ?, hari = ?, jamkul = ?, ruang = ?, idmatkul = ?, npp = ? WHERE idkultawar = ?");
            $stmt->bind_param("ssssssi", $_POST['klp'], $_POST['hari'], $_POST['jamkul'], $_POST['ruang'], $_POST['idmatkul'], $_POST['npp'], $_POST['idkultawar']);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Data berhasil diperbarui.";
            } else {
                $_SESSION['message'] = "Gagal memperbarui data.";
            }
            header('Location: index.php');
            exit();
            // $stmt->close();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM kultawar WHERE idkultawar = ?");
            $stmt->bind_param("i", $_POST['idkultawar']);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Data berhasil dihapus.";
            } else {
                $_SESSION['message'] = "Gagal menghapus data.";
            }
            header('Location: index.php');
            exit();
            // $stmt->close();
        } elseif ($action == 'add') {
            $klp = $_POST['klp'];
            $hari = $_POST['hari'];
            $jamkul = $_POST['jamkul'];
            $ruang = $_POST['ruang'];
            $idmatkul = $_POST['idmatkul'];
            $npp = $_POST['npp'];

            $sql = "INSERT INTO kultawar (klp, hari, jamkul, ruang, idmatkul, npp) VALUES ('$klp', '$hari', '$jamkul', '$ruang', '$idmatkul', '$npp')";
            if ($conn->query($sql) === TRUE) {
                $response['message'] = "Data berhasil ditambahkan.";
            } else {
                $response['error'] = "Gagal menambahkan data: " . $conn->error;
            }
        }
    } catch (Exception $e) {
        $response['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
