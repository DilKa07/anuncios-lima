<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <div class="auth-shell">
    <div class="auth-card">
      <p class="auth-callout">
        Para publicar en Anuncios Lima primero debes reguistrarte como nuevo usuario. Hazlo ahora y podras administrar tus avisos,
        destacar tus publicaciones y recibir contactos reales de forma rapida y segura.
      </p>
      <h2>Iniciar Sesion</h2>
      <?php if (!empty($flashSuccess)) echo '<div class="auth-success">'.htmlspecialchars($flashSuccess).'</div>'; ?>
      <?php if (!empty($flashWarning)) echo '<div class="auth-error">'.htmlspecialchars($flashWarning).'</div>'; ?>
      <?php if (!empty($errors)) foreach ($errors as $e) echo '<div class="auth-error">'.htmlspecialchars($e).'</div>'; ?>
      <form method="post" action="/login" class="auth-form" autocomplete="on">
      <?php echo csrf_field(); ?>
      <label>Email</label>
      <input type="email" name="email" autocomplete="username" required>
      <label>Contrasena</label>
      <div class="auth-password-wrap">
        <input type="password" name="password" id="login_password" autocomplete="current-password" required>
        <button type="button" class="auth-password-toggle" data-target="login_password" aria-label="Mostrar u ocultar contraseña">&#128065;</button>
      </div>
      <div class="auth-actions">
        <button class="btn-primary" type="submit">Entrar</button>
        <a class="btn-outline" href="/registro">Registrate ahora</a>
      </div>
      <div style="margin-top:10px;">
        <a href="/olvido-password" style="font-size:14px;color:#0f5cc0;text-decoration:none;">Olvide mi contrasena</a>
      </div>
    </form>
  </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.auth-password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-target'));
      if (!input) return;
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.classList.toggle('is-visible', isPassword);
    });
  });
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>