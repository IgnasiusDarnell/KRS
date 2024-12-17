<?php
require_once '../conn.php';

function respondWithJson($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'load':
            $query = $_POST['query'] ?? '';
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;
            $offset = ($page - 1) * $limit;

            $stmt = $conn->prepare("SELECT * FROM matkul WHERE namamatkul LIKE ? OR idmatkul LIKE ? ORDER BY idmatkul ASC LIMIT ?, ?");
            $search = "%$query%";
            $stmt->bind_param("ssii", $search, $search, $offset, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM matkul WHERE namamatkul LIKE ? OR idmatkul LIKE ?");
            $stmt->bind_param("ss", $search, $search);
            $stmt->execute();
            $totalResult = $stmt->get_result()->fetch_assoc();
            $totalRecords = $totalResult['total'];
            $totalPages = ceil($totalRecords / $limit);

            respondWithJson(['data' => $data, 'totalPages' => $totalPages, 'totalRecords' => $totalRecords]);
            break;

        case 'check_idmatkul':
            $idmatkul = $_POST['idmatkul'] ?? '';
            $stmt = $conn->prepare("SELECT idmatkul FROM matkul WHERE idmatkul = ?");
            $stmt->bind_param("s", $idmatkul);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            respondWithJson(['exists' => $exists]);
            break;

        case 'save':
            $idmatkul = $_POST['idmatkul'] ?? '';
            $namamatkul = $_POST['namamatkul'] ?? '';
            $sks = $_POST['sks'] ?? 0;
            $jns = $_POST['jns'] ?? '';
            $smt = $_POST['smt'] ?? 0;
            $is_edit = $_POST['is_edit'] === 'true'; // Determine if edit mode

            // Validation
            if (empty($idmatkul) || empty($namamatkul) || $sks <= 0 || empty($jns) || $smt <= 0) {
                respondWithJson(['error' => 'Semua field harus diisi dengan benar.'], 400);
            }

            if ($is_edit) { // Update existing record
                $stmt = $conn->prepare("UPDATE matkul SET namamatkul = ?, sks = ?, jns = ?, smt = ? WHERE idmatkul = ?");
                $stmt->bind_param("siiss", $namamatkul, $sks, $jns, $smt, $idmatkul);
            } else { // Insert new record
                $stmt = $conn->prepare("INSERT INTO matkul (idmatkul, namamatkul, sks, jns, smt) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiis", $idmatkul, $namamatkul, $sks, $jns, $smt);
            }

            if ($stmt->execute()) {
                respondWithJson(['message' => 'Data berhasil disimpan.']);
            } else {
                respondWithJson(['error' => 'Gagal menyimpan data: ' . $stmt->error], 500);
            }
            break;


        case 'get_data':
            $idmatkul = $_POST['idmatkul'] ?? '';
            $stmt = $conn->prepare("SELECT * FROM matkul WHERE idmatkul = ?");
            $stmt->bind_param("s", $idmatkul);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result) {
                respondWithJson($result);
            } else {
                respondWithJson(['error' => 'Data not found.'], 404);
            }
            break;

        case 'delete':
            $idmatkul = $_POST['idmatkul'] ?? '';
            $stmt = $conn->prepare("DELETE FROM matkul WHERE idmatkul = ?");
            $stmt->bind_param("s", $idmatkul);

            if ($stmt->execute()) {
                respondWithJson(['message' => 'Data berhasil dihapus!']);
            } else {
                respondWithJson(['error' => 'Gagal menghapus data: ' . $stmt->error], 500);
            }
            break;

        default:
            respondWithJson(['error' => 'Aksi tidak valid!'], 400);
            break;
    }
}

$conn->close();
