<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <div class="auth-shell">
    <div class="auth-card">
      <h2>Recuperar contrasena</h2>
      <p class="auth-note">Ingresa tu email y te enviaremos un enlace para crear una nueva contrasena.</p>

      <?php if (!empty($success)): ?>
        <div class="auth-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $e): ?>
          <div class="auth-error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <form method="post" action="/forgot-password" class="auth-form">
        <?php echo csrf_field(); ?>
        <label>Email</label>
        <input type="email" name="email" required>

        <div class="auth-actions">
          <button class="btn-primary" type="submit">Enviar enlace</button>
          <a class="btn-outline" href="/login">Volver a login</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
