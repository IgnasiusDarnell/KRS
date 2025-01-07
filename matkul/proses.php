<?php
require_once '../conn.php';

// Helper Functions
function respondWithJson($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validateIdMatkul($idmatkul) {
    // Validate format: A11 or A12 followed by 5 digits
    return preg_match('/^A(11|12)\.\d{5}$/', $idmatkul);
}

function validateSKS($sks) {
    // Validate SKS is between 1 and 6
    return is_numeric($sks) && $sks >= 1 && $sks <= 6;
}

function validateJenis($jns) {
    // Validate jenis is either 'T' or 'T/P' or 'P'
    return in_array($jns, ['T', 'T/P', 'P']);
}

function validateSemester($smt) {
    // Validate semester is between 1 and 8
    return is_numeric($smt) && $smt >= 1 && $smt <= 8;
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
                     FROM matkul 
                     WHERE namamatkul LIKE ? OR idmatkul LIKE ? OR smt LIKE ?"
                );
                $stmt_total->bind_param("sss", $search, $search, $search);
                $stmt_total->execute();
                $totalRecords = $stmt_total->get_result()->fetch_assoc()['total'];
                $totalPages = ceil($totalRecords / $limit);
                
                // Get paginated data
                $stmt = $conn->prepare(
                    "SELECT * FROM matkul 
                     WHERE namamatkul LIKE ? OR idmatkul LIKE ? OR smt LIKE ? 
                     ORDER BY idmatkul ASC 
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

            case 'check_idmatkul':
                $idmatkul = $_POST['idmatkul'] ?? '';
                $current_idmatkul = $_POST['current_idmatkul'] ?? '';
                
                $sql = "SELECT idmatkul FROM matkul WHERE idmatkul = ?";
                $params = [$idmatkul];
                
                if ($current_idmatkul) {
                    $sql .= " AND idmatkul != ?";
                    $params[] = $current_idmatkul;
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                $stmt->execute();
                
                respondWithJson(['exists' => $stmt->get_result()->num_rows > 0]);
                break;

            case 'save':
                $id = $_POST['id'] ?? ''; // Hidden ID field for current idmatkul
                $idmatkul = trim($_POST['idmatkul'] ?? '');
                $namamatkul = trim($_POST['namamatkul'] ?? '');
                $sks = trim($_POST['sks'] ?? '');
                $jns = trim($_POST['jns'] ?? '');
                $smt = trim($_POST['smt'] ?? '');
                
                // Validation
                if (empty($namamatkul)) {
                    respondWithJson(['error' => 'Nama matkul tidak boleh kosong!'], 400);
                }
                
                if (!validateIdMatkul($idmatkul)) {
                    respondWithJson(['error' => 'Format ID Matkul tidak valid! Contoh: A12.56101'], 400);
                }
                
                if (!validateSKS($sks)) {
                    respondWithJson(['error' => 'SKS harus antara 1 dan 6!'], 400);
                }
                
                if (!validateJenis($jns)) {
                    respondWithJson(['error' => 'Jenis matkul harus T atau T/P!'], 400);
                }
                
                if (!validateSemester($smt)) {
                    respondWithJson(['error' => 'Semester harus antara 1 dan 8!'], 400);
                }
                
                if (empty($id)) {
                    // New record
                    $stmt = $conn->prepare("SELECT idmatkul FROM matkul WHERE idmatkul = ?");
                    $stmt->bind_param("s", $idmatkul);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        respondWithJson(['error' => 'ID Matkul sudah terdaftar!'], 400);
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO matkul (idmatkul, namamatkul, sks, jns, smt) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssiss", $idmatkul, $namamatkul, $sks, $jns, $smt);
                } else {
                    // Update existing record - use the ID field for WHERE clause
                    $stmt = $conn->prepare("UPDATE matkul SET namamatkul=?, sks=?, jns=?, smt=? WHERE idmatkul=?");
                    $stmt->bind_param("sisss", $namamatkul, $sks, $jns, $smt, $id);
                }
                
                if ($stmt->execute()) {
                    respondWithJson([
                        'message' => 'Data berhasil ' . (empty($id) ? 'ditambahkan!' : 'diperbarui!'),
                        'idmatkul' => empty($id) ? $idmatkul : $id
                    ]);
                } else {
                    respondWithJson(['error' => 'Gagal menyimpan data: ' . $conn->error], 500);
                }
                break;

            case 'get_data':
                $idmatkul = $_POST['idmatkul'] ?? '';
                if (!$idmatkul) {
                    respondWithJson(['error' => 'ID Matkul tidak valid!'], 400);
                }
                
                $stmt = $conn->prepare("SELECT * FROM matkul WHERE idmatkul=?");
                $stmt->bind_param("s", $idmatkul);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    respondWithJson($row);
                } else {
                    respondWithJson(['error' => 'Data tidak ditemukan!'], 404);
                }
                break;

            case 'delete':
                $idmatkul = $_POST['idmatkul'] ?? '';
                if (!$idmatkul) {
                    respondWithJson(['error' => 'ID Matkul tidak valid!'], 400);
                }
                
                $stmt = $conn->prepare("DELETE FROM matkul WHERE idmatkul=?");
                $stmt->bind_param("s", $idmatkul);
                
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