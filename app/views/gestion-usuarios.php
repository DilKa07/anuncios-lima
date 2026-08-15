<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <section class="admin-users-page">
    <div class="section-title" style="margin-top:0;">
      <div>
        <span class="section-kicker">Panel admin</span>
        <h3>
          Gestion de usuarios
          <span class="admin-users-counter">Administradores <?php echo (int)($adminsCount ?? 0); ?> | Usuarios <?php echo (int)($usuariosCount ?? 0); ?></span>
        </h3>
      </div>
      <a class="btn-outline" href="/">Volver al inicio</a>
    </div>

    <form method="get" action="/gestion-usuarios" class="admin-users-search">
      <input
        type="text"
        name="q"
        value="<?php echo htmlspecialchars($q ?? ''); ?>"
        placeholder="Buscar por usuario, correo o titulo de anuncio"
      >
      <button type="submit" class="btn-primary">Buscar</button>
      <?php if (!empty($q)): ?>
        <a href="/gestion-usuarios" class="btn-outline">Limpiar</a>
      <?php endif; ?>
    </form>

    <div class="admin-users-section-title">Administradores</div>
    <?php if (empty($admins)): ?>
      <p class="admin-users-empty">No hay administradores para mostrar.</p>
    <?php else: ?>
      <div class="admin-users-grid">
        <?php foreach ($admins as $u): ?>
          <article class="admin-user-card">
            <div class="admin-user-main">
              <h4><?php echo htmlspecialchars($u['username'] ?? ''); ?></h4>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($u['email'] ?? ''); ?></p>
              <p><strong>Rol:</strong> Administrador</p>
              <p><strong>Publicaciones:</strong> <?php echo (int)($u['publicaciones_count'] ?? 0); ?></p>
              <p><strong>Ultimo anuncio:</strong> <?php echo htmlspecialchars($u['ultimo_anuncio_titulo'] ?? 'Sin anuncios'); ?></p>
              <p><strong>IP ultimo acceso:</strong> <?php echo htmlspecialchars($u['last_login_ip'] ?? 'No registrada'); ?></p>
              <p><strong>Estado:</strong> <?php echo ((int)($u['cuenta_bloqueada'] ?? 0) === 1) ? 'Bloqueada' : 'Activa'; ?></p>
            </div>
            <div class="admin-user-actions">
              <?php if ((int)($u['cuenta_bloqueada'] ?? 0) === 1): ?>
                <form method="post" action="/gestion-usuarios">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="desbloquear_cuenta">
                  <input type="hidden" name="user_id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                  <button type="submit" class="btn-outline">Desbloquear cuenta</button>
                </form>
              <?php else: ?>
                <form method="post" action="/gestion-usuarios" onsubmit="return confirm('¿Bloquear esta cuenta?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="bloquear_cuenta">
                  <input type="hidden" name="user_id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                  <button type="submit" class="btn-primary admin-user-block-btn">Bloquear cuenta</button>
                </form>
              <?php endif; ?>

              <form method="post" action="/gestion-usuarios" onsubmit="return confirm('¿Bloquear IP del ultimo acceso?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="bloquear_ip">
                <input type="hidden" name="user_id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                <button type="submit" class="btn-outline" <?php echo empty($u['last_login_ip']) ? 'disabled' : ''; ?>>Bloquear IP del dispositivo</button>
              </form>

              <?php $isOwnCard = (int)($u['id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0); ?>
              <form method="post" action="/gestion-usuarios" onsubmit="return confirm('ALERTA: Esta accion eliminara permanentemente esta cuenta y todo lo relacionado (anuncios, imagenes y datos). No se puede deshacer. ¿Deseas continuar?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="eliminar_cuenta">
                <input type="hidden" name="user_id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                <button type="submit" class="btn-outline admin-user-delete-btn" <?php echo $isOwnCard ? 'disabled title="No puedes eliminar tu propia cuenta desde este panel"' : ''; ?>>Eliminar cuenta</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="admin-users-section-title">Usuarios</div>
    <?php if (empty($usuarios)): ?>
      <p class="admin-users-empty">No hay usuarios para mostrar.</p>
    <?php else: ?>
      <div class="admin-users-grid">
        <?php foreach ($usuarios as $u): ?>
          <article class="admin-user-card">
            <div class="admin-user-main">
              <h4><?php echo htmlspecialchars($u['username'] ?? ''); ?></h4>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($u['email'] ?? ''); ?></p>
              <p><strong>Rol:</strong> Usuario</p>
              <p><strong>Publicaciones:</strong> <?php echo (int)($u['publicaciones_count'] ?? 0); ?></p>
              <p><strong>Ultimo anuncio:</strong> <?php echo htmlspecialchars($u['ultimo_anuncio_titulo'] ?? 'Sin anuncios'); ?></p>
              <p><strong>IP ultimo acceso:</strong> <?php echo htmlspecialchars($u['last_login_ip'] ?? 'No registrada'); ?></p>
              <p><strong>Estado:</strong> <?php echo ((int)($u['cuenta_bloqueada'] ?? 0) === 1) ? 'Bloqueada' : 'Activa'; ?></p>
            </div>
            <div class="admin-user-actions">
              <?php if ((int)($u['cuenta_bloqueada'] ?? 0) === 1): ?>
                <form method="post" action="/gestion-usuarios">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="desbloquear_cuenta">
                  <input type="hidden" name="user_id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                  <button type="submit" class="btn-outline">Desbloquear cuenta</button>
                </form>
              <?php else: ?>
                <form method="post" action="/gestion-usuarios" onsubmit="return confirm('¿Bloquear esta cuenta?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="bloquear_cuenta">
                  <input type="hidden" name="user_id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                  <button type="submit" class="btn-primary admin-user-block-btn">Bloquear cuenta</button>
                </form>
              <?php endif; ?>

              <form method="post" action="/gestion-usuarios" onsubmit="return confirm('¿Bloquear IP del ultimo acceso?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="bloquear_ip">
                <input type="hidden" name="user_id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                <button type="submit" class="btn-outline" <?php echo empty($u['last_login_ip']) ? 'disabled' : ''; ?>>Bloquear IP del dispositivo</button>
              </form>

              <?php $isOwnCard = (int)($u['id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0); ?>
              <form method="post" action="/gestion-usuarios" onsubmit="return confirm('ALERTA: Esta accion eliminara permanentemente esta cuenta y todo lo relacionado (anuncios, imagenes y datos). No se puede deshacer. ¿Deseas continuar?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="eliminar_cuenta">
                <input type="hidden" name="user_id" value="<?php echo (int)($u['id'] ?? 0); ?>">
                <button type="submit" class="btn-outline admin-user-delete-btn" <?php echo $isOwnCard ? 'disabled title="No puedes eliminar tu propia cuenta desde este panel"' : ''; ?>>Eliminar cuenta</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
