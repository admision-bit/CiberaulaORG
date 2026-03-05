<?php


/**
 * Página de Categoría v5 - Catálogo Móvil Ciberaula
 * FAQ contextualizadas + Schema JSON-LD (FAQPage, BreadcrumbList)
 * v5: enlaces a cursos usan URL amigable /curso-bonificado-de-[shortname]
 */
require __DIR__ . '/config.php';
require __DIR__ . '/seo.php';

$catId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$catId) { header('Location: ' . BASE_URL); exit; }

$db = db();

$stmt = $db->prepare("SELECT id, name, description FROM mdl_course_categories WHERE id = ? AND visible = 1");
$stmt->bind_param('i', $catId);
$stmt->execute();
$cat = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cat) { header('Location: ' . BASE_URL); exit; }

$nombre = clean_cat_name($cat['name']);
$heroUrl = extract_hero_url($cat['description']);
$editorialTitle = extract_editorial_title($cat['description']);
$editorialText = extract_editorial($cat['description']);

$stmt = $db->prepare("SELECT id, fullname, shortname, summary FROM mdl_course WHERE category = ? AND visible = 1 ORDER BY fullname");
$stmt->bind_param('i', $catId);
$stmt->execute();
$result = $stmt->get_result();
$cursos = [];
while ($row = $result->fetch_assoc()) {
    $data = extract_course_data($row['summary']);
    $cursos[] = [
        'id' => $row['id'],
        'shortname' => $row['shortname'],
        'nombre' => plain_text($row['fullname']),
        'horas' => $data['horas'],
        'imagen' => $data['imagen'],
    ];
}
$stmt->close();

$nombreCorto = short_cat_name($nombre);

// Paleta de colores para cursos sin imagen
$palette = ['#1B2A4A','#2d4a7a','#E07A5F','#6B8F71','#8B5E3C','#5C6BC0','#00897B','#C62828','#F4845F','#7E57C2','#0277BD','#AD1457'];

$pageTitle = $nombre;
$pageDesc = 'Cursos bonificados de ' . $nombreCorto . ' para empresas. 100% bonificable por FUNDAE. ' . count($cursos) . ' cursos disponibles.';
$pageImage = $heroUrl ?: '';

// FAQ contextualizadas
$faqs = get_faq_categoria($nombreCorto);

// Schema JSON-LD
$pageSchemas = [
    schema_faqpage($faqs),
    schema_breadcrumbs([
        ['name' => 'Inicio', 'url' => 'https://www.ciberaula.org' . BASE_URL],
        ['name' => $nombre, 'url' => cat_url($catId)],
    ]),
];

require __DIR__ . '/header.php';
?>

<nav class="breadcrumb">
  <a href="<?= BASE_URL ?>">Inicio</a> &rsaquo; <?= htmlspecialchars($nombre) ?>
</nav>

<?php if ($heroUrl): ?>
<img class="cat-hero-img" src="<?= htmlspecialchars($heroUrl) ?>" alt="<?= htmlspecialchars($nombre) ?>" width="800" height="300">
<?php endif; ?>

<h1 class="cat-title"><?= htmlspecialchars($nombre) ?></h1>

<?php if ($editorialText): ?>
<article class="cat-editorial">
  <?php if ($editorialTitle): ?>
  <h2 class="cat-editorial-title"><?= htmlspecialchars($editorialTitle) ?></h2>
  <?php endif; ?>
  <p><?= $editorialText ?></p>
</article>
<?php endif; ?>

<div class="info-block">
  <div class="info-block-header">
    <img src="<?= IMG_BASE ?>comunes/Bonificacion.png" alt="" width="24" height="24"> Bonificación por FUNDAE
  </div>
  <div class="info-block-body">
    <p>Los cursos de <strong><?= htmlspecialchars($nombreCorto) ?></strong> pueden ser bonificados al 100% para la empresa receptora, incluso aunque tenga un porcentaje de copago en razón de su número de empleados en plantilla.</p>
    <div class="bonif-badge">BONIFICACIÓN 100%</div>
  </div>
</div>

<div class="info-block">
  <div class="info-block-header">
    <img src="<?= IMG_BASE ?>comunes/Tiempo.png" alt="" width="24" height="24"> Duración y Plazo
  </div>
  <div class="info-block-body">
    <p>La duración de los cursos de <strong><?= htmlspecialchars($nombreCorto) ?> online</strong> puede estar entre 20 y 100 horas lectivas. El plazo para realizarlo es flexible.</p>
    <p><strong>Fecha de inicio:</strong> Se puede determinar libremente. En cursos bonificados se debe comunicar a FUNDAE con al menos 7 días de antelación.</p>
  </div>
</div>

<div class="info-block">
  <div class="info-block-header">
    <img src="<?= IMG_BASE ?>comunes/modalidades.png" alt="" width="24" height="24"> Modalidades
  </div>
  <div class="info-block-body">
    <p>El curso se imparte en tres modalidades:</p>
    <ul>
      <li>Teleformación con asistencia de profesor (acceso 24 hs.)</li>
      <li>Clases en vivo - Aula Virtual</li>
      <li>Modalidad MIXTA: combinación de ambas</li>
    </ul>
    <p>En cualquiera de los casos es bonificable al 100% a través de FUNDAE.</p>
  </div>
</div>

<section class="courses-section">
  <h2 class="courses-title"><?= count($cursos) ?> cursos disponibles</h2>
  <?php foreach ($cursos as $i => $curso): ?>
  <?php $cursoHref = $curso['shortname'] ? '/curso-bonificado-de-' . $curso['shortname'] : '/m/curso.php?id=' . $curso['id']; ?>
  <a href="<?= $cursoHref ?>" class="course-item">
    <?php if ($curso['imagen']): ?>
    <img src="<?= htmlspecialchars($curso['imagen']) ?>" alt="" loading="lazy" width="60" height="60">
    <?php else:
      $color = $palette[$curso['id'] % count($palette)];
      $inicial = mb_strtoupper(mb_substr($curso['nombre'], 0, 1));
    ?>
    <div class="course-item-noimg" style="background:<?= $color ?>"><span><?= $inicial ?></span></div>
    <?php endif; ?>
    <div class="course-info">
      <div class="course-name"><?= htmlspecialchars($curso['nombre']) ?></div>
      <?php if ($curso['horas']): ?>
      <div class="course-hours"><?= $curso['horas'] ?> horas - 100% bonificable</div>
      <?php endif; ?>
    </div>
    <span class="course-arrow">&rsaquo;</span>
  </a>
  <?php endforeach; ?>
</section>

<?php
// FAQ contextualizadas de la categoría
render_faq($faqs, 'Preguntas frecuentes sobre cursos de ' . $nombreCorto);

require __DIR__ . '/footer.php';
?>
