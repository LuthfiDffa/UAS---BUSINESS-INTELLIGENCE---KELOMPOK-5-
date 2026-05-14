<?php
// ============================================================
// api/dss.php — DSS / Sistem Pendukung Keputusan SPKLU
// Weighted Scoring Model berdasarkan data MySQL
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';

function jsonOut(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$pdo = getDB();

// ── Filter params ──────────────────────────────────────────
$filterLevel = trim($_GET['level']  ?? '');   // Tinggi / Sedang / Rendah
$sortBy      = trim($_GET['sort']   ?? 'score'); // score | units | county
$evTypeF     = trim($_GET['ev_type'] ?? '');  // BEV | PHEV

// ── Query: agregasi per kota ────────────────────────────────
$whereExtra = '';
$params     = [];
if ($evTypeF !== '') {
    $whereExtra = 'AND t.ev_type = ?';
    $params[]   = $evTypeF;
}

$sql = "
    SELECT
        l.location_key,
        l.city,
        l.county,
        l.latitude,
        l.longitude,
        COALESCE(SUM(f.total_units), 0)                         AS total_units,
        COALESCE(AVG(f.avg_electric_range), 0)                  AS avg_range,
        COALESCE(GROUP_CONCAT(DISTINCT v.make ORDER BY v.make), '') AS all_makes,
        COUNT(DISTINCT f.fact_id)                               AS record_count,
        SUM(CASE WHEN t.ev_type = 'BEV' THEN f.total_units ELSE 0 END)  AS bev_units,
        SUM(CASE WHEN t.ev_type = 'PHEV' THEN f.total_units ELSE 0 END) AS phev_units
    FROM locations l
    LEFT JOIN ev_facts f  ON l.location_key = f.location_key
    LEFT JOIN vehicles v  ON f.vehicle_key  = v.vehicle_key
    LEFT JOIN ev_types t  ON f.ev_type_key  = t.ev_type_key
    WHERE 1=1 $whereExtra
    GROUP BY l.location_key, l.city, l.county, l.latitude, l.longitude
    HAVING total_units > 0
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    jsonOut(['success' => true, 'data' => [], 'meta' => ['total' => 0]]);
}

// ── Weighted Scoring ───────────────────────────────────────
// Bobot: Densitas EV 50% + Avg Range 25% + Tipe EV 25%
$maxUnits = max(array_column($rows, 'total_units'));
$maxRange = max(array_column($rows, 'avg_range'));

$result = [];
foreach ($rows as $r) {
    $units = (float)$r['total_units'];
    $range = (float)$r['avg_range'];
    $bev   = (float)$r['bev_units'];
    $phev  = (float)$r['phev_units'];

    // Densitas EV score (0-50)
    $densityScore = $maxUnits > 0 ? ($units / $maxUnits) * 50 : 0;

    // Avg Range score (0-25)
    $rangeScore   = $maxRange > 0 ? ($range / $maxRange) * 25 : 0;

    // Tipe EV score: BEV lebih tinggi bobot (0-25)
    $totalTyped   = $bev + $phev;
    $bevRatio     = $totalTyped > 0 ? $bev / $totalTyped : 0;
    $typeScore    = $bevRatio * 25;

    $score = round($densityScore + $rangeScore + $typeScore, 2);

    // Tentukan level prioritas
    if ($score >= 60)      $level = 'Tinggi';
    elseif ($score >= 25)  $level = 'Sedang';
    else                   $level = 'Rendah';

    // Merek dominan
    $makes = $r['all_makes'] ? explode(',', $r['all_makes']) : [];
    // Cari merek dominan dari subquery
    $dominant = !empty($makes) ? $makes[0] : 'N/A';

    $result[] = [
        'location_key' => (int)$r['location_key'],
        'city'         => $r['city'],
        'county'       => $r['county'],
        'latitude'     => (float)$r['latitude'],
        'longitude'    => (float)$r['longitude'],
        'total_units'  => (int)$units,
        'avg_range'    => round($range, 1),
        'bev_units'    => (int)$bev,
        'phev_units'   => (int)$phev,
        'bev_ratio'    => round($bevRatio * 100, 1),
        'score'        => $score,
        'level'        => $level,
        'dominant_make'=> $dominant,
        'density_score'=> round($densityScore, 2),
        'range_score'  => round($rangeScore, 2),
        'type_score'   => round($typeScore, 2),
        'record_count' => (int)$r['record_count'],
    ];
}

// ── Filter level ──────────────────────────────────────────
if ($filterLevel !== '' && in_array($filterLevel, ['Tinggi','Sedang','Rendah'])) {
    $result = array_values(array_filter($result, fn($r) => $r['level'] === $filterLevel));
}

// ── Sort ──────────────────────────────────────────────────
usort($result, function($a, $b) use ($sortBy) {
    return match($sortBy) {
        'units'  => $b['total_units'] <=> $a['total_units'],
        'county' => strcmp($a['county'], $b['county']),
        default  => $b['score'] <=> $a['score'],
    };
});

// ── Hitung ringkasan ──────────────────────────────────────
$countTinggi = count(array_filter($result, fn($r) => $r['level'] === 'Tinggi'));
$countSedang = count(array_filter($result, fn($r) => $r['level'] === 'Sedang'));
$countRendah = count(array_filter($result, fn($r) => $r['level'] === 'Rendah'));

jsonOut([
    'success' => true,
    'data'    => $result,
    'meta'    => [
        'total'          => count($result),
        'count_tinggi'   => $countTinggi,
        'count_sedang'   => $countSedang,
        'count_rendah'   => $countRendah,
        'filter_level'   => $filterLevel,
        'sort_by'        => $sortBy,
    ],
]);
