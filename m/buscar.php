<?php
/**
 * Buscador AJAX — Catálogo Móvil Ciberaula
 * v2 - Prepared statements para prevenir SQL injection
 */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (mb_strlen($q) < 2) { echo '[]'; exit; }

$db = db();
$like = '%' . $q . '%';
$results = [];

// Buscar categorías — prepared statement
$stmt = $db->prepare(
    "SELECT c.id, c.name FROM mdl_course_categories c
     WHERE c.visible = 1 AND c.description LIKE '%cat-page%'
     AND c.name LIKE ? LIMIT 5"
);
$stmt->bind_param('s', $like);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $results[] = [
        'nombre' => short_cat_name($row['name']),
        'url'    => cat_url($row['id']),
        'horas'  => '',
        'tipo'   => 'cat'
    ];
}
$stmt->close();

// Buscar cursos — prepared statement
$stmt = $db->prepare(
    "SELECT co.id, co.shortname, co.fullname, co.summary FROM mdl_course co
     WHERE co.visible = 1 AND co.fullname LIKE ? LIMIT 10"
);
$stmt->bind_param('s', $like);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $data = extract_course_data($row['summary']);
    $url = $row['shortname']
        ? '/curso-bonificado-de-' . $row['shortname']
        : BASE_URL . 'curso.php?id=' . $row['id'];
    $results[] = [
        'nombre' => plain_text($row['fullname']),
        'url'    => $url,
        'horas'  => $data['horas'],
        'tipo'   => 'curso'
    ];
}
$stmt->close();

echo json_encode($results, JSON_UNESCAPED_UNICODE);
?>
