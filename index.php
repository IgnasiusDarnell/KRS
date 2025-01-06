<?php
require_once 'conn.php';
startSecureSession();
requireLogin();
$current_page = 'Home ' . basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'];

// Fetch data counts
$conn = getDbConnection();

// Query for Dosen
$queryDosen = "SELECT COUNT(*) as total FROM dosen";
$resultDosen = $conn->query($queryDosen);
if (!$resultDosen) {
    die("Error in Dosen query: " . $conn->error);
}
$totalDosen = $resultDosen->fetch_assoc()['total'];

// Query for Mahasiswa
$queryMahasiswa = "SELECT COUNT(*) as total FROM mhs";
$resultMahasiswa = $conn->query($queryMahasiswa);
if (!$resultMahasiswa) {
    die("Error in Mahasiswa query: " . $conn->error);
}
$totalMahasiswa = $resultMahasiswa->fetch_assoc()['total'];

// Query for Matkul
$queryMatkul = "SELECT COUNT(*) as total FROM matkul";
$resultMatkul = $conn->query($queryMatkul);
if (!$resultMatkul) {
    die("Error in Matkul query: " . $conn->error);
}
$totalMatkul = $resultMatkul->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - KRS Udinus</title>
    <!-- CSS Dependencies -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="menu.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Custom Styles for Home Page */
        .welcome-section {
            background: linear-gradient(135deg, #007bff, #00bfff);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            font-size: 1.2rem;
            margin-bottom: 0;
        }

        .data-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .data-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .data-card h3 {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 1rem;
        }

        .data-card p {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0;
        }

        .clock-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 2rem;
        }

        .clock-section h3 {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 1rem;
        }

        .clock-section #clock {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <?php require "menu.html"; ?>

    <div class="container mt-5">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Selamat Datang, <?= htmlspecialchars($username) ?>!</h1>
            <p>Sistem KRS Udinus - Kelola Data dengan Mudah</p>
        </div>

        <!-- Clock Section -->
        <div class="clock-section">
            <h3>Waktu Sekarang</h3>
            <div id="clock">Loading...</div>
        </div>

        <!-- Data Cards Section -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="data-card">
                    <h3>Dosen</h3>
                    <p><?= $totalDosen ?></p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="data-card">
                    <h3>Mahasiswa</h3>
                    <p><?= $totalMahasiswa ?></p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="data-card">
                    <h3>Mata Kuliah</h3>
                    <p><?= $totalMatkul ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Real-Time Clock
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const clock = `${hours}:${minutes}:${seconds}`;
            document.getElementById('clock').textContent = clock;
        }

        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial call
    </script>
</body>
</html>