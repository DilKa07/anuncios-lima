<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <div class="auth-shell">
    <div class="auth-card">
      <h2>Nueva contrasena</h2>

      <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $e): ?>
          <div class="auth-error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (empty($user)): ?>
        <p class="auth-note">El enlace de recuperacion no es valido o ya vencio.</p>
        <div class="auth-actions">
          <a class="btn-outline" href="/forgot-password">Solicitar nuevo enlace</a>
          <a class="btn-outline" href="/login">Ir a login</a>
        </div>
      <?php else: ?>
        <form method="post" action="/reset-password" class="auth-form">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">

          <label>Nueva contrasena</label>
          <div class="auth-password-wrap">
            <input type="password" name="password" id="reset_password" required minlength="6">
            <button type="button" class="auth-password-toggle" data-target="reset_password" aria-label="Mostrar u ocultar contraseña">&#128065;</button>
          </div>

          <label>Confirmar nueva contrasena</label>
          <div class="auth-password-wrap">
            <input type="password" name="password_confirm" id="reset_password_confirm" required minlength="6">
            <button type="button" class="auth-password-toggle" data-target="reset_password_confirm" aria-label="Mostrar u ocultar contraseña">&#128065;</button>
          </div>

          <div class="auth-actions">
            <button class="btn-primary" type="submit">Guardar contrasena</button>
            <a class="btn-outline" href="/login">Cancelar</a>
          </div>
        </form>
      <?php endif; ?>
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
