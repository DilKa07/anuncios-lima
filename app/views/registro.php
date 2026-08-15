<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <div class="auth-shell">
    <div class="auth-card auth-card-register">
      <p class="auth-callout">
        Crea tu cuenta para empezar a publicar anuncios en minutos. Con tu perfil podras editar tus avisos,
        mantener tus datos de contacto actualizados y ganar mas visibilidad en cada publicacion.
      </p>
      <h2>Registro</h2>
      <?php if (!empty($errors)) foreach ($errors as $e) echo '<div class="auth-error">'.htmlspecialchars($e).'</div>'; ?>
      <form method="post" action="/registro" class="auth-form">
      <?php echo csrf_field(); ?>
      <label>Nombre de usuario</label>
      <input type="text" name="username" required>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Contrasena</label>
      <div class="auth-password-wrap">
        <input type="password" name="password" id="register_password" required>
        <button type="button" class="auth-password-toggle" data-target="register_password" aria-label="Mostrar u ocultar contraseña">&#128065;</button>
      </div>
      <div class="auth-actions">
        <button class="btn-primary" type="submit">Crear cuenta</button>
        <a class="btn-outline" href="/login">Iniciar sesion</a>
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