<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Cursos Bonificados Online | Ciberaula' ?></title>
<?php if (!empty($pageDesc)): ?>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= htmlspecialchars($pageCanonical ?? ('https://www.ciberaula.org' . $_SERVER['REQUEST_URI'])) ?>">
<?php
// Normalizar og:image: siempre debe usar www.ciberaula.org (nunca dev)
$ogImage = !empty($pageImage)
    ? str_replace('https://dev.ciberaula.org', 'https://www.ciberaula.org', $pageImage)
    : '';
?>
<?php // Open Graph ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'Cursos Bonificados Online | Ciberaula') ?>">
<?php if (!empty($pageDesc)): ?>
<meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
<?php endif; ?>
<meta property="og:url" content="<?= htmlspecialchars($pageCanonical ?? ('https://www.ciberaula.org' . $_SERVER['REQUEST_URI'])) ?>">
<?php if ($ogImage): ?>
<meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
<meta property="og:image:width" content="800">
<meta property="og:image:height" content="400">
<?php endif; ?>
<meta property="og:site_name" content="Ciberaula">
<meta property="og:locale" content="es_ES">
<?php // Twitter Card ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle ?? 'Cursos Bonificados Online | Ciberaula') ?>">
<?php if (!empty($pageDesc)): ?>
<meta name="twitter:description" content="<?= htmlspecialchars($pageDesc) ?>">
<?php endif; ?>
<?php if ($ogImage): ?>
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= BASE_URL ?>styles.css?v=12">
<?php
// Schema JSON-LD inyectado desde cada página
if (!empty($pageSchemas)) {
    foreach ($pageSchemas as $schema) {
        echo $schema . "\n";
    }
}
?>
</head>
<body>

<header class="header">
  <div class="header-inner">
    <a href="<?= BASE_URL ?>" class="header-logo-link">
      <img class="header-logo" src="https://www.ciberaula.com/assets/img/logo_opt.png" alt="<?= SITE_NAME ?>" width="200" height="80" onerror="this.onerror=null;this.parentElement.innerHTML='<span class=header-logo-text>Ciberaula</span>';">
    </a>
    <div class="header-cta">
      <a href="tel:<?= SITE_PHONE_INTL ?>" class="btn-cta btn-phone">Llamar</a>
      <a href="https://wa.me/<?= SITE_WA ?>?text=Hola,%20quisiera%20informacion%20sobre%20cursos%20bonificados" class="btn-cta btn-wa">WhatsApp</a>
    </div>
  </div>
</header>

<nav class="navbar">
  <div class="navbar-inner">
    <div class="nav-search-wrap">
      <input type="search" class="nav-search" id="navSearch" placeholder="Buscar cursos..." autocomplete="off">
      <div class="nav-search-results" id="navSearchResults"></div>
    </div>
    <button class="nav-cat-btn" id="navCatBtn" type="button">
      Categorias &#9662;
    </button>
  </div>
</nav>

<div id="navModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:500;overflow-y:auto;padding:50px 12px 12px">
  <div style="background:#fff;border-radius:12px;max-width:500px;margin:0 auto;max-height:calc(100vh - 70px);overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,0.2)">
    <div style="padding:14px 16px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
      <strong style="font-size:16px;color:#1B2A4A">Categorias</strong>
      <button id="navModalClose" type="button" style="background:none;border:none;font-size:22px;cursor:pointer;color:#999">&#10005;</button>
    </div>
    <div id="navModalList" style="padding:0"></div>
  </div>
</div>

<script>
(function(){
  var modal = document.getElementById('navModal');
  var list = document.getElementById('navModalList');
  var loaded = false;

  document.getElementById('navCatBtn').addEventListener('click', function(){
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    if (!loaded) {
      list.innerHTML = '<div style="padding:20px;text-align:center;color:#999">Cargando...</div>';
      fetch('<?= BASE_URL ?>categorias_json.php')
        .then(function(r){ return r.json(); })
        .then(function(data){
          var h = '';
          data.forEach(function(c){
            h += '<a href="' + c.url + '" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid #f0f0f0;color:#1B2A4A;font-weight:600;font-size:14px;text-decoration:none">';
            h += c.nombre;
            h += '<span style="background:#e8f5e9;color:#2e7d32;font-size:11px;padding:2px 8px;border-radius:10px;font-weight:700">' + c.n + '</span>';
            h += '</a>';
          });
          list.innerHTML = h;
          loaded = true;
        })
        .catch(function(){ list.innerHTML = '<div style="padding:20px;text-align:center;color:#c00">Error al cargar</div>'; });
    }
  });

  function closeModal(){ modal.style.display = 'none'; document.body.style.overflow = ''; }
  document.getElementById('navModalClose').addEventListener('click', closeModal);
  modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

  var si = document.getElementById('navSearch');
  var sr = document.getElementById('navSearchResults');
  var timer = null;
  si.addEventListener('input', function(){
    clearTimeout(timer);
    var q = this.value.trim();
    if (q.length < 2) { sr.innerHTML = ''; sr.style.display = 'none'; return; }
    timer = setTimeout(function(){
      fetch('<?= BASE_URL ?>buscar.php?q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (!data.length) {
            sr.innerHTML = '<div class="sr-empty">Sin resultados para "' + q.replace(/</g,'&lt;') + '"</div>';
            sr.style.display = 'block';
            return;
          }
          var h = '';
          data.forEach(function(item){
            var isCat = item.tipo === 'cat';
            var icon   = isCat ? '📂' : '📖';
            var badge  = isCat
              ? '<span class="sr-badge-cat">Categoría</span>'
              : '<span class="sr-badge-curso">Curso bonificado</span>';
            var meta   = item.horas ? '<span class="sr-meta">&#8987; ' + item.horas + ' h</span>' : '';
            h += '<a href="' + item.url + '" class="sr-item">';
            h +=   '<div class="sr-icon sr-icon-' + item.tipo + '">' + icon + '</div>';
            h +=   '<div class="sr-body">';
            h +=     '<div class="sr-name">' + item.nombre + '</div>';
            h +=     '<div class="sr-footer">' + badge + meta + '</div>';
            h +=   '</div>';
            h +=   '<span class="sr-arrow">›</span>';
            h += '</a>';
          });
          sr.innerHTML = h;
          sr.style.display = 'block';
        })
        .catch(function(){ sr.innerHTML = ''; sr.style.display = 'none'; });
    }, 300);
  });
  document.addEventListener('click', function(e){ if (!si.contains(e.target) && !sr.contains(e.target)) sr.style.display = 'none'; });
})();
</script>
