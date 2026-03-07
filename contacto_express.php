<?php
// contacto_express.php - Ciberaula.org
// Acepta POST con: nombre, email, comentario, url_origen, website (honeypot)
// Devuelve JSON si X-Requested-With: XMLHttpRequest
// Renderiza página Moodle si acceso directo

$smtp_host = "ssl://ciberaula.org";
$smtp_port = 465;
$smtp_user = "admision@ciberaula.org";
$smtp_pass = "0n\$w67d8P";
$destinatario = "admision@ciberaula.com";
$remitente    = "admision@ciberaula.org";
$nombre_remitente = "Ciberaula";

$es_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_GET['ajax']) && $_GET['ajax'] === '1');

header('Content-Type: ' . ($es_ajax ? 'application/json' : 'text/html') . '; charset=utf-8');

// Solo procesar en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($es_ajax) { echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }
    // GET directo: redirigir a la página de IA
    header('Location: /formacion-inteligencia-artificial-empresas/');
    exit;
}

// Honeypot
if (!empty($_POST['website'])) {
    if ($es_ajax) { echo json_encode(['ok'=>true]); exit; }
    header('Location: /'); exit;
}

$nombre    = htmlspecialchars(trim($_POST['nombre']    ?? ''));
$email     = filter_var(trim($_POST['email']    ?? ''), FILTER_SANITIZE_EMAIL);
$comentario= htmlspecialchars(trim($_POST['comentario'] ?? ''));
$url_origen= htmlspecialchars(trim($_POST['url_origen'] ?? $_SERVER['HTTP_REFERER'] ?? 'No especificada'));

$errores = [];
if (empty($nombre))    $errores[] = 'El nombre es obligatorio';
if (empty($email))     $errores[] = 'El email es obligatorio';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido';
if (empty($comentario))$errores[] = 'El mensaje es obligatorio';

if (!empty($errores)) {
    if ($es_ajax) { echo json_encode(['ok'=>false,'error'=>implode('. ',$errores)]); exit; }
    exit('Error: '.implode('. ',$errores));
}

$fecha = date('d/m/Y H:i:s');

$asunto_admin   = "Contacto Web - Nueva consulta desde ciberaula.org";
$mensaje_admin  = "NUEVA CONSULTA\r\n===================\r\n\r\n";
$mensaje_admin .= "NOMBRE: $nombre\r\nEMAIL: $email\r\n\r\nMENSAJE:\r\n$comentario\r\n\r\n";
$mensaje_admin .= "Página: $url_origen\r\nFecha: $fecha\r\n";

$asunto_usuario   = "Hemos recibido tu consulta - Ciberaula";
$mensaje_usuario  = "Hola $nombre,\r\n\r\nHemos recibido tu consulta y te responderemos en un plazo máximo de 3 horas en días laborables.\r\n\r\n";
$mensaje_usuario .= "---\r\nTU MENSAJE:\r\n$comentario\r\n---\r\n\r\nGracias por contactar con Ciberaula.\r\nhttps://www.ciberaula.org\r\n\r\n(No respondas a este email automático)";

$ok = enviarSMTP($smtp_host,$smtp_port,$smtp_user,$smtp_pass,$remitente,$nombre_remitente,$destinatario,$asunto_admin,$mensaje_admin,$email);
if ($ok === true) {
    enviarSMTP($smtp_host,$smtp_port,$smtp_user,$smtp_pass,$remitente,$nombre_remitente,$email,$asunto_usuario,$mensaje_usuario,$remitente);
}

if ($es_ajax) {
    echo json_encode($ok === true ? ['ok'=>true] : ['ok'=>false,'error'=>'Error técnico. Por favor llámanos al 911 976 752.']);
    exit;
}

// Nunca debería llegar aquí en uso normal
echo $ok === true ? 'OK' : 'ERROR: '.$ok;

// ── SMTP SSL ──────────────────────────────────────────
function enviarSMTP($host,$port,$user,$pass,$from,$fromName,$to,$subject,$message,$replyTo){
    $ctx = stream_context_create(['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]]);
    $socket = @stream_socket_client("$host:$port",$errno,$errstr,30,STREAM_CLIENT_CONNECT,$ctx);
    if (!$socket) return "Conexión: $errstr ($errno)";
    fgets($socket,515);
    fputs($socket,"EHLO ciberaula.org\r\n"); $r='