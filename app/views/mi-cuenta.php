<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <div class="auth-shell">
    <div class="auth-card auth-account-card">
      <?php $isAdminView = is_admin_user($conexion ?? null); ?>

      <?php if ($isAdminView): ?>
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
          <button type="button" id="btn-toggle-admin-create" class="btn-primary">Crear cuenta de administrador</button>
        </div>

        <div id="admin-create-panel" class="card" style="display:none;padding:14px;border-radius:10px;margin-bottom:14px;background:#fff8ef;border:1px solid #eadfce;">
          <h3 style="margin-bottom:10px;">Nueva cuenta administradora</h3>
          <form method="post" action="/mi-cuenta" class="auth-form" style="display:grid;gap:10px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="crear_admin">

            <label>Nombre de usuario</label>
            <input type="text" name="admin_username" required maxlength="50" placeholder="Ej. admin2">

            <label>Email</label>
            <input type="email" name="admin_email" required maxlength="150" placeholder="Ej. admin2@anuncios.com">

            <label>Contrasena</label>
            <input type="password" name="admin_password" required minlength="6" placeholder="Minimo 6 caracteres">

            <div class="auth-actions">
              <button class="btn-primary" type="submit">Crear administrador</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <p class="auth-callout">
        Desde aqui puedes gestionar tu cuenta: actualizar nombre, correo y telefono, y cambiar tu contrasena de forma segura.
      </p>

      <h2>Gestion de usuario</h2>

      <?php if (!empty($success)): ?>
        <div class="auth-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $e): ?>
          <div class="auth-error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <form method="post" action="/mi-cuenta" class="auth-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="actualizar_cuenta">

        <label>Nombre de usuario</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($usuario['username'] ?? ''); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>

        <label>Telefono (opcional)</label>
        <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" maxlength="20">

        <label>Contrasena actual</label>
        <input type="password" name="current_password" required>

        <label>Nueva contrasena (opcional)</label>
        <input type="password" name="new_password" placeholder="Solo si deseas cambiarla">

        <label>Confirmar nueva contrasena</label>
        <input type="password" name="confirm_new_password">

        <div class="auth-actions">
          <button class="btn-primary" type="submit">Guardar cambios</button>
          <a class="btn-outline" href="/">Volver a inicio</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (!empty($isAdminView)): ?>
<script>
  (function () {
    var btn = document.getElementById('btn-toggle-admin-create');
    var panel = document.getElementById('admin-create-panel');
    if (!btn || !panel) return;
    btn.addEventListener('click', function () {
      panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });
  })();
</script>
<?php endif; ?>

<?php require_once 'app/views/layouts/footer.php'; ?>
