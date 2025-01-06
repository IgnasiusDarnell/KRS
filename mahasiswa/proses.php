<?php
require_once '../conn.php';

// Configuration
$target_dir = "../photo/";
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Helper Functions
function respondWithJson($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function handleFileUpload($file) {
    global $target_dir, $allowed_types, $max_file_size;
    
    if (empty($file['name'])) return '';
    
    // Validate file size
    if ($file['size'] > $max_file_size) {
        respondWithJson(['error' => 'Ukuran file terlalu besar! Maksimal 5MB.'], 400);
    }
    
    // Validate file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        respondWithJson(['error' => 'Format file tidak valid! Hanya menerima jpg, jpeg, png, dan gif.'], 400);
    }
    
    // Generate unique filename
    $new_file_name = uniqid('img_') . '.' . $file_ext;
    $target_file = $target_dir . $new_file_name;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        respondWithJson(['error' => 'Gagal mengunggah file! Silakan coba lagi.'], 500);
    }
    
    return $new_file_name;
}

function validateNim($nim) {
    return preg_match('/^[A-Za-z0-9]{3}\.[0-9]{4}\.[0-9]{5}$/', $nim);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function deleteOldPhoto($conn, $id) {
    $stmt = $conn->prepare("SELECT foto FROM mhs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['foto'] && file_exists($GLOBALS['target_dir'] . $row['foto'])) {
            unlink($GLOBALS['target_dir'] . $row['foto']);
        }
    }
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
                     FROM mhs 
                     WHERE nama LIKE ? OR nim LIKE ?"
                );
                $stmt_total->bind_param("ss", $search, $search);
                $stmt_total->execute();
                $totalRecords = $stmt_total->get_result()->fetch_assoc()['total'];
                $totalPages = ceil($totalRecords / $limit);
                
                // Get paginated data
                $stmt = $conn->prepare(
                    "SELECT * FROM mhs 
                     WHERE nama LIKE ? OR nim LIKE ? 
                     ORDER BY id DESC 
                     LIMIT ?, ?"
                );
                $stmt->bind_param("ssii", $search, $search, $offset, $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = [];
                
                while ($row = $result->fetch_assoc()) {
                    // Add photo URL to each row
                    if ($row['foto']) {
                        $row['foto_url'] = "../photo/" . $row['foto'];
                    }
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

            case 'check_nim':
                $nim = $_POST['nim'] ?? '';
                $id = $_POST['id'] ?? '';
                
                $sql = "SELECT id FROM mhs WHERE nim = ?";
                $params = [$nim];
                
                if ($id) {
                    $sql .= " AND id != ?";
                    $params[] = $id;
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                $stmt->execute();
                
                respondWithJson(['exists' => $stmt->get_result()->num_rows > 0]);
                break;

            case 'save':
                $id = $_POST['id'] ?? '';
                $nim = $_POST['nim'] ?? '';
                $nama = trim($_POST['nama'] ?? '');
                $email = trim($_POST['email'] ?? '');
                
                // Validation
                if (empty($nama)) {
                    respondWithJson(['error' => 'Nama tidak boleh kosong!'], 400);
                }
                
                if (!validateEmail($email)) {
                    respondWithJson(['error' => 'Format email tidak valid!'], 400);
                }
                
                if (empty($id)) {
                    // New record
                    if (!validateNim($nim)) {
                        respondWithJson(['error' => 'NIM harus dalam format A12.2022.06905!'], 400);
                    }
                    
                    // Check if NIM exists
                    $stmt = $conn->prepare("SELECT id FROM mhs WHERE nim = ?");
                    $stmt->bind_param("s", $nim);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        respondWithJson(['error' => 'NIM sudah terdaftar!'], 400);
                    }
                    
                    // Handle file upload for new record
                    $new_filename = isset($_FILES['foto']) ? handleFileUpload($_FILES['foto']) : '';
                    
                    // Insert new record
                    $stmt = $conn->prepare("INSERT INTO mhs (nama, nim, email, foto) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $nama, $nim, $email, $new_filename);
                } else {
                    // Update existing record
                    $update_foto = isset($_FILES['foto']) && !empty($_FILES['foto']['name']);
                    
                    if ($update_foto) {
                        $new_filename = handleFileUpload($_FILES['foto']);
                        deleteOldPhoto($conn, $id);
                        
                        $stmt = $conn->prepare("UPDATE mhs SET nama=?, email=?, foto=? WHERE id=?");
                        $stmt->bind_param("sssi", $nama, $email, $new_filename, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE mhs SET nama=?, email=? WHERE id=?");
                        $stmt->bind_param("ssi", $nama, $email, $id);
                    }
                }
                
                if ($stmt->execute()) {
                    respondWithJson([
                        'message' => 'Data berhasil ' . (empty($id) ? 'ditambahkan!' : 'diperbarui!'),
                        'id' => $id ?: $conn->insert_id
                    ]);
                } else {
                    respondWithJson(['error' => 'Gagal menyimpan data: ' . $conn->error], 500);
                }
                break;

            case 'get_data':
                $id = $_POST['id'] ?? '';
                if (!$id) {
                    respondWithJson(['error' => 'ID tidak valid!'], 400);
                }
                
                $stmt = $conn->prepare("SELECT * FROM mhs WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    if ($row['foto']) {
                        $row['foto_path'] = "../photo/" . $row['foto'];
                    }
                    respondWithJson($row);
                } else {
                    respondWithJson(['error' => 'Data tidak ditemukan!'], 404);
                }
                break;

            case 'delete':
                $id = $_POST['id'] ?? '';
                if (!$id) {
                    respondWithJson(['error' => 'ID tidak valid!'], 400);
                }
                
                // Delete photo file first
                deleteOldPhoto($conn, $id);
                
                // Delete database record
                $stmt = $conn->prepare("DELETE FROM mhs WHERE id=?");
                $stmt->bind_param("i", $id);
                
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