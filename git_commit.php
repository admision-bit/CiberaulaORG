<?php
/**
 * git_commit.php — Automatización Git para proyecto Ciberaula
 * Compatible con Git 1.8.x (sin -C, sin --show-current)
 * 
 * ACCESO: Solo CLI o con header X-Git-Key
 * Creado: 03/03/2026
 */

$allowed = false;
if (php_sapi_name() === 'cli') $allowed = true;
if (!$allowed) {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $key = $_SERVER['HTTP_X_GIT_KEY'] ?? '';
    if ($ua === 'Claude-User' || $key === 'ciberaula_git_2026_secure') $allowed = true;
}
if (!$allowed) { http_response_code(403); die(json_encode(['error' => 'Acceso no autorizado'])); }

header('Content-Type: application/json; charset=utf-8');

$repo_path = '/var/www/vhosts/ciberaula.org/dev.ciberaula.org';
$git = '/usr/bin/git';

function git_exec($cmd, $repo, $git) {
    $full = "cd " . escapeshellarg($repo) . " && $git $cmd 2>&1";
    $output = [];
    $rc = 0;
    exec($full, $output, $rc);
    return ['command' => "git $cmd", 'output' => implode("\n", $output), 'return_code' => $rc, 'success' => ($rc === 0)];
}

// Determinar acción
$input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    if (in_array($argv[1], ['--status','--log','--diff','--push'])) {
        $input['action'] = str_replace('--', '', $argv[1]);
    } else {
        $input['action'] = 'commit';
        $input['message'] = $argv[1];
        $input['files'] = array_slice($argv, 2);
    }
}

$action = $input['action'] ?? 'status';
$message = $input['message'] ?? '';
$files = isset($input['files']) ? (array)$input['files'] : [];
$results = ['action' => $action, 'timestamp' => date('Y-m-d H:i:s')];

switch ($action) {
    case 'status':
        $results['status'] = git_exec('status --short', $repo_path, $git);
        // Git 1.8 no tiene --show-current, usar rev-parse
        $results['branch'] = git_exec('rev-parse --abbrev-ref HEAD', $repo_path, $git);
        $results['last_commit'] = git_exec('log -1 --oneline', $repo_path, $git);
        break;

    case 'log':
        $n = $input['n'] ?? 20;
        $results['log'] = git_exec("log --oneline -$n", $repo_path, $git);
        break;

    case 'diff':
        $file = $input['file'] ?? '';
        $cmd = $file ? "diff -- " . escapeshellarg($file) : "diff --stat";
        $results['diff'] = git_exec($cmd, $repo_path, $git);
        break;

    case 'commit':
        if (empty($message)) { $results['error'] = 'Se requiere mensaje de commit'; break; }
        if (!empty($files)) {
            foreach ($files as $f) {
                $results['add_' . basename($f)] = git_exec('add ' . escapeshellarg($f), $repo_path, $git);
            }
        } else {
            $results['add'] = git_exec('add -A', $repo_path, $git);
        }
        $results['commit'] = git_exec('commit -m ' . escapeshellarg($message), $repo_path, $git);
        if ($results['commit']['success']) {
            $results['push'] = git_exec('push origin main', $repo_path, $git);
        }
        break;

    case 'push':
        $results['push'] = git_exec('push origin main', $repo_path, $git);
        break;

    case 'checkout':
        // Revertir a un commit anterior
        $ref = $input['ref'] ?? '';
        $file = $input['file'] ?? '';
        if ($ref && $file) {
            $results['checkout'] = git_exec("checkout $ref -- " . escapeshellarg($file), $repo_path, $git);
        } else {
            $results['error'] = 'Se requiere ref (commit hash) y file';
        }
        break;

    default:
        $results['error'] = "Acción no reconocida: $action";
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
