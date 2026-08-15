<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <section class="admin-cat-panel">
    <div class="section-title">
      <div>
        <span class="section-kicker">Panel admin</span>
        <h3>Gestion de categorias</h3>
      </div>
      <a class="btn-outline" href="/">Volver a inicio</a>
    </div>

    <div class="admin-cat-create-split">
      <form method="post" action="/admin-categorias-crear" class="admin-cat-create-form admin-cat-create-card">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="create_type" value="principal">
        <h4>Nueva categoria principal</h4>
        <label>
          Nombre de categoria
          <input type="text" name="nombre" required maxlength="150">
        </label>
        <button class="btn-primary" type="submit">Agregar categoria</button>
      </form>

      <form method="post" action="/admin-categorias-crear" class="admin-cat-create-form admin-cat-create-card">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="create_type" value="subcategoria">
        <h4>Nueva subcategoria</h4>
        <label>
          Nombre de subcategoria
          <input type="text" name="nombre" required maxlength="150">
        </label>
        <label>
          Categoria principal asociada
          <select name="parent_id" required>
            <option value="">Selecciona categoria principal</option>
            <?php foreach ($principales as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="btn-primary" type="submit">Agregar subcategoria</button>
      </form>
    </div>
  </section>

  <section class="admin-cat-panel">
    <h3>Categorias existentes</h3>

    <?php if (!empty($categorias)): ?>
      <?php
        $categoriasPrincipales = [];
        $subcategorias = [];
        foreach ($categorias as $catItem) {
          if (empty($catItem['parent_id'])) {
            $categoriasPrincipales[] = $catItem;
          } else {
            $subcategorias[] = $catItem;
          }
        }
      ?>

      <div class="admin-cat-group">
        <h4>Categorias principales</h4>
        <?php if (!empty($categoriasPrincipales)): ?>
          <ul class="admin-cat-list">
            <?php foreach ($categoriasPrincipales as $c): ?>
              <li class="admin-cat-item">
                <div class="admin-cat-left">
                  <strong><?php echo htmlspecialchars($c['nombre']); ?></strong>
                  <div class="admin-cat-meta">
                    <span>Categoria principal</span>
                    <span>Slug: <?php echo htmlspecialchars($c['slug']); ?></span>
                  </div>
                </div>

                <div class="admin-cat-right">
                  <form method="post" action="/admin-categorias-actualizar" class="admin-cat-inline-edit">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($c['nombre']); ?>" required maxlength="150" aria-label="Nombre de categoria principal">
                    <select name="parent_id" aria-label="Padre de categoria principal">
                      <option value="">Categoria principal</option>
                      <?php foreach ($principales as $p): ?>
                        <?php if ((int)$p['id'] === (int)$c['id']) continue; ?>
                        <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn-outline" type="submit">Editar</button>
                  </form>

                  <form method="post" action="/admin-categorias-eliminar" onsubmit="return confirm('¿Eliminar categoria?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                    <button class="btn-primary admin-delete-btn" type="submit">Eliminar</button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="admin-cat-empty">No hay categorias principales.</p>
        <?php endif; ?>
      </div>

      <div class="admin-cat-group">
        <h4>Subcategorias</h4>
        <?php if (!empty($subcategorias)): ?>
          <ul class="admin-cat-list">
            <?php foreach ($subcategorias as $c): ?>
              <li class="admin-cat-item">
                <div class="admin-cat-left">
                  <strong><?php echo htmlspecialchars($c['nombre']); ?></strong>
                  <div class="admin-cat-meta">
                    <span>Subcategoria de <?php echo htmlspecialchars($c['parent_nombre'] ?? ''); ?></span>
                    <span>Slug: <?php echo htmlspecialchars($c['slug']); ?></span>
                  </div>
                </div>

                <div class="admin-cat-right">
                  <form method="post" action="/admin-categorias-actualizar" class="admin-cat-inline-edit">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($c['nombre']); ?>" required maxlength="150" aria-label="Nombre de subcategoria">
                    <select name="parent_id" aria-label="Categoria padre de subcategoria" required>
                      <option value="">Selecciona categoria principal</option>
                      <?php foreach ($principales as $p): ?>
                        <?php if ((int)$p['id'] === (int)$c['id']) continue; ?>
                        <option value="<?php echo (int)$p['id']; ?>" <?php echo ((int)($c['parent_id'] ?? 0) === (int)$p['id']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($p['nombre']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn-outline" type="submit">Editar</button>
                  </form>

                  <form method="post" action="/admin-categorias-eliminar" onsubmit="return confirm('¿Eliminar subcategoria?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                    <button class="btn-primary admin-delete-btn" type="submit">Eliminar</button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="admin-cat-empty">No hay subcategorias registradas.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <h3>No hay categorias registradas</h3>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
