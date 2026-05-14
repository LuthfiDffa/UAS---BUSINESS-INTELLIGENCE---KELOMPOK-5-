<?php
// ============================================================
// api/locations.php — REST API untuk Tabel Locations
// Endpoint: /api/locations.php
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

// ── Helper ──────────────────────────────────────────────────
function jsonOut(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ── Route ────────────────────────────────────────────────────
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ── GET: list semua / satu lokasi ──────────────────────
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM locations WHERE location_key = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            jsonOut(['success' => true, 'data' => $row]);
        }

        // Search + pagination
        $search  = trim($_GET['search'] ?? '');
        $county  = trim($_GET['county'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 15)));
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]  = "(city LIKE ? OR postal_code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($county !== '') {
            $where[]  = "county = ?";
            $params[] = $county;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSQL = "SELECT COUNT(*) FROM locations $whereSQL";
        $stmtC    = $pdo->prepare($countSQL);
        $stmtC->execute($params);
        $total = (int)$stmtC->fetchColumn();

        $dataSQL  = "SELECT * FROM locations $whereSQL ORDER BY county, city LIMIT $perPage OFFSET $offset";
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

    // ── POST: tambah lokasi baru ───────────────────────────
    case 'POST':
        $b = body();
        $required = ['city','county','postal_code','latitude','longitude'];
        foreach ($required as $f) {
            if (empty($b[$f])) jsonOut(['success' => false, 'message' => "Field '$f' wajib diisi"], 422);
        }

        // Cek duplikat kota
        $dup = $pdo->prepare("SELECT location_key FROM locations WHERE city = ? AND county = ?");
        $dup->execute([trim($b['city']), trim($b['county'])]);
        if ($dup->fetch()) {
            jsonOut(['success' => false, 'message' => 'Kota sudah terdaftar di county tersebut'], 409);
        }

        $stmt = $pdo->prepare("
            INSERT INTO locations (city, county, state, postal_code, latitude, longitude)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($b['city']),
            trim($b['county']),
            trim($b['state'] ?? 'WA'),
            trim($b['postal_code']),
            (float)$b['latitude'],
            (float)$b['longitude'],
        ]);
        $newId = $pdo->lastInsertId();

        $newRow = $pdo->prepare("SELECT * FROM locations WHERE location_key = ?");
        $newRow->execute([$newId]);
        jsonOut(['success' => true, 'message' => 'Lokasi berhasil ditambahkan', 'data' => $newRow->fetch()], 201);

    // ── PUT: update lokasi ────────────────────────────────
    case 'PUT':
        if (!$id) jsonOut(['success' => false, 'message' => 'ID wajib disertakan'], 400);
        $b = body();

        $check = $pdo->prepare("SELECT * FROM locations WHERE location_key = ?");
        $check->execute([$id]);
        $existing = $check->fetch();
        if (!$existing) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

        $city        = trim($b['city']        ?? $existing['city']);
        $county      = trim($b['county']      ?? $existing['county']);
        $state       = trim($b['state']       ?? $existing['state']);
        $postalCode  = trim($b['postal_code'] ?? $existing['postal_code']);
        $latitude    = isset($b['latitude'])  ? (float)$b['latitude']  : $existing['latitude'];
        $longitude   = isset($b['longitude']) ? (float)$b['longitude'] : $existing['longitude'];

        $stmt = $pdo->prepare("
            UPDATE locations
            SET city=?, county=?, state=?, postal_code=?, latitude=?, longitude=?
            WHERE location_key=?
        ");
        $stmt->execute([$city, $county, $state, $postalCode, $latitude, $longitude, $id]);

        $upd = $pdo->prepare("SELECT * FROM locations WHERE location_key = ?");
        $upd->execute([$id]);
        jsonOut(['success' => true, 'message' => 'Lokasi berhasil diperbarui', 'data' => $upd->fetch()]);

    // ── DELETE: hapus lokasi ──────────────────────────────
    case 'DELETE':
        if (!$id) jsonOut(['success' => false, 'message' => 'ID wajib disertakan'], 400);

        $check = $pdo->prepare("SELECT city FROM locations WHERE location_key = ?");
        $check->execute([$id]);
        $row = $check->fetch();
        if (!$row) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

        // Cek apakah lokasi masih dipakai di ev_facts
        $used = $pdo->prepare("SELECT COUNT(*) FROM ev_facts WHERE location_key = ?");
        $used->execute([$id]);
        if ((int)$used->fetchColumn() > 0) {
            jsonOut(['success' => false, 'message' => "Lokasi '{$row['city']}' masih digunakan di data EV Facts. Hapus data EV terkait terlebih dahulu."], 409);
        }

        $del = $pdo->prepare("DELETE FROM locations WHERE location_key = ?");
        $del->execute([$id]);
        jsonOut(['success' => true, 'message' => "Lokasi '{$row['city']}' berhasil dihapus"]);

    default:
        jsonOut(['success' => false, 'message' => 'Method tidak didukung'], 405);
}
