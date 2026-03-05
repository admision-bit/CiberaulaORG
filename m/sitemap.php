<?php
/**
 * Sitemap XML dinámico - Catálogo Móvil Ciberaula
 * v2 - URLs amigables de producción (www.ciberaula.org)
 *      Eliminado X-Robots-Tag: noindex
 *      Categorías y cursos con URLs canónicas amigables
 */
require __DIR__ . '/config.php';

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = 'https://www.ciberaula.org';
$today = date('Y-m-d');

$db = db();

// Recoger categorías con cursos
$sqlCats = "SELECT c.id, c.name
            FROM mdl_course_categories c
            INNER JOIN mdl_course co ON co.category = c.id AND co.visible = 1
            WHERE c.visible = 1 AND c.description LIKE '%cat-page%'
            GROUP BY c.id
            ORDER BY c.name";
$resCats = $db->query($sqlCats);
$categorias = [];
while ($row = $resCats->fetch_assoc()) {
    $categorias[] = $row;
}

// Recoger todos los cursos visibles de categorías con cat-page
$sqlCursos = "SELECT co.id, co.shortname, co.fullname, co.category
              FROM mdl_course co
              INNER JOIN mdl_course_categories c ON c.id = co.category
              WHERE co.visible = 1
                AND c.visible = 1
                AND c.description LIKE '%cat-page%'
              ORDER BY co.fullname";
$resCursos = $db->query($sqlCursos);
$cursos = [];
while ($row = $resCursos->fetch_assoc()) {
    $cursos[] = $row;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

  <!-- Portada -->
  <url>
    <loc><?= $baseUrl ?>/</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- Contacto -->
  <url>
    <loc><?= $baseUrl ?>/m/contacto.php</loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>

  <!-- Categorías (URLs amigables) -->
<?php foreach ($categorias as $cat):
    $url = cat_url($cat['id']);
    if (!$url) continue;
?>
  <url>
    <loc><?= htmlspecialchars($url) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach; ?>

  <!-- Cursos (URLs amigables) -->
<?php foreach ($cursos as $curso):
    $url = $curso['shortname']
        ? $baseUrl . '/curso-bonificado-de-' . $curso['shortname']
        : null;
    if (!$url) continue;
?>
  <url>
    <loc><?= htmlspecialchars($url) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
<?php endforeach; ?>

</urlset>
