<?php
// curso_redirect.php - Redirige URL tecnica de curso a URL amigable
// Uso: /curso_redirect.php?id=765
// Desktop: 301 -> /curso-bonificado-de-[shortname]
// Movil:   302 -> /m/curso.php?slug=[shortname]

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: /', true, 301);
    exit;
}

$db = new mysqli('localhost', 'ciberaulaorg', 'Ciberaula2026Moodle', 'moodle.ciberaula_org');
if ($db->connect_error) {
    header('Location: /course/view.php?id=' . $id, true, 302);
    exit;
}

$stmt = $db->prepare('SELECT shortname FROM mdl_course WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($shortname);
$stmt->fetch();
$stmt->close();
$db->close();

if (!$shortname) {
    header('Location: /', true, 301);
    exit;
}

// Deteccion de movil por User-Agent
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$is_mobile = preg_match('/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|mobile|CriOS/i', $ua);

if ($is_mobile) {
    header('Location: /m/curso.php?slug=' . urlencode($shortname), true, 302);
} else {
    header('Location: /curso-bonificado-de-' . $shortname, true, 301);
}
exit;
