<?php
require 'db_config.php';
header('Content-Type: application/json');

// 1. Ambil harga emas terakhir
$res_price = $conn->query("SELECT * FROM gold_history ORDER BY id DESC LIMIT 1");
$gold = $res_price->fetch_assoc();

// 2. Ambil Rule dari Decision Matrix berdasarkan harga saat ini
$price = $gold['price_buy'];
$res_rule = $conn->query("SELECT * FROM gold_decision_matrix WHERE $price BETWEEN price_min AND price_max LIMIT 1");
$rule = $res_rule->fetch_assoc();

// 3. Logic RRR (Risk Reward Ratio)
// RRR = (Target TP - Harga Sekarang) / (Harga Sekarang - Support Terdekat)
$target_tp = 2970000;
$support = 2750000;
$rrr = ($target_tp - $price) / ($price - $support);
$worth_it = ($rrr > 1.5); // Kalau RRR > 1.5, kita anggap Worth It

// 4. Susun Response
$response = [
    "current_price" => (int)$gold['price_buy'],
    "sell_price"    => (int)$gold['price_sell'],
    "analysis" => [
        "zone" => $rule['zone_name'],
        "recommendation" => $rule['action_recommend'] . " " . $rule['allocation_pct'] . "%",
        "worth_it" => $worth_it,
        "next_all_in" => 2750000,
        "next_tp" => 2970000,
        "rrr" => round($rrr, 2)
    ],
    "btc_correlation" => "Stable - Focus on Gold"
];

echo json_encode($response);
