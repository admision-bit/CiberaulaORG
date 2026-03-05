<?php
/**
 * Formulario de Contacto v3 - Catalogo Movil Ciberaula
 * v3: Email HTML de confirmacion al usuario con logo y pie profesional
 */
session_start();
require __DIR__ . '/config.php';

$email_destino = 'admision@ciberaula.com';
$email_remite  = 'admision@ciberaula.com';
$nombre_remite = 'Ciberaula';

$smtp_enabled = true;
$smtp_host    = 'smtp.gmail.com';
$smtp_port    = 587;
$smtp_user    = 'admision@ciberaula.com';
$smtp_pass    = 'tgrg gowj ybsm dwes';
$smtp_secure  = 'tls';

function enviarEmailSMTP($para, $asunto, $cuerpo, $from_email, $from_name, $reply_to, $config, $content_type = 'text/plain') {
    $log = ''; $ok = false; $socket = null;
    try {
        if (!function_exists('fsockopen')) return ['ok'=>false,'log'=>"fsockopen no disponible\n"];
        $para = str_replace(["\r","\n"],'',$para);
        $asunto = str_replace(["\r","\n"],'',$asunto);
        $prefix = ($config['secure']==='ssl') ? 'ssl://' : '';
        $socket = @fsockopen($prefix.$config['host'], $config['port'], $errno, $errstr, 10);
        if (!$socket) return ['ok'=>false,'log'=>"Conexion fallida: $errstr\n"];
        $leer = function() use (&$socket,&$log) {
            $r=''; stream_set_timeout($socket,10);
            while($l=@fgets($socket,512)){$r.=$l;if(isset($l[3])&&$l[3]===' ')break;if(strlen($l)<4)break;}
            $log.="S:".trim($r)."\n"; return $r;
        };
        $cmd = function($c) use (&$socket,$leer,&$log) {
            $log.="C:".trim($c)."\n"; @fwrite($socket,$c."\r\n"); return $leer();
        };
        $leer(); $cmd('EHLO '.gethostname());
        if ($config['secure']==='tls') {
            $r=$cmd('STARTTLS');
            if(strpos($r,'220')===false){@fclose($socket);return['ok'=>false,'log'=>$log];}
            $m=defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')?STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT:STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if(!@stream_socket_enable_crypto($socket,true,$m)){@fclose($socket);return['ok'=>false,'log'=>$log];}
            $cmd('EHLO '.gethostname());
        }
        $r=$cmd('AUTH LOGIN');
        if(strpos($r,'334')===false){@fclose($socket);return['ok'=>false,'log'=>$log];}
        $r=$cmd(base64_encode($config['user']));
        if(strpos($r,'334')===false){@fclose($socket);return['ok'=>false,'log'=>$log];}
        $r=$cmd(base64_encode($config['pass']));
        if(strpos($r,'235')===false){@fclose($socket);return['ok'=>false,'log'=>$log];}
        $r=$cmd('MAIL FROM:<'.$from_email.'>');
        if(strpos($r,'250')===false){@fclose($socket);return['ok'=>false,'log'=>$log];}
        $r=$cmd('RCPT TO:<'.$para.'>');
        if(strpos($r,'250')===false){@fclose($socket);return['ok'=>false,'log'=>$log];}
        $r=$cmd('DATA');
        if(strpos($r,'354')===false){@fclose($socket);return['ok'=>false,'log'=>$log];}
        $msg ="From: $from_name <$from_email>\r\n";
        $msg.="To: $para\r\nReply-To: $reply_to\r\n";
        $msg.="Subject: =?UTF-8?B?".base64_encode($asunto)."?=\r\n";
        $msg.="MIME-Version: 1.0\r\nContent-Type: $content_type; charset=UTF-8\r\n";
        $msg.="Content-Transfer-Encoding: 8bit\r\n";
        $msg.="Date: ".date('r')."\r\n";
        $msg.="Message-ID: <".uniqid('ciberaula-m-',true)."@ciberaula.com>\r\n";
        $msg.="\r\n".$cuerpo."\r\n.\r\n";
        @fwrite($socket,$msg); $r=$leer();
        if(strpos($r,'250')!==false){$ok=true;}
        $cmd('QUIT'); @fclose($socket);
    } catch(\Throwable $e) {
        $log.="ERROR:".$e->getMessage()."\n";
        if($socket&&is_resource($socket))@fclose($socket);
    }
    return ['ok'=>$ok,'log'=>$log];
}

function enviarEmail($para,$asunto,$cuerpo,$from_email,$from_name,$reply_to,$ct='text/plain') {
    global $smtp_enabled,$smtp_host,$smtp_port,$smtp_user,$smtp_pass,$smtp_secure;
    if ($smtp_enabled && !empty($smtp_pass)) {
        $cfg=['host'=>$smtp_host,'port'=>$smtp_port,'user'=>$smtp_user,'pass'=>$smtp_pass,'secure'=>$smtp_secure];
        $r = enviarEmailSMTP($para,$asunto,$cuerpo,$from_email,$from_name,$reply_to,$cfg,$ct);
        if ($r['ok']) return ['ok'=>true,'method'=>'smtp','log'=>$r['log']];
    }
    $h ="From: $from_name <$from_email>\r\nReply-To: $reply_to\r\n";
    $h.="Content-Type: $ct; charset=UTF-8\r\nMIME-Version: 1.0\r\n";
    $ok = mail($para,$asunto,$cuerpo,$h,'-f'.$from_email);
    return ['ok'=>$ok,'method'=>'mail','log'=>''];
}

// Generar email HTML de confirmacion para el usuario
function generarEmailUsuario($nombre, $mensaje, $catalogo_url) {
    $nombre_html = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $mensaje_html = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));

    return '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:Arial,Helvetica,sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f2f5;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;width:100%;box-shadow:0 2px 12px rgba(0,0,0,0.08);">

<!-- CABECERA CON LOGO -->
<tr>
<td style="background:linear-gradient(135deg,#1B2A4A 0%,#2d4a7a 100%);padding:32px 40px;text-align:center;">
<img src="https://www.ciberaula.com/img/logo.png" alt="Ciberaula" width="200" style="display:block;margin:0 auto;max-width:200px;height:auto;">
<p style="margin:12px 0 0;font-size:13px;color:rgba(255,255,255,0.7);letter-spacing:0.5px;">Formaci&oacute;n online para empresas desde 1996</p>
</td>
</tr>

<!-- ICONO CHECK -->
<tr>
<td style="padding:28px 40px 0;text-align:center;">
<div style="display:inline-block;width:60px;height:60px;border-radius:50%;background-color:#e8f5e9;line-height:60px;text-align:center;">
<span style="color:#2e7d32;font-size:30px;font-weight:bold;">&#10003;</span>
</div>
</td>
</tr>

<!-- TITULO -->
<tr>
<td style="padding:16px 40px 0;text-align:center;">
<h1 style="margin:0;font-size:22px;color:#1B2A4A;font-weight:700;">&iexcl;Mensaje recibido!</h1>
</td>
</tr>

<!-- SALUDO Y TEXTO -->
<tr>
<td style="padding:16px 40px 0;font-size:15px;line-height:1.6;color:#4a5568;">
<p style="margin:0 0 12px;">Hola <strong>' . $nombre_html . '</strong>,</p>
<p style="margin:0 0 12px;">Hemos recibido su consulta correctamente. Nuestro equipo la revisar&aacute; y le responderemos lo antes posible, normalmente en <strong>menos de 3 horas</strong> en d&iacute;as laborales.</p>
</td>
</tr>

<!-- RESUMEN DEL MENSAJE -->
<tr>
<td style="padding:12px 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="background-color:#faf6f0;border-left:4px solid #E07A5F;padding:16px 20px;border-radius:0 8px 8px 0;">
<p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.8px;">Su consulta</p>
<p style="margin:0;font-size:14px;line-height:1.6;color:#1a202c;">' . $mensaje_html . '</p>
</td>
</tr>
</table>
</td>
</tr>

<!-- CONTACTO DIRECTO -->
<tr>
<td style="padding:24px 40px 0;">
<p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#1B2A4A;">&iquest;Necesita contactarnos antes?</p>
<table role="presentation" cellpadding="0" cellspacing="0">
<tr>
<td style="padding:4px 0;font-size:14px;color:#4a5568;">
&#9742;&nbsp; <a href="tel:+34915303387" style="color:#4a5568;text-decoration:none;">915 303 387</a>
</td>
</tr>
<tr>
<td style="padding:4px 0;font-size:14px;color:#4a5568;">
&#128172;&nbsp; <a href="https://wa.me/34620505230" style="color:#4a5568;text-decoration:none;">WhatsApp: 620 505 230</a>
</td>
</tr>
<tr>
<td style="padding:4px 0;font-size:14px;color:#4a5568;">
&#9993;&nbsp; <a href="mailto:admision@ciberaula.com" style="color:#2c5282;text-decoration:none;">admision@ciberaula.com</a>
</td>
</tr>
</table>
</td>
</tr>

<!-- SEPARADOR -->
<tr>
<td style="padding:24px 40px 0;">
<hr style="border:none;border-top:1px solid #e8e8e8;margin:0;">
</td>
</tr>

<!-- BOTON CATALOGO -->
<tr>
<td style="padding:24px 40px 0;text-align:center;">
<p style="margin:0 0 14px;font-size:14px;color:#4a5568;">Mientras tanto, explore nuestro cat&aacute;logo de cursos bonificados:</p>
<a href="' . htmlspecialchars($catalogo_url) . '" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#E07A5F,#d4694f);color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:8px;">Ver cat&aacute;logo de cursos</a>
</td>
</tr>

<!-- PIE -->
<tr>
<td style="padding:28px 40px;text-align:center;">
<p style="margin:0 0 4px;font-size:13px;color:#1B2A4A;font-weight:700;">Ciberaula de Formaci&oacute;n Online S.L.</p>
<p style="margin:0 0 4px;font-size:12px;color:#a0aec0;">P&ordm; de la Castellana 91, 4&ordf; planta &middot; 28046 Madrid</p>
<p style="margin:0 0 4px;font-size:12px;color:#a0aec0;">Entidad autorizada FUNDAE</p>
<p style="margin:10px 0 0;">
<a href="https://www.ciberaula.com" style="display:inline-block;padding:8px 20px;background-color:#1B2A4A;color:#ffffff;font-size:12px;font-weight:600;text-decoration:none;border-radius:4px;">ciberaula.com</a>
</p>
</td>
</tr>

</table>
</td></tr>

<!-- NOTA LEGAL EXTERIOR -->
<tr>
<td align="center" style="padding:16px 20px;">
<p style="margin:0;font-size:11px;color:#a0aec0;line-height:1.5;max-width:500px;">
Este correo es una confirmaci&oacute;n autom&aacute;tica de su consulta enviada a trav&eacute;s de Ciberaula. Si no ha realizado esta solicitud, puede ignorar este mensaje.
</p>
</td>
</tr>

</table>
</body>
</html>';
}

$opciones_fuente = [
    ''                => 'Seleccione...',
    'google'          => 'Google (b&uacute;squeda)',
    'chatgpt'         => 'ChatGPT',
    'perplexity'      => 'Perplexity',
    'gemini'          => 'Gemini',
    'copilot'         => 'Copilot (Microsoft)',
    'linkedin'        => 'LinkedIn',
    'cliente'         => 'Ya soy cliente',
    'recomendacion'   => 'Recomendaci&oacute;n de un conocido',
    'evento'          => 'Evento o feria profesional',
    'otro'            => 'Otro',
];

// Para validacion server-side (sin entidades HTML)
$opciones_fuente_plain = [
    ''                => 'Seleccione...',
    'google'          => 'Google (búsqueda)',
    'chatgpt'         => 'ChatGPT',
    'perplexity'      => 'Perplexity',
    'gemini'          => 'Gemini',
    'copilot'         => 'Copilot (Microsoft)',
    'linkedin'        => 'LinkedIn',
    'cliente'         => 'Ya soy cliente',
    'recomendacion'   => 'Recomendación de un conocido',
    'evento'          => 'Evento o feria profesional',
    'otro'            => 'Otro',
];

if(empty($_SESSION['csrf_token_m'])) $_SESSION['csrf_token_m']=bin2hex(random_bytes(32));
if(!isset($_SESSION['form_time_m'])) $_SESSION['form_time_m']=time();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['form_referer'] = $_SERVER['HTTP_REFERER'] ?? '';
}

$errores=[]; $exito=false;
$nombre=$email=$telefono=$mensaje=$fuente='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!empty($_POST['website'])) { $exito=true; }
    else {
        $t = $_POST['csrf_token_m'] ?? '';
        if (!hash_equals($_SESSION['csrf_token_m'],$t)) $errores[]='Sesión expirada. Recargue la página.';
        if (empty($errores) && (time()-($_SESSION['form_time_m']??time()))<3) $errores[]='Rellene el formulario con calma.';

        $nombre   = trim($_POST['nombre'] ?? '');
        $email    = trim(filter_var($_POST['email']??'', FILTER_SANITIZE_EMAIL));
        $telefono = trim(preg_replace('/[^0-9+\s\-]/','',$_POST['telefono']??''));
        $mensaje  = trim($_POST['mensaje'] ?? '');
        $fuente   = trim($_POST['fuente'] ?? '');
        $pagina_origen = trim($_POST['pagina_origen'] ?? '');

        if (empty($errores)) {
            if (empty($nombre)||mb_strlen($nombre)<3) $errores[]='Nombre obligatorio (mín. 3 caracteres).';
            if (empty($email)||!filter_var($email,FILTER_VALIDATE_EMAIL)) $errores[]='Email válido obligatorio.';
            $tn=preg_replace('/[^0-9]/','',$telefono);
            if (empty($telefono)||strlen($tn)<9) $errores[]='Teléfono obligatorio (mín. 9 dígitos).';
            if (empty($mensaje)||mb_strlen($mensaje)<10) $errores[]='Mensaje obligatorio (mín. 10 caracteres).';
            if (empty($fuente)||!array_key_exists($fuente,$opciones_fuente_plain)) $errores[]='Indique cómo nos ha conocido.';
            if (preg_match_all('/https?:\/\//i', $mensaje) > 2) $errores[]='El mensaje contiene demasiados enlaces.';
        }

        if (empty($errores)) {
            $fuente_texto = $opciones_fuente_plain[$fuente] ?? $fuente;

            // --- Email texto plano a admision ---
            $cuerpo ="CONTACTO DESDE CATÁLOGO MÓVIL\n";
            $cuerpo.="================================\n\n";
            $cuerpo.="Nombre: $nombre\n";
            $cuerpo.="Email: $email\n";
            $cuerpo.="Teléfono: $telefono\n";
            $cuerpo.="Nos conoció por: $fuente_texto\n";
            $cuerpo.="Página de origen: " . ($pagina_origen ?: 'Acceso directo') . "\n\n";
            $cuerpo.="Mensaje:\n--------\n$mensaje\n\n";
            $cuerpo.="================================\n";
            $cuerpo.="Enviado: ".date('d/m/Y H:i:s')."\n";
            $cuerpo.="IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . "\n";

            $r = enviarEmail($email_destino,'Contacto catálogo móvil - '.$fuente_texto,$cuerpo,$email_remite,$nombre_remite,$email);

            if ($r['ok']) {
                $exito = true;
                $_SESSION['csrf_token_m']=bin2hex(random_bytes(32));
                $_SESSION['form_time_m']=time();

                // --- Email HTML de confirmacion al usuario ---
                $catalogo_url = 'https://www.ciberaula.com/catalogo/';
                $email_html = generarEmailUsuario($nombre, $mensaje, $catalogo_url);
                enviarEmail(
                    $email,
                    'Hemos recibido su consulta - Ciberaula',
                    $email_html,
                    $email_remite,
                    $nombre_remite,
                    $email_destino,
                    'text/html'
                );

                $nombre=$email=$telefono=$mensaje=$fuente='';
            } else {
                $errores[]='Error al enviar. Puede llamarnos al 915 303 387 o escribir a admision@ciberaula.com.';
            }
        }
    }
}

$pageTitle = 'Contacto';
$pageDesc = 'Contacta con Ciberaula. Cursos bonificados FUNDAE para empresas. Tel: 915 303 387.';
require __DIR__ . '/header.php';
?>

<nav class="breadcrumb">
  <a href="<?= BASE_URL ?>">Inicio</a> &rsaquo; Contacto
</nav>

<div class="contacto-page">

  <h1 class="contacto-title">Contacte con nosotros</h1>
  <p class="contacto-sub">Respondemos en menos de 3 horas en días laborales.</p>

  <?php if ($exito): ?>
  <div class="contacto-ok">
    <div class="contacto-ok-icon">&#10003;</div>
    <h2>Mensaje enviado</h2>
    <p>Gracias por contactar con nosotros. Le hemos enviado una copia de confirmación a su correo electrónico. Le responderemos lo antes posible.</p>
    <p style="font-size:12px;color:#888;margin-top:8px">Si no encuentra la copia, revise la carpeta de <strong>spam</strong>.</p>
    <a href="<?= BASE_URL ?>" class="contact-form-btn" style="margin-top:16px">Volver al catálogo</a>
  </div>
  <?php else: ?>

  <?php if (!empty($errores)): ?>
  <div class="contacto-errores">
    <?php foreach($errores as $e): ?>
    <p><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>contacto.php" id="mContactForm" novalidate>
    <input type="hidden" name="csrf_token_m" value="<?= htmlspecialchars($_SESSION['csrf_token_m']) ?>">
    <input type="hidden" name="pagina_origen" value="<?= htmlspecialchars($_SESSION['form_referer'] ?? '') ?>">
    <div style="position:absolute;left:-9999px" aria-hidden="true">
      <input type="text" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="form-field">
      <label for="mNombre">Nombre completo *</label>
      <input type="text" id="mNombre" name="nombre" required minlength="3" maxlength="100"
             autocomplete="name" value="<?= htmlspecialchars($nombre) ?>"
             placeholder="Ej: María García López">
    </div>

    <div class="form-field">
      <label for="mEmail">Correo electrónico *</label>
      <input type="email" id="mEmail" name="email" required maxlength="150"
             autocomplete="email" value="<?= htmlspecialchars($email) ?>"
             placeholder="Ej: maria@empresa.com">
    </div>

    <div class="form-field">
      <label for="mTel">Teléfono *</label>
      <input type="tel" id="mTel" name="telefono" required minlength="9" maxlength="20"
             autocomplete="tel" value="<?= htmlspecialchars($telefono) ?>"
             placeholder="Ej: 612 345 678">
    </div>

    <div class="form-field">
      <label for="mFuente">¿Cómo nos ha conocido? *</label>
      <select id="mFuente" name="fuente" required>
        <?php foreach ($opciones_fuente as $val => $txt): ?>
        <option value="<?= htmlspecialchars($val) ?>" <?= ($fuente===$val)?'selected':'' ?>><?= $txt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-field">
      <label for="mMsg">Mensaje *</label>
      <textarea id="mMsg" name="mensaje" required minlength="10" maxlength="5000" rows="4"
                placeholder="Describa su consulta..."><?= htmlspecialchars($mensaje) ?></textarea>
      <div class="form-char-count" id="mMsgCount">0 / 5000</div>
    </div>

    <button type="submit" class="contact-form-btn form-submit-btn" id="mBtnSend">
      Enviar mensaje
    </button>

    <p class="form-legal">Al enviar acepta nuestra <a href="https://www.ciberaula.com/formulario/politica-datos.html" target="_blank" rel="noopener">política de datos</a>. Responsable: Ciberaula de Formación Online S.L.</p>
  </form>
  <?php endif; ?>

</div>

<script>
(function(){
  var f=document.getElementById('mContactForm');
  if(!f)return;
  var msg=document.getElementById('mMsg');
  var cnt=document.getElementById('mMsgCount');
  if(msg&&cnt){msg.addEventListener('input',function(){cnt.textContent=this.value.length+' / 5000';});}
  f.addEventListener('submit',function(e){
    var ok=true;
    ['mNombre','mEmail','mTel','mMsg'].forEach(function(id){
      var el=document.getElementById(id);
      if(!el.value.trim()||!el.checkValidity()){el.style.borderColor='#c00';ok=false;}
      else{el.style.borderColor='#6B8F71';}
    });
    var sel=document.getElementById('mFuente');
    if(!sel.value){sel.style.borderColor='#c00';ok=false;}
    else{sel.style.borderColor='#6B8F71';}
    if(!ok){e.preventDefault();return;}
    var btn=document.getElementById('mBtnSend');
    btn.disabled=true;btn.textContent='Enviando...';
  });
})();
</script>

<?php require __DIR__ . '/footer.php'; ?>