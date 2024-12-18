<?php
require '../conn.php';
$conn = getDbConnection();

$search = isset($_GET['search']) ? $_GET['search'] : '';
$search = '%' . $conn->real_escape_string($search) . '%';

$sql = "SELECT * FROM kultawar 
        WHERE klp LIKE ? OR hari LIKE ? OR ruang LIKE ? OR idmatkul LIKE ? OR npp LIKE ?
        ORDER BY idkultawar ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $search, $search, $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
