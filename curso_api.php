<?php
/**
 * curso_api.php - API interna para fichas de curso desktop
 * Recibe: ?shortname=X o ?id=X
 * Devuelve: JSON con html listo para inyectar en course/view.php
 * Solo lectura. Sin autenticacion (datos publicos).
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/m/config.php';

$shortname = isset($_GET['shortname']) ? trim($_GET['shortname']) : '';
$id        = isset($_GET['id'])        ? (int)$_GET['id']         : 0;

if (!$shortname && !$id) {
    echo json_encode(['error' => 'Parametro requerido: shortname o id']);
    exit;
}

$db = db();

if ($shortname) {
    $stmt = $db->prepare("
        SELECT c.id, c.fullname, c.shortname, c.summary, c.category,
               cat.name AS cat_name
        FROM mdl_course c
        LEFT JOIN mdl_course_categories cat ON cat.id = c.category
        WHERE c.shortname = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $shortname);
} else {
    $stmt = $db->prepare("
        SELECT c.id, c.fullname, c.shortname, c.summary, c.category,
               cat.name AS cat_name
        FROM mdl_course c
        LEFT JOIN mdl_course_categories cat ON cat.id = c.category
        WHERE c.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $id);
}

$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(['error' => 'Curso no encontrado']);
    exit;
}

$data    = extract_course_data($row['summary']);
$faqs    = get_faq_curso($row['fullname'], $data['horas']);
$cat_url = cat_url($row['category']);
$cat_name = clean_cat_name($row['cat_name']);
$slug    = $row['shortname'];
$titulo  = htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8');

// Imagen: del summary, o fallback imagen de categoria
$imagen = $data['imagen'];
if (!$imagen) {
    // Intentar hero de categoria
    $res = $db->query("SELECT description FROM mdl_course_categories WHERE id = " . (int)$row['category']);
    if ($res && $cat = $res->fetch_assoc()) {
        $imagen = extract_hero_url($cat['description']);
    }
}
if (!$imagen) {
    $imagen = 'https://www.ciberaula.org/images/general/formacion-bonificada.jpg';
}

// Diplomas
$diplomas = [];
if (preg_match_all('/<a[^>]+href="([^"]+\.pdf[^"]*)"[^>]*>(.*?)<\/a>/is', $row['summary'], $dm)) {
    foreach ($dm[1] as $i => $url) {
        $diplomas[] = ['url' => $url, 'label' => trim(strip_tags($dm[2][$i])) ?: 'Diploma acreditativo'];
    }
}

// Modalidades
$modalidades = [];
if (preg_match('/<ul[^>]*class="[^"]*modalidades[^"]*"[^>]*>(.*?)<\/ul>/is', $row['summary'], $mm)) {
    preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $mm[1], $li);
    foreach ($li[1] as $item) {
        $modalidades[] = trim(strip_tags($item));
    }
}
if (empty($modalidades)) {
    $modalidades = [
        'Teleformación con asistencia de profesor (acceso 24h)',
        'Clases en vivo — Aula Virtual',
        'Modalidad MIXTA: combinación de ambas',
    ];
}

// Construir HTML
ob_start();
?>
<div id="cb-ficha-curso">

  <!-- Breadcrumb -->
  <nav class="cb-breadcrumb">
    <a href="https://www.ciberaula.org/">Inicio</a> &rsaquo;
    <a href="<?= htmlspecialchars($cat_url) ?>"><?= htmlspecialchars($cat_name) ?></a> &rsaquo;
    <span><?= $titulo ?></span>
  </nav>

  <!-- Badge -->
  <span class="cb-badge">100% bonificable por FUNDAE</span>

  <!-- Título -->
  <h1 class="cb-titulo">Curso online <?= $titulo ?></h1>

  <!-- Imagen hero -->
  <?php if ($imagen): ?>
  <div class="cb-hero-wrap">
    <img src="<?= htmlspecialchars($imagen) ?>" alt="<?= $titulo ?>" class="cb-hero-img" loading="lazy">
  </div>
  <?php endif; ?>

  <!-- Meta bar -->
  <div class="cb-meta-bar">
    <?php if ($data['horas']): ?>
    <div class="cb-meta-item">
      <span class="cb-meta-icon">&#9201;</span>
      <strong><?= (int)$data['horas'] ?> horas</strong>
    </div>
    <?php endif; ?>
    <div class="cb-meta-item">
      <span class="cb-meta-icon">&#127891;</span>
      <strong>Diploma FUNDAE</strong>
    </div>
    <div class="cb-meta-item">
      <span class="cb-meta-icon">&#128187;</span>
      <strong>Online</strong>
    </div>
    <div class="cb-meta-item">
      <span class="cb-meta-icon">&#128197;</span>
      <strong>Plazo flexible</strong>
    </div>
  </div>

  <!-- Objetivos -->
  <?php if ($data['objetivo']): ?>
  <div class="cb-bloque cb-objetivos">
    <h2>Objetivos del curso</h2>
    <div class="cb-objetivos-body"><?= $data['objetivo'] ?></div>
  </div>
  <?php endif; ?>

  <!-- Bonificación FUNDAE -->
  <div class="cb-bloque cb-bonificacion">
    <h3><span class="cb-icon-b">&#127891;</span> Bonificación FUNDAE</h3>
    <p>Este curso es <strong>100% bonificable</strong> para empresas a través de FUNDAE.
       El coste se recupera íntegramente mediante descuento en los seguros sociales.</p>
    <a href="https://www.ciberaula.org/como-acceder-a-la-formacion-bonificada" class="cb-btn-boni">BONIFICACIÓN 100%</a>
  </div>

  <!-- Duración -->
  <div class="cb-bloque cb-duracion">
    <h3><span class="cb-icon-b">&#9201;</span> Duración y Plazo</h3>
    <p>Duración: <strong><?= $data['horas'] ? (int)$data['horas'] . ' horas lectivas' : 'consultar' ?></strong>.
       El plazo para completarlo es flexible. En cursos bonificados se debe comunicar a FUNDAE con al menos 7 días de antelación a la fecha de inicio.</p>
  </div>

  <!-- Modalidades -->
  <div class="cb-bloque cb-modalidades">
    <h3><span class="cb-icon-b">&#128187;</span> Modalidades de impartición</h3>
    <ul>
      <?php foreach ($modalidades as $mod): ?>
      <li><?= htmlspecialchars($mod) ?></li>
      <?php endforeach; ?>
    </ul>
    <p>Todas las modalidades son bonificables al 100% a través de FUNDAE.</p>
  </div>

  <!-- Diplomas -->
  <?php if (!empty($diplomas)): ?>
  <div class="cb-bloque cb-diplomas">
    <h3><span class="cb-icon-b">&#127891;</span> Diploma acreditativo</h3>
    <p>Al finalizar el curso recibirás un diploma con sello oficial FUNDAE.</p>
    <?php foreach ($diplomas as $dip): ?>
    <a href="<?= htmlspecialchars($dip['url']) ?>" class="cb-diploma-link" target="_blank">&#128196; <?= htmlspecialchars($dip['label']) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- FAQ -->
  <div class="cb-bloque cb-faq">
    <h2>Preguntas frecuentes</h2>
    <?php foreach ($faqs as $faq): ?>
    <details class="cb-faq-item">
      <summary><?= htmlspecialchars($faq['q']) ?></summary>
      <p><?= htmlspecialchars($faq['a']) ?></p>
    </details>
    <?php endforeach; ?>
  </div>

  <!-- CTA contacto -->
  <div class="cb-bloque cb-cta">
    <h3>¿Quieres matricularte en este curso?</h3>
    <p>Llámanos o escríbenos y te gestionamos la bonificación sin coste adicional.</p>
    <div class="cb-cta-btns">
      <a href="tel:911976752" class="cb-btn-llamar">&#128222; Llamar</a>
      <a href="https://wa.me/34689308141" class="cb-btn-wa" target="_blank">&#128172; WhatsApp</a>
    </div>
  </div>

</div>
<?php
$html = ob_get_clean();

echo json_encode([
    'ok'       => true,
    'shortname'=> $row['shortname'],
    'titulo'   => $row['fullname'],
    'html'     => $html,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
