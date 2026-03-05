<?php
/**
 * curso_ficha.php - Ficha de Curso Desktop Ciberaula
 * Muestra el contenido del summary del curso con diseño editorial.
 * Reutiliza config, seo y helpers de /m/
 * v1 - 04/03/2026
 */
require __DIR__ . '/m/config.php';
require __DIR__ . '/m/seo.php';

$db = db();

// Acepta ?slug= (desde .htaccess) o ?id=
if (!empty($_GET['slug'])) {
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug']));
    $stmt = $db->prepare("SELECT c.id, c.fullname, c.shortname, c.summary, c.category,
                           cat.name as cat_name, cat.description as cat_desc
                           FROM mdl_course c
                           LEFT JOIN mdl_course_categories cat ON cat.id = c.category
                           WHERE c.shortname = ? AND c.visible = 1");
    $stmt->bind_param('s', $slug);
} else {
    $cursoId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$cursoId) { header('Location: /'); exit; }
    $stmt = $db->prepare("SELECT c.id, c.fullname, c.shortname, c.summary, c.category,
                           cat.name as cat_name, cat.description as cat_desc
                           FROM mdl_course c
                           LEFT JOIN mdl_course_categories cat ON cat.id = c.category
                           WHERE c.id = ? AND c.visible = 1");
    $stmt->bind_param('i', $cursoId);
}
$stmt->execute();
$curso = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$curso) { header('Location: /'); exit; }

$nombre     = plain_text($curso['fullname']);
$catNombre  = clean_cat_name($curso['cat_name']);
$catNombreCorto = short_cat_name($curso['cat_name']);
$data       = extract_course_data($curso['summary']);

// Imagen: del summary, fallback hero de categoria, fallback genérica
$imagen = $data['imagen'];
if (!$imagen && !empty($curso['cat_desc'])) {
    $imagen = extract_hero_url($curso['cat_desc']);
}
if (!$imagen) {
    $imagen = IMG_BASE . 'comunes/CursosOnlineRedesSocialesORG.jpg';
}

// URLs canónicas
$shortname  = $curso['shortname'];
$cursoUrl   = $shortname
    ? 'https://www.ciberaula.org/curso-bonificado-de-' . $shortname
    : 'https://www.ciberaula.org/curso_ficha.php?id=' . $curso['id'];

$catUrl = cat_url($curso['category']);

// SEO
$pageTitle    = 'Curso bonificado de ' . $nombre . ' | Ciberaula';
$pageDesc     = $data['objetivo']
    ? mb_substr(strip_tags($data['objetivo']), 0, 155)
    : 'Curso bonificado de ' . $nombre . '. 100% bonificable por FUNDAE para empresas.';
$pageImage    = $imagen;
$pageCanonical = $cursoUrl;

// FAQ + Schema
$faqs = get_faq_curso($nombre, $data['horas']);
$pageSchemas = [
    schema_course($nombre, $data['objetivo'] ?: $pageDesc, $data['horas'], $cursoUrl, $imagen),
    schema_faqpage($faqs),
    schema_breadcrumbs([
        ['name' => 'Inicio',      'url' => 'https://www.ciberaula.org/'],
        ['name' => $catNombre,    'url' => $catUrl],
        ['name' => $nombre,       'url' => $cursoUrl],
    ]),
];

require __DIR__ . '/m/header.php';
?>

<style>
/* === FICHA DE CURSO DESKTOP === */
.ficha-wrap {
    max-width: 900px;
    margin: 0 auto;
    padding: 24px 20px 60px;
}
.ficha-breadcrumb {
    font-size: 13px;
    color: #888;
    margin-bottom: 20px;
}
.ficha-breadcrumb a {
    color: #1B2A4A;
    text-decoration: none;
}
.ficha-breadcrumb a:hover { text-decoration: underline; }

.ficha-badge {
    display: inline-block;
    background: #E07A5F;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    letter-spacing: .5px;
    margin-bottom: 10px;
}
.ficha-title {
    font-size: 28px;
    font-weight: 800;
    color: #1B2A4A;
    line-height: 1.25;
    margin: 0 0 16px;
}
.ficha-meta-bar {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding: 12px 16px;
    background: #f5f7fa;
    border-radius: 8px;
    font-size: 14px;
    color: #1B2A4A;
}
.ficha-meta-bar strong { color: #E07A5F; }

.ficha-img {
    width: 100%;
    max-height: 380px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 24px;
}

.ficha-block {
    border: 1px solid #e8edf2;
    border-radius: 10px;
    margin-bottom: 20px;
    overflow: hidden;
}
.ficha-block-header {
    background: #1B2A4A;
    color: #fff;
    padding: 10px 16px;
    font-weight: 700;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ficha-block-header img { filter: brightness(0) invert(1); }
.ficha-block-body {
    padding: 16px;
    font-size: 15px;
    line-height: 1.65;
    color: #333;
}
.ficha-block-body ul { margin: 8px 0 0 16px; padding: 0; }
.ficha-block-body li { margin-bottom: 4px; }

.bonif-badge-lg {
    display: inline-block;
    background: #6B8F71;
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    padding: 8px 20px;
    border-radius: 6px;
    margin-top: 10px;
    letter-spacing: 1px;
}

.ficha-objetivos {
    margin-bottom: 24px;
}
.ficha-objetivos h2 {
    font-size: 20px;
    color: #1B2A4A;
    margin-bottom: 10px;
    border-left: 4px solid #E07A5F;
    padding-left: 12px;
}
.ficha-objetivos-body {
    font-size: 15px;
    line-height: 1.7;
    color: #333;
}

.ficha-cta {
    background: #1B2A4A;
    color: #fff;
    border-radius: 12px;
    padding: 28px 24px;
    text-align: center;
    margin-top: 32px;
}
.ficha-cta h2 {
    font-size: 20px;
    margin: 0 0 8px;
    color: #fff;
}
.ficha-cta p { margin: 0 0 16px; color: #cdd5e0; font-size: 14px; }
.ficha-cta-btn {
    display: inline-block;
    background: #E07A5F;
    color: #fff;
    font-weight: 700;
    font-size: 15px;
    padding: 12px 28px;
    border-radius: 8px;
    text-decoration: none;
    transition: background .2s;
}
.ficha-cta-btn:hover { background: #c9674f; }

.ficha-faq { margin-top: 32px; }
.ficha-faq h2 {
    font-size: 20px;
    color: #1B2A4A;
    margin-bottom: 16px;
}
.ficha-faq-item {
    border-bottom: 1px solid #eee;
    padding: 12px 0;
}
.ficha-faq-q {
    font-weight: 700;
    color: #1B2A4A;
    font-size: 15px;
    margin-bottom: 6px;
}
.ficha-faq-a {
    font-size: 14px;
    color: #555;
    line-height: 1.6;
}
</style>

<div class="ficha-wrap">

  <!-- Breadcrumb -->
  <nav class="ficha-breadcrumb">
    <a href="/">Inicio</a> &rsaquo;
    <a href="<?= htmlspecialchars($catUrl) ?>"><?= htmlspecialchars($catNombreCorto) ?></a> &rsaquo;
    <?= htmlspecialchars($nombre) ?>
  </nav>

  <!-- Badge + Título -->
  <div class="ficha-badge">100% bonificable por FUNDAE</div>
  <h1 class="ficha-title"><?= htmlspecialchars($nombre) ?></h1>

  <!-- Meta bar -->
  <?php if ($data['horas']): ?>
  <div class="ficha-meta-bar">
    <div><strong><?= $data['horas'] ?> horas</strong> lectivas</div>
    <div>Diploma acreditativo</div>
    <div>100% online</div>
    <div>Modalidad flexible</div>
  </div>
  <?php endif; ?>

  <!-- Imagen -->
  <img class="ficha-img"
       src="<?= htmlspecialchars($imagen) ?>"
       alt="<?= htmlspecialchars($nombre) ?>"
       width="900" height="380">

  <!-- Objetivos -->
  <?php if ($data['objetivo']): ?>
  <div class="ficha-objetivos">
    <h2>Objetivos del curso</h2>
    <div class="ficha-objetivos-body"><?= $data['objetivo'] ?></div>
  </div>
  <?php endif; ?>

  <!-- Bonificación -->
  <div class="ficha-block">
    <div class="ficha-block-header">
      <img src="<?= IMG_BASE ?>comunes/Bonificacion.png" alt="" width="20" height="20">
      Bonificación FUNDAE
    </div>
    <div class="ficha-block-body">
      <p>Este curso es <strong>100% bonificable</strong> para empresas a través de FUNDAE.
         El coste se recupera íntegramente mediante descuento en los seguros sociales.</p>
      <span class="bonif-badge-lg">BONIFICACIÓN 100%</span>
    </div>
  </div>

  <!-- Duración -->
  <div class="ficha-block">
    <div class="ficha-block-header">
      <img src="<?= IMG_BASE ?>comunes/Tiempo.png" alt="" width="20" height="20">
      Duración y Plazo
    </div>
    <div class="ficha-block-body">
      <p>Duración: <strong><?= $data['horas'] ? $data['horas'] . ' horas lectivas' : 'consultar' ?></strong>.
         El plazo para completarlo es flexible. En cursos bonificados se debe comunicar a FUNDAE
         con al menos 7 días de antelación a la fecha de inicio.</p>
    </div>
  </div>

  <!-- Modalidades -->
  <div class="ficha-block">
    <div class="ficha-block-header">
      <img src="<?= IMG_BASE ?>comunes/modalidades.png" alt="" width="20" height="20">
      Modalidades de impartición
    </div>
    <div class="ficha-block-body">
      <ul>
        <li>Teleformación con asistencia de profesor (acceso 24h)</li>
        <li>Clases en vivo — Aula Virtual</li>
        <li>Modalidad MIXTA: combinación de ambas</li>
      </ul>
      <p>Todas las modalidades son bonificables al 100% a través de FUNDAE.</p>
    </div>
  </div>

  <!-- Diplomas -->
  <div class="ficha-block">
    <div class="ficha-block-header">
      <img src="<?= IMG_BASE ?>comunes/Certificate512_44186.png" alt="" width="20" height="20">
      Diplomas acreditativos
    </div>
    <div class="ficha-block-body" style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
      <a href="<?= IMG_BASE ?>comunes/Diploma-Ciberaula.pdf" target="_blank" rel="noopener">
        <img src="<?= IMG_BASE ?>comunes/Diploma-generico-2023.png" alt="Diploma Ciberaula" width="80" style="border:1px solid #ddd;border-radius:4px;">
      </a>
      <a href="<?= IMG_BASE ?>comunes/Diploma-acreditativo.pdf" target="_blank" rel="noopener">
        <img src="<?= IMG_BASE ?>comunes/Diploma-acreditativo-de-generico.png" alt="Diploma FUNDAE" width="80" style="border:1px solid #ddd;border-radius:4px;">
      </a>
      <div style="font-size:14px;color:#555;">
        Al finalizar el curso recibirás un diploma acreditativo de Ciberaula
        y el diploma FUNDAE de formación bonificada.
      </div>
    </div>
  </div>

  <!-- FAQ -->
  <?php if (!empty($faqs)): ?>
  <div class="ficha-faq">
    <h2>Preguntas frecuentes sobre este curso</h2>
    <?php foreach ($faqs as $faq): ?>
    <div class="ficha-faq-item">
      <div class="ficha-faq-q"><?= htmlspecialchars($faq['q']) ?></div>
      <div class="ficha-faq-a"><?= htmlspecialchars($faq['a']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- CTA contacto -->
  <div class="ficha-cta">
    <h2>¿Quieres más información?</h2>
    <p>Solicita presupuesto sin compromiso para tu empresa</p>
    <a href="/m/contacto.php" class="ficha-cta-btn">Solicitar información</a>
  </div>

</div>

<?php require __DIR__ . '/m/footer.php'; ?>
