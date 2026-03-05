<?php
/**
 * Portada del Catálogo Móvil Ciberaula v4
 * Contenido institucional + bonificación + catálogo de categorías
 * FAQ + Schema JSON-LD (Organization, FAQPage, BreadcrumbList)
 */
require __DIR__ . '/config.php';
require __DIR__ . '/seo.php';

$pageTitle = 'Cursos Bonificados Online | Ciberaula';
$pageDesc = 'Ciberaula: desde 1997 formando empresas. Más de 1.500 cursos bonificados por FUNDAE, 100% online, con diploma acreditativo oficial. Consulte su crédito de formación.';
$pageImage = 'https://www.ciberaula.com/assets/img/logo_opt.png';

$faqs = get_faq_portada();

$pageSchemas = [
    schema_organization(),
    schema_faqpage($faqs),
    schema_breadcrumbs([
        ['name' => 'Inicio', 'url' => 'https://www.ciberaula.org' . BASE_URL],
    ]),
];

// --- Obtener categorias con cursos ---
$db = db();
$sql = "SELECT c.id, c.name, c.description, COUNT(co.id) as num_cursos
        FROM mdl_course_categories c
        INNER JOIN mdl_course co ON co.category = c.id AND co.visible = 1
        WHERE c.visible = 1 AND c.description LIKE '%cat-page%'
        GROUP BY c.id
        ORDER BY num_cursos DESC";
$result = $db->query($sql);
$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

require __DIR__ . '/header.php';
?>

<!-- HERO INSTITUCIONAL -->
<section class="hero-home">
  <h1>Formación bonificada para empresas</h1>
  <p class="hero-sub">Más de <strong>1.500 cursos</strong> · 100% bonificables por FUNDAE</p>
  <div class="hero-ctas">
    <a href="tel:<?= SITE_PHONE_INTL ?>" class="hero-btn hero-btn-primary"><?= SITE_PHONE ?></a>
    <a href="<?= BASE_URL ?>contacto.php" class="hero-btn hero-btn-secondary">Solicitar información</a>
  </div>
</section>

<!-- QUIÉNES SOMOS -->
<section class="about-section">
  <h2>Ciberaula: 28 años formando empresas</h2>
  <p>Desde 1997 somos referencia en formación corporativa online en España. Hemos formado a trabajadores de más de <strong>8.000 empresas</strong>, desde pymes hasta grandes corporaciones del IBEX 35.</p>
  <p>Nuestro compromiso es sencillo: facilitar a las empresas el acceso a formación de calidad, gestionando todo el proceso de bonificación FUNDAE de principio a fin, sin coste ni complicaciones.</p>
  <div class="about-stats">
    <div class="stat-item">
      <span class="stat-num">1997</span>
      <span class="stat-label">Desde</span>
    </div>
    <div class="stat-item">
      <span class="stat-num">+1.500</span>
      <span class="stat-label">Cursos</span>
    </div>
    <div class="stat-item">
      <span class="stat-num">+8.000</span>
      <span class="stat-label">Empresas</span>
    </div>
  </div>
</section>

<!-- BONIFICACIÓN -->
<section class="bonif-section">
  <h2>¿Qué es la formación bonificada?</h2>
  <p>Todas las empresas disponen de un <strong>crédito anual</strong> para formar a sus trabajadores sin coste, a través de FUNDAE. Si no lo utilizan, lo pierden al finalizar el año.</p>
  <div class="bonif-steps">
    <div class="bonif-step">
      <span class="step-icon">1</span>
      <strong>Elija los cursos</strong>
      <span>De nuestro catálogo de +1.500 cursos</span>
    </div>
    <div class="bonif-step">
      <span class="step-icon">2</span>
      <strong>Nosotros gestionamos</strong>
      <span>Toda la tramitación con FUNDAE</span>
    </div>
    <div class="bonif-step">
      <span class="step-icon">3</span>
      <strong>Coste cero</strong>
      <span>Se recupera en los seguros sociales</span>
    </div>
  </div>
</section>

<!-- DIPLOMA -->
<section class="diploma-section">
  <h2>Diploma acreditativo oficial</h2>
  <p>Al completar cada curso, el alumno recibe un <strong>diploma con el sello de FUNDAE</strong> que acredita la formación recibida y las horas lectivas cursadas.</p>
  <p>Este diploma tiene <strong>reconocimiento institucional</strong> y es <strong>puntuable</strong> en numerosos tribunales de oposiciones y concursos públicos, además de válido para auditorías de formación y procesos de selección profesional.</p>
</section>

<!-- CATÁLOGO DE CATEGORÍAS -->
<section class="catalog-section">
  <h2>Explore nuestro catálogo</h2>
  <p class="catalog-intro"><?= count($categorias) ?> áreas de formación con cursos 100% bonificables</p>
  <div class="cat-grid" id="grid">
    <?php foreach ($categorias as $cat):
      $nombre = clean_cat_name($cat['name']);
      $heroUrl = extract_hero_url($cat['description']);
      if (!$heroUrl) $heroUrl = IMG_BASE . 'comunes/CursosOnlineRedesSocialesORG.jpg';
    ?>
    <a href="<?= cat_url($cat['id']) ?>" class="cat-card" data-name="<?= htmlspecialchars(mb_strtolower($nombre)) ?>">
      <img src="<?= htmlspecialchars($heroUrl) ?>" alt="<?= htmlspecialchars($nombre) ?>" loading="lazy" width="400" height="200">
      <div class="cat-card-body">
        <div class="cat-card-title"><?= htmlspecialchars($nombre) ?></div>
        <div class="cat-card-count"><?= $cat['num_cursos'] ?> cursos</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<?php
render_faq($faqs, 'Preguntas frecuentes sobre formación bonificada');
?>

<!-- CTA FINAL -->
<section class="cta-final">
  <div class="cta-final-title">¿Quiere saber cuánto crédito tiene su empresa?</div>
  <p>Le informamos sin compromiso sobre su bonificación disponible</p>
  <a href="<?= BASE_URL ?>contacto.php" class="contact-form-btn">Consultar crédito FUNDAE</a>
  <a href="tel:<?= SITE_PHONE_INTL ?>" class="cta-phone-link">o llámenos al <?= SITE_PHONE ?></a>
</section>

<?php require __DIR__ . '/footer.php'; ?>