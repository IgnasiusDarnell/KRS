<?php
require_once '../conn.php';

$target_dir = "../photo/";
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

function respondWithJson($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function handleFileUpload($file)
{
    global $target_dir, $allowed_types;

    if (empty($file['name'])) return '';

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        respondWithJson(['error' => 'Format file tidak valid!'], 400);
    }

    $new_file_name = uniqid() . '.' . $file_ext;
    $target_file = $target_dir . $new_file_name;

    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        respondWithJson(['error' => 'Gagal mengunggah file!'], 500);
    }

    return $new_file_name;
}

function getImagePath($filename)
{
    global $target_dir;
    return $target_dir . $filename;
}

function validateNim($nim)
{
    return preg_match('/^[A-Za-z0-9]{3}\.[0-9]{4}\.[0-9]{5}$/', $nim);
}

$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'load':
            $query = $_POST['query'] ?? '';
            $stmt = $conn->prepare("SELECT * FROM mhs WHERE nama LIKE ? OR nim LIKE ? ORDER BY id DESC");
            $search = "%$query%";
            $stmt->bind_param("ss", $search, $search);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            respondWithJson($data);
            break;

        case 'save':
            $id = $_POST['id'] ?? '';
            $nim = $_POST['nim'] ?? '';
            $nama = $_POST['nama'] ?? '';
            $email = $_POST['email'] ?? '';
            $new_filename = isset($_FILES['foto']) ? handleFileUpload($_FILES['foto']) : '';

            if (!validateNim($nim)) {
                respondWithJson(['error' => 'NIM harus dalam format A12.2022.06905!'], 400);
            }

            $stmt = $conn->prepare("SELECT id FROM mhs WHERE nim = ?" . ($id ? " AND id != ?" : ""));
            if ($id) {
                $stmt->bind_param("si", $nim, $id);
            } else {
                $stmt->bind_param("s", $nim);
            }
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                respondWithJson(['error' => 'NIM sudah ada!'], 400);
            }

            if ($id) {
                $sql = "UPDATE mhs SET nama=?, nim=?, email=?";
                $params = [$nama, $nim, $email];

                if ($new_filename) {
                    $sql .= ", foto=?";
                    $params[] = $new_filename;
                }

                $sql .= " WHERE id=?";
                $params[] = $id;
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            } else {
                $stmt = $conn->prepare("INSERT INTO mhs (nama, nim, email, foto) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nama, $nim, $email, $new_filename);
            }

            if ($stmt->execute()) {
                respondWithJson(['message' => 'Data berhasil disimpan!', 'id' => $id ?: $conn->insert_id]);
            } else {
                respondWithJson(['error' => $conn->error], 500);
            }
            break;

        case 'get_data':
            $id = $_POST['id'];
            $stmt = $conn->prepare("SELECT * FROM mhs WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && $row['foto']) {
                $row['foto_path'] = getImagePath($row['foto']);
            }
            respondWithJson($row);
            break;

        case 'delete':
            $id = $_POST['id'];

            $stmt = $conn->prepare("SELECT foto FROM mhs WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $foto = $stmt->get_result()->fetch_assoc()['foto'];

            if ($foto) {
                $foto_path = getImagePath($foto);
                if (file_exists($foto_path)) {
                    unlink($foto_path);
                }
            }

            $stmt = $conn->prepare("DELETE FROM mhs WHERE id=?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                respondWithJson(['message' => 'Data berhasil dihapus!']);
            } else {
                respondWithJson(['error' => $conn->error], 500);
            }
            break;
        case 'check_nim':
            $nim = $_POST['nim'] ?? '';

            $stmt = $conn->prepare("SELECT id FROM mhs WHERE nim = ?");
            $stmt->bind_param("s", $nim);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                respondWithJson(['exists' => true]);
            } else {
                respondWithJson(['exists' => false]);
            }
            break;

        default:
            respondWithJson(['error' => 'Aksi tidak valid!'], 400);
            break;
    }
}

$conn->close();
