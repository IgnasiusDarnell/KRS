<?php
require_once '../conn.php';

// Helper Functions
function respondWithJson($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Database Connection
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'load':
                $query = sanitizeInput($_POST['query'] ?? '');
                $klp = sanitizeInput($_POST['klp'] ?? '');
                $hari = sanitizeInput($_POST['hari'] ?? '');
                $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
                $limit = isset($_POST['limit']) ? max(5, min(50, (int)$_POST['limit'])) : 10;
                $offset = ($page - 1) * $limit;

                $search = "%$query%";
                $klpFilter = $klp ? "AND k.klp = ?" : "";
                $hariFilter = $hari ? "AND k.hari = ?" : "";
                $bindParams = [$search, $search];

                if ($klp) {
                    $bindParams[] = $klp;
                }
                if ($hari) {
                    $bindParams[] = $hari;
                }

                // Get total records for pagination
                $sql_total = "SELECT COUNT(*) as total 
                              FROM kultawar k
                              JOIN matkul m ON k.idmatkul = m.idmatkul
                              JOIN dosen d ON k.npp = d.npp
                              WHERE (m.namamatkul LIKE ? OR d.namadosen LIKE ?) 
                              $klpFilter $hariFilter";
                $stmt_total = $conn->prepare($sql_total);
                $types = str_repeat('s', count($bindParams));
                $stmt_total->bind_param($types, ...$bindParams);
                $stmt_total->execute();
                $totalRecords = $stmt_total->get_result()->fetch_assoc()['total'];
                $totalPages = ceil($totalRecords / $limit);

                // Get paginated data with additional validation
                $sql = "SELECT k.*, m.namamatkul, d.namadosen, m.sks 
                        FROM kultawar k
                        JOIN matkul m ON k.idmatkul = m.idmatkul
                        JOIN dosen d ON k.npp = d.npp
                        WHERE (m.namamatkul LIKE ? OR d.namadosen LIKE ?) 
                        $klpFilter $hariFilter
                        ORDER BY k.hari ASC, k.jamkul ASC
                        LIMIT ?, ?";
                $bindParamsForData = array_merge($bindParams, [$offset, $limit]);

                $stmt = $conn->prepare($sql);
                $types = str_repeat('s', count($bindParamsForData) - 2) . 'ii';
                $stmt->bind_param($types, ...$bindParamsForData);
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

            case 'save':
                $idkultawar = sanitizeInput($_POST['idkultawar'] ?? '');
                $idmatkul = sanitizeInput($_POST['idmatkul'] ?? '');
                $npp = sanitizeInput($_POST['npp'] ?? '');
                $klp = sanitizeInput($_POST['klp'] ?? '');
                $hari = sanitizeInput($_POST['hari'] ?? '');
                $jamkul = sanitizeInput($_POST['jamkul'] ?? '');
                $ruang = sanitizeInput($_POST['ruang'] ?? '');

                // Validate required fields
                if (!$idmatkul || !$npp || !$klp || !$hari || !$jamkul || !$ruang) {
                    respondWithJson(['error' => 'Semua field harus diisi!'], 400);
                }

                // Check for schedule conflicts
                $conflictSQL = "SELECT k.*, m.namamatkul, d.namadosen 
                               FROM kultawar k
                               JOIN matkul m ON k.idmatkul = m.idmatkul
                               JOIN dosen d ON k.npp = d.npp
                               WHERE k.hari = ? AND k.jamkul = ? AND k.ruang = ?
                               AND k.idkultawar != ?";
                $conflictStmt = $conn->prepare($conflictSQL);
                $nullId = 0;
                $checkId = $idkultawar ?: $nullId;
                $conflictStmt->bind_param("sssi", $hari, $jamkul, $ruang, $checkId);
                $conflictStmt->execute();
                $conflictResult = $conflictStmt->get_result();

                if ($conflictResult->num_rows > 0) {
                    respondWithJson(['error' => 'Jadwal bentrok! Ruangan sudah digunakan pada waktu tersebut.'], 409);
                }

                if (empty($idkultawar)) {
                    $stmt = $conn->prepare(
                        "INSERT INTO kultawar (idmatkul, npp, klp, hari, jamkul, ruang)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("ssssss", $idmatkul, $npp, $klp, $hari, $jamkul, $ruang);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE kultawar 
                         SET idmatkul=?, npp=?, klp=?, hari=?, jamkul=?, ruang=?
                         WHERE idkultawar=?"
                    );
                    $stmt->bind_param("ssssssi", $idmatkul, $npp, $klp, $hari, $jamkul, $ruang, $idkultawar);
                }

                if ($stmt->execute()) {
                    respondWithJson([
                        'message' => 'Data berhasil disimpan!',
                        'idkultawar' => $idkultawar ?: $conn->insert_id
                    ]);
                } else {
                    respondWithJson(['error' => 'Gagal menyimpan data: ' . $conn->error], 500);
                }
                break;

            case 'delete':
                $idkultawar = sanitizeInput($_POST['idkultawar'] ?? '');
                if (!$idkultawar) {
                    respondWithJson(['error' => 'ID Kultawar tidak valid!'], 400);
                }

                $stmt = $conn->prepare("DELETE FROM kultawar WHERE idkultawar=?");
                $stmt->bind_param("i", $idkultawar);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        respondWithJson(['message' => 'Data berhasil dihapus!']);
                    } else {
                        respondWithJson(['error' => 'Data tidak ditemukan!'], 404);
                    }
                } else {
                    respondWithJson(['error' => 'Gagal menghapus data: ' . $conn->error], 500);
                }
                break;

            case 'get_data':
                $idkultawar = sanitizeInput($_POST['idkultawar'] ?? '');
                if (!$idkultawar) {
                    respondWithJson(['error' => 'ID Kultawar tidak valid!'], 400);
                }

                $stmt = $conn->prepare(
                    "SELECT k.*, m.namamatkul, m.sks, d.namadosen 
                     FROM kultawar k
                     JOIN matkul m ON k.idmatkul = m.idmatkul
                     JOIN dosen d ON k.npp = d.npp
                     WHERE k.idkultawar=?"
                );
                $stmt->bind_param("i", $idkultawar);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    respondWithJson($row);
                } else {
                    respondWithJson(['error' => 'Data tidak ditemukan!'], 404);
                }
                break;

            case 'get_matkul':
                $query = sanitizeInput($_POST['query'] ?? '');
                $searchTerm = "%$query%";
                
                $stmt = $conn->prepare(
                    "SELECT idmatkul, namamatkul, sks 
                     FROM matkul 
                     WHERE namamatkul LIKE ? 
                     ORDER BY namamatkul ASC"
                );
                $stmt->bind_param("s", $searchTerm);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = [];

                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }

                respondWithJson($data);
                break;

            case 'get_dosen':
                $query = sanitizeInput($_POST['query'] ?? '');
                $searchTerm = "%$query%";
                
                $stmt = $conn->prepare(
                    "SELECT npp, namadosen 
                     FROM dosen 
                     WHERE namadosen LIKE ? 
                     ORDER BY namadosen ASC"
                );
                $stmt->bind_param("s", $searchTerm);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = [];

                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }

                respondWithJson($data);
                break;

            default:
                respondWithJson(['error' => 'Aksi tidak valid!'], 400);
        }
    } catch (Exception $e) {
        respondWithJson(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
    }
}

$conn->close();