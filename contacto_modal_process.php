<?php
/**
 * contacto_modal_process.php
 * Procesador AJAX del formulario de contacto modal — ciberaula.org
 * Solo acepta POST con X-Requested-With: XMLHttpRequest
 * Devuelve JSON: {"ok":true} o {"ok":false,"error":"mensaje"}
 *
 * Creado: 07/03/2026
 */

// Solo AJAX
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Método no permitido']);
    exit;
}

$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$esAjax) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Petición no válida']);
    exit;
}

// ── CONFIGURACIÓN SMTP ────────────────────────────────────────
$smtp_host       = 'ssl://ciberaula.org';
$smtp_port       = 465;
$smtp_user       = 'admision@ciberaula.org';
$smtp_pass       = "0n\$w67d8P";
$destinatario    = 'admision@ciberaula.com';
$remitente       = 'admision@ciberaula.org';
$nombre_remitente= 'Ciberaula';

// ── HONEYPOT ─────────────────────────────────────────────────
if (!empty($_POST['website'])) {
    sleep(1);
    echo json_encode(['ok'=>true]); // Silencioso para bots
    exit;
}

// ── RECIBIR Y VALIDAR ────────────────────────────────────────
$nombre     = htmlspecialchars(trim($_POST['nombre']    ?? ''));
$empresa    = htmlspecialchars(trim($_POST['empresa']   ?? ''));
$email      = filter_var(trim($_POST['email']     ?? ''), FILTER_SANITIZE_EMAIL);
$telefono   = htmlspecialchars(trim($_POST['telefono']  ?? ''));
$mensaje    = htmlspecialchars(trim($_POST['mensaje']   ?? ''));
$url_origen = htmlspecialchars(trim($_POST['url_origen']?? ($_SERVER['HTTP_REFERER'] ?? 'No especificada')));

$errores = [];
if (empty($nombre))                                          $errores[] = 'El nombre es obligatorio';
if (empty($email))                                           $errores[] = 'El email es obligatorio';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido';
if (empty($mensaje) || mb_strlen($mensaje) < 5)             $errores[] = 'El mensaje es obligatorio';
if (empty($_POST['acepta_rgpd']))                            $errores[] = 'Debes aceptar la política de datos';

if (!empty($errores)) {
    echo json_encode(['ok'=>false,'error'=>implode('. ', $errores)]);
    exit;
}

// ── PREPARAR EMAILS ──────────────────────────────────────────
$fecha = date('d/m/Y H:i:s');

$empresaLinea = $empresa ? "EMPRESA: $empresa\r\n" : '';
$telefonoLinea= $telefono? "TELÉFONO: $telefono\r\n": '';

$asunto_admin = 'Contacto Web — Nueva consulta ciberaula.org';
$msg_admin    = "NUEVA CONSULTA DESDE CIBERAULA.ORG\r\n";
$msg_admin   .= "===========================================\r\n\r\n";
$msg_admin   .= "NOMBRE: $nombre\r\n";
$msg_admin   .= $empresaLinea;
$msg_admin   .= "EMAIL: $email\r\n";
$msg_admin   .= $telefonoLinea;
$msg_admin   .= "\r\nMENSAJE:\r\n$mensaje\r\n\r\n";
$msg_admin   .= "-------------------------------------------\r\n";
$msg_admin   .= "Página de origen: $url_origen\r\n";
$msg_admin   .= "Fecha: $fecha\r\n";
$msg_admin   .= "===========================================\r\n";

$asunto_usr = 'Hemos recibido tu consulta — Ciberaula';
$msg_usr    = "Hola $nombre,\r\n\r\n";
$msg_usr   .= "Hemos recibido tu consulta y te responderemos en un máximo de 3 horas en días laborables.\r\n\r\n";
$msg_usr   .= "-------------------------------------------\r\n";
$msg_usr   .= "TU MENSAJE:\r\n$mensaje\r\n";
$msg_usr   .= "-------------------------------------------\r\n\r\n";
$msg_usr   .= "Gracias por contactar con Ciberaula.\r\n";
$msg_usr   .= "https://www.ciberaula.org\r\n\r\n";
$msg_usr   .= "(No respondas a este email automático)\r\n";

// ── ENVÍO ────────────────────────────────────────────────────
$resultado = enviarSMTP($smtp_host,$smtp_port,$smtp_user,$smtp_pass,
                        $remitente,$nombre_remitente,$destinatario,
                        $asunto_admin,$msg_admin,$email);

if ($resultado === true) {
    // Copia al usuario (no crítica — ignorar si falla)
    enviarSMTP($smtp_host,$smtp_port,$smtp_user,$smtp_pass,
               $remitente,$nombre_remitente,$email,
               $asunto_usr,$msg_usr,$remitente);
    echo json_encode(['ok'=>true]);
} else {
    error_log("contacto_modal_process.php SMTP error: $resultado");
    echo json_encode(['ok'=>false,'error'=>'Error técnico al enviar. Por favor llámanos al 911 976 752.']);
}

// ── FUNCIÓN SMTP SSL ─────────────────────────────────────────
function enviarSMTP($host,$port,$user,$pass,$from,$fromName,$to,$subject,$message,$replyTo) {
    $ctx = stream_context_create([
        'ssl' => ['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]
    ]);
    $socket = @stream_socket_client("$host:$port",$errno,$errstr,30,STREAM_CLIENT_CONNECT,$ctx);
    if (!$socket) return "Conexión fallida: $errstr ($errno)";

    fgets($socket,515);
    fputs($socket,"EHLO ciberaula.org\r\n");
    $r=''; while($s=fgets($socket,515)){$r.=$s; if(substr($s,3,1)==' ')break;}
    if(substr($r,0,3)!='250'){fclose($socket);return "EHLO: $r";}

    fputs($socket,"AUTH LOGIN\r\n");
    $r=fgets($socket,515);
    if(substr($r,0,3)!='334'){fclose($socket);return "AUTH: $r";}

    fputs($socket,base64_encode($user)."\r\n");
    $r=fgets($socket,515);
    if(substr($r,0,3)!='334'){fclose($socket);return "USER: $r";}

    fputs($socket,base64_encode($pass)."\r\n");
    $r=fgets($socket,515);
    if(substr($r,0,3)!='235'){fclose($socket);return "PASS: $r";}

    fputs($socket,"MAIL FROM:<$from>\r\n");
    $r=fgets($socket,515);
    if(substr($r,0,3)!='250'){fclose($socket);return "MAIL FROM: $r";}

    fputs($socket,"RCPT TO:<$to>\r\n");
    $r=fgets($socket,515);
    if(substr($r,0,3)!='250'){fclose($socket);return "RCPT TO: $r";}

    fputs($socket,"DATA\r\n");
    $r=fgets($socket,515);
    if(substr($r,0,3)!='354'){fclose($socket);return "DATA: $r";}

    $headers  = "From: $fromName <$from>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Reply-To: $replyTo\r\n";
    $headers .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Date: ".date('r')."\r\n\r\n";

    fputs($socket,$headers.$message."\r\n.\r\n");
    $r=fgets($socket,515);
    fputs($socket,"QUIT\r\n");
    fclose($socket);

    return (substr($r,0,3)==='250') ? true : "Envío: $r";
}
