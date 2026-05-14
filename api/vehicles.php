<?php
// ============================================================
// api/vehicles.php — REST API Kendaraan
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

function jsonOut(mixed $d, int $c=200):void{ http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }
function body():array{ return json_decode(file_get_contents('php://input'),true)??[]; }

switch($method){
    case 'GET':
        if($id){
            $s=$pdo->prepare("SELECT * FROM vehicles WHERE vehicle_key=?");
            $s->execute([$id]);
            $r=$s->fetch();
            if(!$r) jsonOut(['success'=>false,'message'=>'Tidak ditemukan'],404);
            jsonOut(['success'=>true,'data'=>$r]);
        }
        $rows=$pdo->query("SELECT * FROM vehicles ORDER BY make, model")->fetchAll();
        jsonOut(['success'=>true,'data'=>$rows]);

    case 'POST':
        $b=body();
        if(empty($b['make'])||empty($b['model'])) jsonOut(['success'=>false,'message'=>'make dan model wajib'],422);
        $pdo->prepare("INSERT INTO vehicles (make,model) VALUES(?,?)")->execute([strtoupper(trim($b['make'])),trim($b['model'])]);
        $new=$pdo->prepare("SELECT * FROM vehicles WHERE vehicle_key=?");
        $new->execute([$pdo->lastInsertId()]);
        jsonOut(['success'=>true,'message'=>'Kendaraan ditambahkan','data'=>$new->fetch()],201);

    case 'PUT':
        if(!$id) jsonOut(['success'=>false,'message'=>'ID wajib'],400);
        $b=body();
        $chk=$pdo->prepare("SELECT * FROM vehicles WHERE vehicle_key=?");
        $chk->execute([$id]);
        $ex=$chk->fetch();
        if(!$ex) jsonOut(['success'=>false,'message'=>'Tidak ditemukan'],404);
        $make =strtoupper(trim($b['make']  ??$ex['make']));
        $model=trim($b['model']??$ex['model']);
        $pdo->prepare("UPDATE vehicles SET make=?,model=? WHERE vehicle_key=?")->execute([$make,$model,$id]);
        jsonOut(['success'=>true,'message'=>'Kendaraan diperbarui','data'=>['vehicle_key'=>$id,'make'=>$make,'model'=>$model]]);

    case 'DELETE':
        if(!$id) jsonOut(['success'=>false,'message'=>'ID wajib'],400);
        $chk=$pdo->prepare("SELECT make,model FROM vehicles WHERE vehicle_key=?");
        $chk->execute([$id]);
        $r=$chk->fetch();
        if(!$r) jsonOut(['success'=>false,'message'=>'Tidak ditemukan'],404);
        $used=$pdo->prepare("SELECT COUNT(*) FROM ev_facts WHERE vehicle_key=?");
        $used->execute([$id]);
        if((int)$used->fetchColumn()>0) jsonOut(['success'=>false,'message'=>"Kendaraan masih digunakan di data EV"],409);
        $pdo->prepare("DELETE FROM vehicles WHERE vehicle_key=?")->execute([$id]);
        jsonOut(['success'=>true,'message'=>"Kendaraan {$r['make']} {$r['model']} dihapus"]);

    default:
        jsonOut(['success'=>false,'message'=>'Method tidak didukung'],405);
}
