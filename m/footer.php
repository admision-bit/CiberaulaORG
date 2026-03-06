<footer class="footer">
  <div class="footer-inner">
    <div class="footer-brand">Ciberaula</div>
    <div class="footer-tagline">Formación online bonificada para empresas · Desde 1997</div>
    <div class="footer-contact">
      <a href="tel:<?= SITE_PHONE_INTL ?>"><?= SITE_PHONE ?></a>
      <span>|</span>
      <a href="https://wa.me/<?= SITE_WA ?>">WhatsApp</a>
      <span>|</span>
      <a href="mailto:admision@ciberaula.com">admision@ciberaula.com</a>
    </div>
    <div class="footer-address">
      Paseo de la Castellana 91, 4ª planta · 28046 Madrid
    </div>
    <div class="footer-fundae">Entidad organizadora autorizada por FUNDAE</div>
    <div class="footer-links">
      <a href="<?= BASE_URL ?>">Catálogo de cursos</a>
      <a href="<?= BASE_URL ?>contacto.php">Contacto</a>
      <a href="https://www.ciberaula.com" rel="noopener">ciberaula.com</a>
    </div>
    <div class="footer-copy">&copy; <?= date('Y') ?> Ciberaula de Formación Online S.L. Todos los derechos reservados.</div>
  </div>
</footer>

<!-- BOTÓN FLOTANTE DE CONTACTO -->
<a href="<?= BASE_URL ?>contacto.php?origen=<?= urlencode($_SERVER['REQUEST_URI'] ?? '') ?>" class="fab-contacto" id="fabContacto" aria-label="Solicitar información">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
  </svg>
  Solicitar info
</a>

<script>
(function(){
  // Ocultar FAB en la página de contacto
  var path = window.location.pathname;
  if (path.indexOf('contacto.php') !== -1) {
    var fab = document.getElementById('fabContacto');
    if (fab) fab.style.display = 'none';
  }

  // Pasar la URL de origen en el enlace del FAB
  var fab = document.getElementById('fabContacto');
  if (fab) {
    var origen = encodeURIComponent(window.location.href);
    fab.href = '<?= BASE_URL ?>contacto.php?fab=1&origen=' + origen;
  }
})();
</script>

</body>
</html>
