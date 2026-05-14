<?php
// ============================================================
// api/ev_facts.php — REST API untuk Tabel EV Facts
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

function jsonOut(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Base SELECT with JOINs
$baseSelect = "
    SELECT
        f.fact_id, f.total_units, f.avg_electric_range, f.model_year,
        f.vehicle_key, v.make, v.model,
        f.location_key, l.city, l.county, l.state, l.postal_code, l.latitude, l.longitude,
        f.ev_type_key, t.ev_type, t.full_name AS ev_type_full,
        f.created_at, f.updated_at
    FROM ev_facts f
    JOIN vehicles  v ON f.vehicle_key   = v.vehicle_key
    JOIN locations l ON f.location_key  = l.location_key
    JOIN ev_types  t ON f.ev_type_key   = t.ev_type_key
";

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("$baseSelect WHERE f.fact_id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            jsonOut(['success' => true, 'data' => $row]);
        }

        $search   = trim($_GET['search']    ?? '');
        $city     = trim($_GET['city']      ?? '');
        $evType   = trim($_GET['ev_type']   ?? '');
        $year     = trim($_GET['year']      ?? '');
        $make     = trim($_GET['make']      ?? '');
        $page     = max(1,  (int)($_GET['page']     ?? 1));
        $perPage  = max(1, min(100, (int)($_GET['per_page'] ?? 15)));
        $offset   = ($page - 1) * $perPage;
        $sort     = in_array($_GET['sort'] ?? '', ['city','make','total_units','model_year','avg_electric_range'])
                    ? $_GET['sort'] : 'f.fact_id';
        $dir      = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]  = "(l.city LIKE ? OR v.make LIKE ? OR v.model LIKE ?)";
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
        }
        if ($city    !== '') { $where[] = "l.city = ?";      $params[] = $city; }
        if ($evType  !== '') { $where[] = "t.ev_type = ?";   $params[] = $evType; }
        if ($year    !== '') { $where[] = "f.model_year = ?"; $params[] = $year; }
        if ($make    !== '') { $where[] = "v.make = ?";      $params[] = $make; }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSQL = "SELECT COUNT(*) FROM ev_facts f JOIN vehicles v ON f.vehicle_key=v.vehicle_key JOIN locations l ON f.location_key=l.location_key JOIN ev_types t ON f.ev_type_key=t.ev_type_key $whereSQL";
        $stmtC    = $pdo->prepare($countSQL);
        $stmtC->execute($params);
        $total    = (int)$stmtC->fetchColumn();

        $dataSQL  = "$baseSelect $whereSQL ORDER BY $sort $dir LIMIT $perPage OFFSET $offset";
        $stmtD    = $pdo->prepare($dataSQL);
        $stmtD->execute($params);
        $rows     = $stmtD->fetchAll();

        jsonOut([
            'success'    => true,
            'data'       => $rows,
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int)ceil($total / $perPage),
            ],
        ]);

    case 'POST':
        $b = body();
        $required = ['vehicle_key','location_key','ev_type_key','total_units','avg_electric_range','model_year'];
        foreach ($required as $f) {
            if (!isset($b[$f]) || $b[$f] === '') jsonOut(['success' => false, 'message' => "Field '$f' wajib diisi"], 422);
        }

        $stmt = $pdo->prepare("
            INSERT INTO ev_facts (vehicle_key, location_key, ev_type_key, total_units, avg_electric_range, model_year)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([
            (int)$b['vehicle_key'],
            (int)$b['location_key'],
            (int)$b['ev_type_key'],
            (int)$b['total_units'],
            (int)$b['avg_electric_range'],
            (int)$b['model_year'],
        ]);
        $newId = $pdo->lastInsertId();
        $newRow = $pdo->prepare("$baseSelect WHERE f.fact_id = ?");
        $newRow->execute([$newId]);
        jsonOut(['success' => true, 'message' => 'Data EV berhasil ditambahkan', 'data' => $newRow->fetch()], 201);

    case 'PUT':
        if (!$id) jsonOut(['success' => false, 'message' => 'ID wajib'], 400);
        $b = body();

        $check = $pdo->prepare("SELECT fact_id FROM ev_facts WHERE fact_id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

        $fields = [];
        $vals   = [];
        $allowed = ['vehicle_key','location_key','ev_type_key','total_units','avg_electric_range','model_year'];
        foreach ($allowed as $col) {
            if (isset($b[$col])) { $fields[] = "$col = ?"; $vals[] = (int)$b[$col]; }
        }
        if (!$fields) jsonOut(['success' => false, 'message' => 'Tidak ada data yang diperbarui'], 400);

        $vals[] = $id;
        $pdo->prepare("UPDATE ev_facts SET " . implode(', ', $fields) . " WHERE fact_id = ?")->execute($vals);

        $upd = $pdo->prepare("$baseSelect WHERE f.fact_id = ?");
        $upd->execute([$id]);
        jsonOut(['success' => true, 'message' => 'Data berhasil diperbarui', 'data' => $upd->fetch()]);

    case 'DELETE':
        if (!$id) jsonOut(['success' => false, 'message' => 'ID wajib'], 400);

        $check = $pdo->prepare("SELECT f.fact_id, l.city, v.make FROM ev_facts f JOIN locations l ON f.location_key=l.location_key JOIN vehicles v ON f.vehicle_key=v.vehicle_key WHERE f.fact_id=?");
        $check->execute([$id]);
        $row = $check->fetch();
        if (!$row) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

        $pdo->prepare("DELETE FROM ev_facts WHERE fact_id = ?")->execute([$id]);
        jsonOut(['success' => true, 'message' => "Data EV #{$id} ({$row['city']} – {$row['make']}) berhasil dihapus"]);

    default:
        jsonOut(['success' => false, 'message' => 'Method tidak didukung'], 405);
}
