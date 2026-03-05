<?php
/**
 * Endpoint JSON de categorias - Catalogo Movil Ciberaula
 */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$cats = get_nav_categories();
$out = [];
foreach ($cats as $c) {
    $out[] = [
        'id' => (int)$c['id'],
        'nombre' => short_cat_name($c['name']),
        'n' => (int)$c['num_cursos'],
        'url' => cat_url($c['id'])
    ];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);