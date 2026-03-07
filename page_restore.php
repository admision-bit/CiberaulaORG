<?php
declare(strict_types=1);
const RESTORE_KEY = 'CIBERAULA_RESTORE_2026';
const BACKUP_DIR  = __DIR__ . '/.backups_mcp/pages';
const INDEX_FILE  = BACKUP_DIR . '/index.json';
const AUDIT_FILE  = BACKUP_DIR . '/audit_log.jsonl';

const DB_HOST = 'localhost';
const DB_NAME = 'moodle.ciberaula_org';
const DB_USER = 'ciberaulaorg';
const DB_PASS = 'Ciberaula2026Moodle';

header('Content-Type: text/plain; charset=utf-8');
$key      = $_GET['key']      ?? '';
$snapshot = $_GET['snapshot'] ?? '';
$pageId   = (int)($_GET['page_id'] ?? 0);
$confirm  = $_GET['confirm']  ?? '';

if (!hash_equals(RESTORE_KEY, $key)) { http_response_code(403); exit("ERROR 403\n"); }

function db_connect(): PDO {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function append_audit(array $entry): void {
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(AUDIT_FILE, $line, FILE_APPEND | LOCK_EX);
}

try {
    // Validaciones de entrada
    if (!preg_match('/^\d{8}_\d{6}$/', $snapshot))
        throw new InvalidArgumentException("Parametro snapshot invalido: '$snapshot'");
    if ($pageId < 1 || $pageId > 5)
        throw new InvalidArgumentException("page_id debe ser 1-5. Recibido: $pageId");

    $snapDir  = BACKUP_DIR . '/' . $snapshot;
    $filepath = $snapDir . "/page_{$pageId}.json";
    $mPath    = $snapDir . '/manifest.json';

    if (!is_dir($snapDir))      throw new RuntimeException("Snapshot no existe: $snapshot");
    if (!file_exists($mPath))   throw new RuntimeException("Manifest no existe en snapshot.");
    if (!file_exists($filepath)) throw new RuntimeException("Archivo no encontrado: page_{$pageId}.json en $snapshot");

    // Leer y validar datos del backup
    $manifest = json_decode(file_get_contents($mPath), true);
    if (!isset($manifest['pages'][$pageId]))
        throw new RuntimeException("Pagina $pageId no registrada en manifest.");

    $meta = $manifest['pages'][$pageId];
    $row  = json_decode(file_get_contents($filepath), true);
    if (json_last_error() !== JSON_ERROR_NONE)
        throw new RuntimeException("Archivo de backup corrupto: " . json_last_error_msg());

    // Verificar integridad antes de restaurar
    $actualFileMd5    = md5_file($filepath);
    $actualContentMd5 = md5($row['content'] ?? '');
    $fileOk    = hash_equals($meta['file_md5'],    $actualFileMd5);
    $contentOk = hash_equals($meta['content_md5'], $actualContentMd5);

    if (!$fileOk || !$contentOk) {
        $issues = [];
        if (!$fileOk)    $issues[] = 'archivo modificado';
        if (!$contentOk) $issues[] = 'content modificado';
        throw new RuntimeException(
            "INTEGRIDAD COMPROMETIDA: " . implode(', ', $issues) .
            ". No se puede restaurar un backup con checksums incorrectos."
        );
    }

    // Sin confirm= → modo preview (nunca restaura sin confirmacion explicita)
    if ($confirm !== 'SI') {
        echo "=== PREVIEW RESTAURACION (sin cambios) ===\n\n";
        echo "Snapshot    : $snapshot ({$manifest['created_at']})\n";
        echo "Pagina      : $pageId — \"{$meta['name']}\"\n";
        echo "Tamano      : " . round($meta['content_bytes']/1024, 1) . " KB\n";
        echo "MD5 content : {$meta['content_md5']} — OK\n";
        echo "Timemodified: " . date('Y-m-d H:i:s', $meta['timemodified']) . "\n\n";

        // Leer estado actual de BD para comparar
        $pdo     = db_connect();
        $current = $pdo->query("SELECT name, CHAR_LENGTH(content) as bytes, timemodified FROM mdl_page WHERE id = $pageId")->fetch();
        if ($current) {
            echo "Estado BD actual:\n";
            echo "  Nombre : {$current['name']}\n";
            echo "  Tamano : " . round($current['bytes']/1024, 1) . " KB\n";
            echo "  Modificado : " . date('Y-m-d H:i:s', (int)$current['timemodified']) . "\n\n";
        }

        echo "PARA RESTAURAR, anade &confirm=SI a la URL.\n";
        echo "Ejemplo: page_restore.php?key=CIBERAULA_RESTORE_2026&snapshot=$snapshot&page_id=$pageId&confirm=SI\n";
        exit(0);
    }

    // ── RESTAURACION REAL ──────────────────────────────────────────────────
    echo "=== RESTAURANDO PAGINA $pageId ===\n\n";
    echo "Snapshot : $snapshot ({$manifest['created_at']})\n";
    echo "Pagina   : \"{$meta['name']}\"\n\n";

    $pdo = db_connect();

    // Guardar estado actual en audit log antes de sobrescribir
    $currentRow = $pdo->query(
        "SELECT id, name, content, timemodified, revision FROM mdl_page WHERE id = $pageId"
    )->fetch();

    append_audit([
        'ts'          => date('Y-m-d H:i:s'),
        'action'      => 'restore',
        'page_id'     => $pageId,
        'snapshot'    => $snapshot,
        'prev_name'   => $currentRow['name'] ?? null,
        'prev_bytes'  => strlen($currentRow['content'] ?? ''),
        'prev_tmod'   => $currentRow['timemodified'] ?? null,
        'new_bytes'   => $meta['content_bytes'],
        'new_md5'     => $meta['content_md5'],
        'ip'          => $_SERVER['REMOTE_ADDR'] ?? 'cli',
    ]);

    // UPDATE atomico — solo actualiza content y timemodified
    // No toca otros campos de configuracion de Moodle
    $stmt = $pdo->prepare(
        "UPDATE mdl_page SET content = :content, timemodified = :ts WHERE id = :id"
    );
    $stmt->execute([
        ':content' => $row['content'],
        ':ts'      => time(),
        ':id'      => $pageId,
    ]);

    $affected = $stmt->rowCount();
    if ($affected !== 1) {
        throw new RuntimeException("UPDATE afecto $affected filas (esperado: 1). Verifica el estado de la BD.");
    }

    // Purgar cache de Moodle (MUC)
    $purgeUrl = 'https://www.ciberaula.org/_purge_all.php';
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
    @file_get_contents($purgeUrl, false, $ctx);

    echo "UPDATE    : OK ($affected fila afectada)\n";
    echo "Auditoria : registrada en audit_log.jsonl\n";
    echo "Cache     : purge solicitado\n\n";
    echo "OK — Pagina $pageId restaurada desde snapshot $snapshot\n";
    echo "Verifica en: https://www.ciberaula.org/?noredirect=migra2026\n";

} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}