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

            $stmt = $conn->prepare("SELECT * FROM dosen WHERE namadosen LIKE ? OR npp LIKE ? ORDER BY npp ASC LIMIT ?, ?");
            $search = "%$query%";
            $stmt->bind_param("ssii", $search, $search, $offset, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM dosen WHERE namadosen LIKE ? OR npp LIKE ?");
            $stmt->bind_param("ss", $search, $search);
            $stmt->execute();
            $totalResult = $stmt->get_result()->fetch_assoc();
            $totalRecords = $totalResult['total'];
            $totalPages = ceil($totalRecords / $limit);

            respondWithJson(['data' => $data, 'totalPages' => $totalPages, 'totalRecords' => $totalRecords]);
            break;

        case 'check_npp':
            $npp = $_POST['npp'] ?? '';
            $stmt = $conn->prepare("SELECT npp FROM dosen WHERE npp = ?");
            $stmt->bind_param("s", $npp);
            $stmt->execute();
            respondWithJson(['exists' => $stmt->get_result()->num_rows > 0]);
            break;

        case 'save':
            $id = $_POST['id'] ?? '';
            $npp = $_POST['npp'] ?? '';
            $namadosen = $_POST['namadosen'] ?? '';
            $homebase = $_POST['homebase'] ?? '';

            // Validate npp format
            if (!preg_match('/^\d{4}\.\d{2}\.\d{4}\.\d{2}$/', $npp)) {
                respondWithJson(['error' => 'NPP harus dalam format 0686.11.1993.03'], 400);
            }

            // Check for duplicate npp only for new entries
            if (!$id) {
                $stmt = $conn->prepare("SELECT npp FROM dosen WHERE npp = ?");
                $stmt->bind_param("s", $npp);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    respondWithJson(['error' => 'NPP sudah ada!'], 400);
                }
            }

            if ($id) {
                $stmt = $conn->prepare("UPDATE dosen SET namadosen=?, homebase=? WHERE npp=?");
                $stmt->bind_param("sss", $namadosen, $homebase, $npp);
            } else {
                $stmt = $conn->prepare("INSERT INTO dosen (namadosen, npp, homebase) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $namadosen, $npp, $homebase);
            }

            if ($stmt->execute()) {
                respondWithJson(['message' => 'Data berhasil disimpan!', 'id' => $id ?: $conn->insert_id]);
            } else {
                error_log("SQL Error: " . $stmt->error);
                respondWithJson(['error' => 'Gagal menyimpan data: ' . $stmt->error], 500);
            }
            break;

        case 'get_data':
            $npp = $_POST['npp'];
            $stmt = $conn->prepare("SELECT * FROM dosen WHERE npp=?");
            $stmt->bind_param("s", $npp);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            respondWithJson($row);
            break;

        case 'delete':
            $npp = $_POST['npp'];
            $stmt = $conn->prepare("DELETE FROM dosen WHERE npp=?");
            $stmt->bind_param("s", $npp);

            if ($stmt->execute()) {
                respondWithJson(['message' => 'Data berhasil dihapus!']);
            } else {
                error_log("SQL Error: " . $stmt->error);
                respondWithJson(['error' => 'Gagal menghapus data: ' . $stmt->error], 500);
            }
            break;

        default:
            respondWithJson(['error' => 'Aksi tidak valid!'], 400);
            break;
    }
}

$conn->close();
