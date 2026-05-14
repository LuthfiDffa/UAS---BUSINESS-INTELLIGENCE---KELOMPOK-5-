<?php
// ============================================================
// api/stats.php — Statistik untuk Dashboard
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

function jsonOut(mixed $d, int $c=200):void{ http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }

$totalUnits     = $pdo->query("SELECT COALESCE(SUM(total_units),0) FROM ev_facts")->fetchColumn();
$totalCities    = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
$totalVehicles  = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$avgRange       = $pdo->query("SELECT COALESCE(ROUND(AVG(avg_electric_range),1),0) FROM ev_facts")->fetchColumn();

// Per EV Type
$evTypeSplit = $pdo->query("
    SELECT t.ev_type, COALESCE(SUM(f.total_units),0) as units
    FROM ev_types t LEFT JOIN ev_facts f ON t.ev_type_key=f.ev_type_key
    GROUP BY t.ev_type
")->fetchAll();

// Top 5 Kota
$topCities = $pdo->query("
    SELECT l.city, l.county, COALESCE(SUM(f.total_units),0) as total
    FROM locations l LEFT JOIN ev_facts f ON l.location_key=f.location_key
    GROUP BY l.location_key, l.city, l.county
    ORDER BY total DESC LIMIT 5
")->fetchAll();

// Per Tahun
$byYear = $pdo->query("
    SELECT model_year, COALESCE(SUM(total_units),0) as units
    FROM ev_facts
    GROUP BY model_year ORDER BY model_year
")->fetchAll();

// Per Merek
$byMake = $pdo->query("
    SELECT v.make, COALESCE(SUM(f.total_units),0) as units
    FROM vehicles v LEFT JOIN ev_facts f ON v.vehicle_key=f.vehicle_key
    GROUP BY v.make ORDER BY units DESC LIMIT 10
")->fetchAll();

// Per County
$byCounty = $pdo->query("
    SELECT l.county, COALESCE(SUM(f.total_units),0) as units
    FROM locations l LEFT JOIN ev_facts f ON l.location_key=f.location_key
    GROUP BY l.county ORDER BY units DESC LIMIT 8
")->fetchAll();

// Total lokasi per county
$locPerCounty = $pdo->query("
    SELECT county, COUNT(*) as total FROM locations GROUP BY county ORDER BY total DESC
")->fetchAll();

jsonOut([
    'success' => true,
    'data'    => [
        'total_units'     => (int)$totalUnits,
        'total_cities'    => (int)$totalCities,
        'total_vehicles'  => (int)$totalVehicles,
        'avg_range'       => (float)$avgRange,
        'ev_type_split'   => $evTypeSplit,
        'top_cities'      => $topCities,
        'by_year'         => $byYear,
        'by_make'         => $byMake,
        'by_county'       => $byCounty,
        'loc_per_county'  => $locPerCounty,
    ],
]);
