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

$configs = [];
$res_config = $conn->query("SELECT * FROM gold_config");
while($row = $res_config->fetch_assoc()) $configs[$row['config_key']] = $row['config_value'];

// Fungsi untuk menentukan warna berdasarkan action
function getActionColor($action) {
    switch ($action) {
        case 'ALL-IN': return 'bg-danger';      // Merah menyala
        case 'CICIL': return 'bg-warning text-dark'; // Kuning
        case 'SELL': return 'bg-success';      // Hijau
        default: return 'bg-secondary';        // Abu-abu
    }
}
$action_color = getActionColor($rule['action_recommend']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../bs/bootstrap.min.css" rel="stylesheet">
    <title>Gold Tracker - Analysis</title>
</head>
<body class="bg-light p-4">
    <div class="container bg-white p-4 shadow rounded">
<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-secondary">
            <strong>Info Analis:</strong> 
            Target TP: Rp <?= number_format($configs['target_tp']) ?> | 
            Support: Rp <?= number_format($configs['support_level']) ?> | 
            Min RRR: <?= $configs['rrr_min'] ?>x
        </div>
    </div>
</div>
        <h2 class="mb-4 text-secondary">Gold Tracker Dashboard</h2>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
    <div class="card-body">
        <h6 class="card-title text-uppercase opacity-75">Zona Saat Ini</h6>
        <h3 class="fw-bold"><?= $rule['zone_name'] ?></h3>
        
        <span class="badge <?= $action_color ?> p-2 h2"><?= $rule['action_recommend'] ?></span>
        
        <p class="mt-2">Alokasi: <strong><?= $rule['allocation_pct'] ?>%</strong> dari dana</p>
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
<div class="card mt-4 p-3">
    <canvas id="goldChart" height="100"></canvas>
</div>

<?php
// Ambil data buat chart (10 hari terakhir)
$res_chart = $conn->query("SELECT created_at, price_buy FROM gold_history ORDER BY id ASC LIMIT 10");
$labels = []; $values = [];
while($row = $res_chart->fetch_assoc()) {
    $labels[] = $row['created_at'];
    $values[] = $row['price_buy'];
}
?>

<script>
const ctx = document.getElementById('goldChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Harga Emas (IDR)',
            data: <?= json_encode($values) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true }
});
</script>
    </div>

</body>
</html>
