<?php
/**
 * Ficha de Curso v9 - Catálogo Móvil Ciberaula
 * FAQ de curso + Schema JSON-LD (Course, FAQPage, BreadcrumbList)
 * v9: cursoUrl usa URL amigable /curso-bonificado-de-[shortname]
 *     acepta ?id= o ?slug= como entrada
 */
require __DIR__ . '/config.php';
require __DIR__ . '/seo.php';

$db = db();

// Acepta ?id= o ?slug=
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
    if (!$cursoId) { header('Location: ' . BASE_URL); exit; }
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
if (!$curso) { header('Location: ' . BASE_URL); exit; }

$nombre = plain_text($curso['fullname']);

// Eliminar prefijo redundante si el fullname ya incluye "Curso bonificado de"
// para evitar títulos duplicados como "Curso bonificado de Curso bonificado de X"
$prefijos = [
    'Curso bonificado de ',
    'Curso Bonificado de ',
    'Curso online de ',
    'Curso Online de ',
];
$nombreCorto = $nombre;
foreach ($prefijos as $p) {
    if (stripos($nombreCorto, $p) === 0) {
        $nombreCorto = substr($nombreCorto, strlen($p));
        break;
    }
}

$catNombre = clean_cat_name($curso['cat_name']);
$catNombreCorto = short_cat_name($curso['cat_name']);
$data = extract_course_data($curso['summary']);

$imagen = $data['imagen'];
if (!$imagen && !empty($curso['cat_desc'])) {
    $imagen = extract_hero_url($curso['cat_desc']);
}
if (!$imagen) {
    $imagen = IMG_BASE . 'comunes/CursosOnlineRedesSocialesORG.jpg';
}

// URL amigable canónica del curso
$shortname = $curso['shortname'];
$cursoUrl = $shortname
    ? 'https://www.ciberaula.org/curso-bonificado-de-' . $shortname
    : 'https://www.ciberaula.org/m/curso.php?id=' . $curso['id'];

$shareUrl = $cursoUrl;

// URL canónica de la categoría (amigable)
$catUrl = cat_url($curso['category']);

// Título de página: usar el fullname completo si ya tiene prefijo, o añadirlo si no lo tiene
$pageTitle = (stripos($nombre, 'Curso bonificado') === 0 || stripos($nombre, 'Curso online') === 0)
    ? $nombre
    : 'Curso bonificado de ' . $nombre;

$pageDesc = $data['objetivo'] ? mb_substr(strip_tags($data['objetivo']), 0, 155) : 'Curso bonificado de ' . $nombreCorto . '. 100% bonificable por FUNDAE.';
$pageImage = $imagen;

// FAQ de curso — usar nombreCorto para las preguntas (sin prefijo)
$faqs = get_faq_curso($nombreCorto, $data['horas']);

// Schema JSON-LD
$pageSchemas = [
    schema_course($pageTitle, $data['objetivo'] ?: $pageDesc, $data['horas'], $cursoUrl, $imagen),
    schema_faqpage($faqs),
    schema_breadcrumbs([
        ['name' => 'Inicio', 'url' => 'https://www.ciberaula.org' . BASE_URL],
        ['name' => $catNombre, 'url' => $catUrl],
        ['name' => $pageTitle, 'url' => $cursoUrl],
    ]),
];

require __DIR__ . '/header.php';
?>

<nav class="breadcrumb">
  <a href="<?= BASE_URL ?>">Inicio</a> &rsaquo;
  <a href="<?= BASE_URL ?>categoria.php?id=<?= $curso['category'] ?>"><?= htmlspecialchars($catNombreCorto) ?></a> &rsaquo;
  Curso
</nav>

<div class="curso-header">
  <span class="curso-badge">100% bonificable por FUNDAE</span>
  <h1 class="curso-title"><?= htmlspecialchars($pageTitle) ?></h1>
</div>

<?php if ($data['horas']): ?>
<div class="curso-meta-bar">
  <div class="curso-meta-item"><strong><?= $data['horas'] ?> horas</strong> lectivas</div>
  <div class="curso-meta-item">Diploma acreditativo</div>
  <div class="curso-meta-item">100% online</div>
</div>
<?php endif; ?>

<div class="curso-img-wrap">
  <img class="curso-img" src="<?= htmlspecialchars($imagen) ?>" alt="<?= htmlspecialchars($pageTitle) ?>" width="800" height="400">
</div>

<!-- Botones de compartir -->
<div class="share-bar">
  <span class="share-label">Compartir:</span>
  <a href="https://wa.me/?text=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener" class="share-btn share-wa" title="WhatsApp">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
  </a>
  <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($cursoUrl) ?>" target="_blank" rel="noopener" class="share-btn share-li" title="LinkedIn">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
  </a>
  <a href="mailto:?subject=<?= rawurlencode('Curso: ' . $pageTitle . ' - Ciberaula') ?>&body=<?= rawurlencode('Te comparto este curso bonificable por FUNDAE:' . "\n\n" . $pageTitle . "\n" . $cursoUrl) ?>" class="share-btn share-email" title="Email">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
  </a>
  <button class="share-btn share-copy" title="Copiar enlace" onclick="navigator.clipboard.writeText('<?= $cursoUrl ?>');this.title='Copiado!'">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
  </button>
</div>

<div class="info-block">
  <div class="info-block-header">
    <img src="<?= IMG_BASE ?>comunes/Bonificacion.png" alt="" width="24" height="24"> Bonificación FUNDAE
  </div>
  <div class="info-block-body">
    <p>Este curso es <strong>100% bonificable</strong> para empresas a través de FUNDAE. El coste se recupera mediante descuento en los seguros sociales.</p>
    <div class="bonif-badge">BONIFICACIÓN 100%</div>
  </div>
</div>

<div class="info-block">
  <div class="info-block-header">
    <img src="<?= IMG_BASE ?>comunes/modalidades.png" alt="" width="24" height="24"> Modalidades
  </div>
  <div class="info-block-body">
    <ul>
      <li>Teleformación con asistencia de profesor (<?= $data['horas'] ? $data['horas'] . ' hs.' : '24 hs.' ?>)</li>
      <li>Clases en vivo - Aula Virtual</li>
      <li>Modalidad MIXTA: combinación de ambas</li>
    </ul>
  </div>
</div>

<?php if ($data['objetivo']): ?>
<div class="curso-desc">
  <h2>Objetivos del curso</h2>
  <?= $data['objetivo'] ?>
</div>
<?php endif; ?>

<?php
// FAQ del curso
render_faq($faqs, 'Preguntas frecuentes sobre este curso');
?>

<div class="curso-contact">
  <div class="curso-contact-title">¿Quieres más información?</div>
  <p>Solicita presupuesto sin compromiso para tu empresa</p>
  <a href="<?= BASE_URL ?>contacto.php" class="contact-form-btn">Solicitar información</a>
</div>

<?php require __DIR__ . '/footer.php'; ?>
