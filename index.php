<?php 
require 'db_config.php'; 
// Ambil data terbaru dan data sebelumnya untuk perbandingan (naik/turun)
$res = $conn->query("SELECT * FROM gold_history ORDER BY id DESC LIMIT 2");
$data = $res->fetch_assoc(); // Data terbaru
$prev = $res->fetch_assoc(); // Data kemarin

$price = $data['price_buy'];
$res_rule = $conn->query("SELECT * FROM gold_decision_matrix WHERE $price BETWEEN price_min AND price_max LIMIT 1");
$rule = $res_rule->fetch_assoc();

// Logic warna: Jika harga naik dibanding kemarin = Hijau, jika turun = Merah
$is_up = ($prev) ? ($data['price_buy'] >= $prev['price_buy']) : true;
$color_class = $is_up ? 'text-success' : 'text-danger';
$indicator = $is_up ? '▲' : '▼';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Gold Tracker - Analysis</title>
</head>
<body class="bg-light p-4">
    <div class="container bg-white p-4 shadow rounded">
        <h2 class="mb-4 text-secondary">Gold Tracker Dashboard</h2>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase opacity-75">Zona Saat Ini</h6>
                        <h3 class="fw-bold"><?= $rule['zone_name'] ?></h3>
                        <p class="h1"><?= $rule['action_recommend'] ?></p>
                        <small>Alokasi: <?= $rule['allocation_pct'] ?>% dari dana nganggur</small>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-muted text-uppercase">Harga Beli Live</h6>
                        <p class="display-6 fw-bold <?= $color_class ?>">
                            <?= $indicator ?> Rp <?= number_format($data['price_buy']) ?>
                        </p>
                        <p class="text-secondary">Net Jual: <strong>Rp <?= number_format($data['price_sell']) ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr><th>Tanggal</th><th>Harga Beli</th><th>Harga Jual (Net)</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php
                $res_list = $conn->query("SELECT * FROM gold_history ORDER BY id DESC LIMIT 10");
                while($row = $res_list->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['created_at'] ?></td>
                    <td class="fw-bold">Rp <?= number_format($row['price_buy']) ?></td>
                    <td class="text-muted">Rp <?= number_format($row['price_sell']) ?></td>
                    <td><?= $row['is_ath'] ? '<span class="badge bg-warning text-dark">ATH 🔥</span>' : '<span class="badge bg-secondary">Normal</span>' ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
