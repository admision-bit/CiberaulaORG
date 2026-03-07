<?php
declare(strict_types=1);
const BACKUP_KEY = 'CIBERAULA_BACKUP_2026';
const BACKUP_DIR = __DIR__ . '/.backups_mcp/pages';
const INDEX_FILE = BACKUP_DIR . '/index.json';
const PAGE_IDS   = [1, 2, 3, 4, 5];
const DB_HOST = 'localhost';
const DB_NAME = 'moodle.ciberaula_org';
const DB_USER = 'ciberaulaorg';
const DB_PASS = 'Ciberaula2026Moodle';

header('Content-Type: text/plain; charset=utf-8');
$key    = $_GET['key']    ?? '';
$action = $_GET['action'] ?? 'snapshot';
$snapId = $_GET['id']     ?? '';
if (!hash_equals(BACKUP_KEY, $key)) { http_response_code(403); exit("ERROR 403\n"); }

function db_connect(): PDO {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
function read_index(): array {
    if (!file_exists(INDEX_FILE)) return [];
    $raw = file_get_contents(INDEX_FILE);
    if ($raw === false) throw new RuntimeException("No se puede leer indice.");
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException("Indice corrupto: " . json_last_error_msg());
    return $data;
}
function write_index(array $index): void {
    $json = json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $tmp  = INDEX_FILE . '.tmp.' . getmypid();
    if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException("No se puede escribir tmp indice.");
    if (!rename($tmp, INDEX_FILE)) { @unlink($tmp); throw new RuntimeException("No se puede renombrar indice."); }
}
function ensure_dir(string $p): void {
    if (!is_dir($p) && !mkdir($p, 0755, true) && !is_dir($p))
        throw new RuntimeException("No se puede crear directorio: $p");
}
function write_atomic(string $path, string $content): void {
    $tmp = $path . '.tmp.' . getmypid();
    if (file_put_contents($tmp, $content, LOCK_EX) === false) throw new RuntimeException("Escritura fallida: $tmp");
    if (!rename($tmp, $path)) { @unlink($tmp); throw new RuntimeException("Rename fallido: $path"); }
}

function action_snapshot(): void {
    $snapId  = date('Ymd_His');
    $snapDir = BACKUP_DIR . '/' . $snapId;
    echo "=== BACKUP PAGINAS ESTATICAS ===\n";
    echo "Snapshot : $snapId\n\n";
    ensure_dir(BACKUP_DIR);
    ensure_dir($snapDir);
    $pdo = db_connect();
    echo "BD       : OK\n";
    $ids  = implode(',', PAGE_IDS);
    $rows = $pdo->query(
        "SELECT id,name,intro,introformat,content,contentformat,
                legacyfiles,legacyfileslast,display,displayoptions,
                revision,timemodified
         FROM mdl_page WHERE id IN ($ids) ORDER BY id"
    )->fetchAll();
    if (!$rows) throw new RuntimeException("Sin resultados para IDs: $ids");
    echo "Paginas  : " . count($rows) . "\n\n";
    $manifest = [
        'snapshot_id' => $snapId, 'created_at' => date('Y-m-d H:i:s'),
        'created_ts' => time(), 'db' => DB_NAME, 'pages_found' => count($rows), 'pages' => []
    ];
    foreach ($rows as $row) {
        $pid  = (int)$row['id'];
        $file = "page_{$pid}.json";
        $path = $snapDir . '/' . $file;
        $json = json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) throw new RuntimeException("JSON error pag $pid");
        write_atomic($path, $json);
        $cMd5 = md5($row['content'] ?? '');
        $fMd5 = md5_file($path);
        $manifest['pages'][$pid] = [
            'id' => $pid, 'name' => $row['name'], 'filename' => $file,
            'content_bytes' => strlen($row['content'] ?? ''),
            'content_md5' => $cMd5, 'file_md5' => $fMd5,
            'timemodified' => (int)$row['timemodified'], 'revision' => (int)$row['revision']
        ];
        $kb = round(strlen($row['content'] ?? '') / 1024, 1);
        echo "  OK page_{$pid} \"{$row['name']}\" ({$kb}KB) md5:{$cMd5}\n";
    }
    write_atomic($snapDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $index = read_index();
    $index[$snapId] = [
        'snapshot_id' => $snapId, 'created_at' => $manifest['created_at'],
        'pages_found' => $manifest['pages_found'],
        'page_names'  => array_column($manifest['pages'], 'name', 'id')
    ];
    krsort($index);
    write_index($index);
    echo "\nOK — .backups_mcp/pages/$snapId/\n";
}

function action_list(): void {
    echo "=== SNAPSHOTS ===\n\n";
    $index = read_index();
    if (empty($index)) { echo "Sin snapshots. Usa ?action=snapshot\n"; return; }
    foreach ($index as $sid => $m) {
        echo "* {$m['snapshot_id']}  {$m['created_at']}  ({$m['pages_found']} pags)\n";
        foreach ($m['page_names'] as $id => $name) echo "    [$id] $name\n";
        echo "\n";
    }
    echo "Total: " . count($index) . " snapshot(s)\n";
    echo "Restaurar: page_restore.php?key=CIBERAULA_RESTORE_2026&snapshot=ID&page_id=N\n";
}

function action_verify(string $snapId): void {
    echo "=== VERIFICAR: $snapId ===\n\n";
    if (!preg_match('/^\d{8}_\d{6}$/', $snapId))
        throw new InvalidArgumentException("ID invalido: $snapId");
    $snapDir = BACKUP_DIR . '/' . $snapId;
    $mPath   = $snapDir . '/manifest.json';
    if (!is_dir($snapDir))      throw new RuntimeException("Directorio no existe: $snapDir");
    if (!file_exists($mPath))   throw new RuntimeException("Manifest no existe: $mPath");
    $m = json_decode(file_get_contents($mPath), true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException("Manifest corrupto");
    echo "Creado  : {$m['created_at']}\n\n";
    $ok = true;
    foreach ($m['pages'] as $pid => $meta) {
        $fp = $snapDir . '/' . $meta['filename'];
        if (!file_exists($fp)) { echo "  FAIL page_{$pid} — ARCHIVO NO ENCONTRADO\n"; $ok = false; continue; }
        $row       = json_decode(file_get_contents($fp), true);
        $fOk       = hash_equals($meta['file_md5'],    md5_file($fp));
        $cOk       = hash_equals($meta['content_md5'], md5($row['content'] ?? ''));
        if ($fOk && $cOk) {
            echo "  OK   page_{$pid} \"{$meta['name']}\" — integridad CORRECTA\n";
        } else {
            $issues = [];
            if (!$fOk) $issues[] = 'archivo';
            if (!$cOk) $issues[] = 'content';
            echo "  FAIL page_{$pid} \"{$meta['name']}\" — CHECKSUM FALLIDO [" . implode(',', $issues) . "]\n";
            $ok = false;
        }
    }
    echo "\n" . ($ok ? "OK — snapshot integro.\n" : "ADVERTENCIA — integridad comprometida.\n");
}

try {
    match($action) {
        'snapshot' => action_snapshot(),
        'list'     => action_list(),
        'verify'   => $snapId ? action_verify($snapId) : throw new InvalidArgumentException("Requiere &id="),
        default    => throw new InvalidArgumentException("Accion desconocida: $action"),
    };
} catch (Throwable $e) {
    http_response_code(500);
    echo "\nERROR: " . $e->getMessage() . "\n";
    exit(1);
}