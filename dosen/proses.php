<?php
require_once '../conn.php';

// Helper Functions
function respondWithJson($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validateNPP($npp) {
    return preg_match('/^\d{4}\.\d{2}\.\d{4}\.\d{3}$/', $npp);
}

function validateHomebase($homebase) {
    return preg_match('/^[A-Z]\d{2}$/', $homebase);
}

// Database Connection
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'load':
                $query = $_POST['query'] ?? '';
                $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
                $limit = isset($_POST['limit']) ? max(5, min(50, (int)$_POST['limit'])) : 10;
                $offset = ($page - 1) * $limit;
                
                $search = "%$query%";
                
                // Get total records for pagination
                $stmt_total = $conn->prepare(
                    "SELECT COUNT(*) as total 
                     FROM dosen 
                     WHERE namadosen LIKE ? OR npp LIKE ? OR homebase LIKE ?"
                );
                $stmt_total->bind_param("sss", $search, $search, $search);
                $stmt_total->execute();
                $totalRecords = $stmt_total->get_result()->fetch_assoc()['total'];
                $totalPages = ceil($totalRecords / $limit);
                
                // Get paginated data
                $stmt = $conn->prepare(
                    "SELECT * FROM dosen 
                     WHERE namadosen LIKE ? OR npp LIKE ? OR homebase LIKE ? 
                     ORDER BY npp DESC 
                     LIMIT ?, ?"
                );
                $stmt->bind_param("sssii", $search, $search, $search, $offset, $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = [];
                
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                
                respondWithJson([
                    'data' => $data,
                    'pagination' => [
                        'totalPages' => $totalPages,
                        'currentPage' => $page,
                        'totalRecords' => $totalRecords,
                        'recordsPerPage' => $limit
                    ]
                ]);
                break;

            case 'check_npp':
                $npp = $_POST['npp'] ?? '';
                $current_npp = $_POST['current_npp'] ?? '';
                
                $sql = "SELECT npp FROM dosen WHERE npp = ?";
                $params = [$npp];
                
                if ($current_npp) {
                    $sql .= " AND npp != ?";
                    $params[] = $current_npp;
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                $stmt->execute();
                
                respondWithJson(['exists' => $stmt->get_result()->num_rows > 0]);
                break;

            case 'save':
                $id = $_POST['id'] ?? ''; // Hidden ID field for current NPP
                $npp = trim($_POST['npp'] ?? '');
                $namadosen = trim($_POST['namadosen'] ?? '');
                $homebase = trim($_POST['homebase'] ?? '');
                
                // Validation
                if (empty($namadosen)) {
                    respondWithJson(['error' => 'Nama dosen tidak boleh kosong!'], 400);
                }
                
                if (!validateHomebase($homebase)) {
                    respondWithJson(['error' => 'Format homebase tidak valid! Contoh: A12'], 400);
                }
                
                if (empty($id)) {
                    // New record
                    if (!validateNPP($npp)) {
                        respondWithJson(['error' => 'NPP harus dalam format 0686.11.1993.003!'], 400);
                    }
                    
                    // Check if NPP exists
                    $stmt = $conn->prepare("SELECT npp FROM dosen WHERE npp = ?");
                    $stmt->bind_param("s", $npp);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        respondWithJson(['error' => 'NPP sudah terdaftar!'], 400);
                    }
                    
                    // Insert new record
                    $stmt = $conn->prepare("INSERT INTO dosen (npp, namadosen, homebase) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $npp, $namadosen, $homebase);
                } else {
                    // Update existing record
                    // Ensure NPP is not changed during update
                    $stmt = $conn->prepare("UPDATE dosen SET namadosen=?, homebase=? WHERE npp=?");
                    $stmt->bind_param("sss", $namadosen, $homebase, $id);
                }
                if ($stmt->execute()) {
                    respondWithJson([
                        'message' => 'Data berhasil ' . (empty($id) ? 'ditambahkan!' : 'diperbarui!'),
                        'npp' => $npp ?: $id
                    ]);
                } else {
                    respondWithJson(['error' => 'Gagal menyimpan data: ' . $conn->error], 500);
                }
                break;

            case 'get_data':
                $npp = $_POST['npp'] ?? '';
                if (!$npp) {
                    respondWithJson(['error' => 'NPP tidak valid!'], 400);
                }
                
                $stmt = $conn->prepare("SELECT * FROM dosen WHERE npp=?");
                $stmt->bind_param("s", $npp);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    respondWithJson($row);
                } else {
                    respondWithJson(['error' => 'Data tidak ditemukan!'], 404);
                }
                break;

            case 'delete':
                $npp = $_POST['npp'] ?? '';
                if (!$npp) {
                    respondWithJson(['error' => 'NPP tidak valid!'], 400);
                }
                
                $stmt = $conn->prepare("DELETE FROM dosen WHERE npp=?");
                $stmt->bind_param("s", $npp);
                
                if ($stmt->execute()) {
                    respondWithJson(['message' => 'Data berhasil dihapus!']);
                } else {
                    respondWithJson(['error' => 'Gagal menghapus data: ' . $conn->error], 500);
                }
                break;

            default:
                respondWithJson(['error' => 'Aksi tidak valid!'], 400);
        }
    } catch (Exception $e) {
        respondWithJson(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
    }
}

$conn->close();