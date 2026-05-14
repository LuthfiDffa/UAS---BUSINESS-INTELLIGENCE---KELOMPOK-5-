<?php
// ============================================================
// api/rencana_pom.php — REST API untuk Rencana Pembangunan POM Listrik
// Endpoint: /api/rencana_pom.php
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

// Pastikan tabel ada (auto-create jika belum)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS rencana_pom (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        nama_lokasi     VARCHAR(150)   NOT NULL,
        latitude        DECIMAL(10,7)  NOT NULL,
        longitude       DECIMAL(10,7)  NOT NULL,
        kota            VARCHAR(100)   NOT NULL DEFAULT '',
        kecamatan       VARCHAR(100)   NOT NULL DEFAULT '',
        kapasitas_kw    INT            NOT NULL DEFAULT 0     COMMENT 'Total kapasitas daya (kW)',
        jumlah_slot     INT            NOT NULL DEFAULT 1     COMMENT 'Jumlah slot pengisian',
        tipe_pengisian  VARCHAR(50)    NOT NULL DEFAULT 'AC'  COMMENT 'AC / DC / AC+DC',
        estimasi_biaya  BIGINT         NOT NULL DEFAULT 0     COMMENT 'Estimasi biaya (Rupiah)',
        target_tahun    YEAR           NOT NULL,
        status          ENUM('Direncanakan','Dalam Proses','Selesai') NOT NULL DEFAULT 'Direncanakan',
        catatan         TEXT,
        dibuat_pada     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
        diperbarui_pada TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── Route ────────────────────────────────────────────────────
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ── GET: daftar semua / satu rencana ──────────────────────
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM rencana_pom WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            jsonOut(['success' => true, 'data' => $row]);
        }

        $page    = max(1, (int)($_GET['page']     ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;
        $status  = trim($_GET['status'] ?? '');
        $search  = trim($_GET['search'] ?? '');

        $where  = [];
        $params = [];

        if ($status !== '') {
            $where[]  = "status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $where[]  = "(nama_lokasi LIKE ? OR kota LIKE ? OR kecamatan LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM rencana_pom $whereSQL");
        $stmtC->execute($params);
        $total = (int)$stmtC->fetchColumn();

        $stmtD = $pdo->prepare("SELECT * FROM rencana_pom $whereSQL ORDER BY dibuat_pada DESC LIMIT $perPage OFFSET $offset");
        $stmtD->execute($params);
        $rows = $stmtD->fetchAll();

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

    // ── POST: tambah rencana baru ──────────────────────────
    case 'POST':
        $b = body();
        $required = ['nama_lokasi', 'latitude', 'longitude', 'target_tahun'];
        foreach ($required as $f) {
            if (!isset($b[$f]) || (string)$b[$f] === '') {
                jsonOut(['success' => false, 'message' => "Field '$f' wajib diisi"], 422);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO rencana_pom
                (nama_lokasi, latitude, longitude, kota, kecamatan,
                 kapasitas_kw, jumlah_slot, tipe_pengisian, estimasi_biaya,
                 target_tahun, status, catatan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($b['nama_lokasi']),
            (float)$b['latitude'],
            (float)$b['longitude'],
            trim($b['kota']          ?? ''),
            trim($b['kecamatan']     ?? ''),
            (int)($b['kapasitas_kw'] ?? 0),
            (int)($b['jumlah_slot']  ?? 1),
            trim($b['tipe_pengisian'] ?? 'AC'),
            (int)($b['estimasi_biaya'] ?? 0),
            (int)$b['target_tahun'],
            trim($b['status'] ?? 'Direncanakan'),
            trim($b['catatan'] ?? ''),
        ]);
        $newId = $pdo->lastInsertId();

        $new = $pdo->prepare("SELECT * FROM rencana_pom WHERE id = ?");
        $new->execute([$newId]);
        jsonOut(['success' => true, 'message' => 'Rencana POM berhasil disimpan', 'data' => $new->fetch()], 201);

    // ── PUT: update rencana ──────────────────────────────
    case 'PUT':
        if (!$id) jsonOut(['success' => false, 'message' => 'ID wajib disertakan'], 400);
        $b = body();

        $check = $pdo->prepare("SELECT * FROM rencana_pom WHERE id = ?");
        $check->execute([$id]);
        $existing = $check->fetch();
        if (!$existing) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

        $stmt = $pdo->prepare("
            UPDATE rencana_pom
            SET nama_lokasi=?, latitude=?, longitude=?, kota=?, kecamatan=?,
                kapasitas_kw=?, jumlah_slot=?, tipe_pengisian=?, estimasi_biaya=?,
                target_tahun=?, status=?, catatan=?
            WHERE id=?
        ");
        $stmt->execute([
            trim($b['nama_lokasi']    ?? $existing['nama_lokasi']),
            (float)($b['latitude']    ?? $existing['latitude']),
            (float)($b['longitude']   ?? $existing['longitude']),
            trim($b['kota']           ?? $existing['kota']),
            trim($b['kecamatan']      ?? $existing['kecamatan']),
            (int)($b['kapasitas_kw']  ?? $existing['kapasitas_kw']),
            (int)($b['jumlah_slot']   ?? $existing['jumlah_slot']),
            trim($b['tipe_pengisian'] ?? $existing['tipe_pengisian']),
            (int)($b['estimasi_biaya'] ?? $existing['estimasi_biaya']),
            (int)($b['target_tahun']  ?? $existing['target_tahun']),
            trim($b['status']         ?? $existing['status']),
            trim($b['catatan']        ?? $existing['catatan']),
            $id,
        ]);

        $upd = $pdo->prepare("SELECT * FROM rencana_pom WHERE id = ?");
        $upd->execute([$id]);
        jsonOut(['success' => true, 'message' => 'Rencana berhasil diperbarui', 'data' => $upd->fetch()]);

    // ── DELETE: hapus rencana ────────────────────────────
    case 'DELETE':
        if (!$id) jsonOut(['success' => false, 'message' => 'ID wajib disertakan'], 400);

        $check = $pdo->prepare("SELECT nama_lokasi FROM rencana_pom WHERE id = ?");
        $check->execute([$id]);
        $row = $check->fetch();
        if (!$row) jsonOut(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

        $del = $pdo->prepare("DELETE FROM rencana_pom WHERE id = ?");
        $del->execute([$id]);
        jsonOut(['success' => true, 'message' => "Rencana '{$row['nama_lokasi']}' berhasil dihapus"]);

    default:
        jsonOut(['success' => false, 'message' => 'Method tidak didukung'], 405);
}
