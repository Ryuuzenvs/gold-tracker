<?php
require 'db_config.php';
header('Content-Type: application/json');

// 1. Ambil harga terbaru
$gold = $conn->query("SELECT * FROM gold_history ORDER BY id DESC LIMIT 1")->fetch_assoc();

// 2. Ambil semua config dalam satu array
$configs = [];
$res_config = $conn->query("SELECT * FROM gold_config");
while($row = $res_config->fetch_assoc()) $configs[$row['config_key']] = $row['config_value'];

// 3. Ambil Rule Decision Matrix
$price = $gold['price_buy'];
$rule = $conn->query("SELECT * FROM gold_decision_matrix WHERE $price BETWEEN price_min AND price_max LIMIT 1")->fetch_assoc();

// 4. Kalkulasi RRR Dinamis
$target_tp = (float)$configs['target_tp'];
$support = (float)$configs['support_level'];
$rrr_min = (float)$configs['rrr_min'];

$rrr = ($target_tp - $price) / ($price - $support);
$worth_it = ($rrr > $rrr_min);

$response = [
    "current_price" => (int)$price,
    "analysis" => [
        "zone" => $rule['zone_name'],
        "recommendation" => $rule['action_recommend'] . " " . $rule['allocation_pct'] . "%",
        "worth_it" => $worth_it,
        "rrr" => round($rrr, 2)
    ]
];
echo json_encode($response);
