<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
    <h2>Gestion de lugar</h2>
    <p style="margin:8px 0 18px;color:var(--muted);">Administra departamentos, provincias y distritos desde un solo panel.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-bottom:18px;">
        <form method="post" action="/gestion-lugares" class="card" style="padding:16px;border-radius:12px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="crear_departamento">
            <h3 style="margin-bottom:12px;">Nuevo departamento</h3>
            <label style="display:block;margin-bottom:6px;"><strong>Nombre</strong></label>
            <input type="text" name="nombre_departamento" required maxlength="100" placeholder="Ej. Arequipa" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);margin-bottom:12px;">
            <button type="submit" class="btn-primary">Agregar departamento</button>
        </form>

        <form method="post" action="/gestion-lugares" class="card" style="padding:16px;border-radius:12px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="crear_provincia">
            <h3 style="margin-bottom:12px;">Nueva provincia</h3>
            <label style="display:block;margin-bottom:6px;"><strong>Departamento</strong></label>
            <select name="departamento_id" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);margin-bottom:10px;">
                <option value="">Seleccione departamento</option>
                <?php foreach ($departamentos as $dep): ?>
                    <option value="<?php echo (int)$dep['id']; ?>"><?php echo htmlspecialchars($dep['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <label style="display:block;margin-bottom:6px;"><strong>Nombre</strong></label>
            <input type="text" name="nombre_provincia" required maxlength="100" placeholder="Ej. Camaná" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);margin-bottom:12px;">
            <button type="submit" class="btn-primary">Agregar provincia</button>
        </form>

        <form method="post" action="/gestion-lugares" class="card" style="padding:16px;border-radius:12px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="crear_distrito">
            <h3 style="margin-bottom:12px;">Nuevo distrito</h3>
            <label style="display:block;margin-bottom:6px;"><strong>Departamento</strong></label>
            <select id="dep_distrito" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);margin-bottom:10px;">
                <option value="">Seleccione departamento</option>
                <?php foreach ($departamentos as $dep): ?>
                    <option value="<?php echo (int)$dep['id']; ?>"><?php echo htmlspecialchars($dep['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <label style="display:block;margin-bottom:6px;"><strong>Provincia</strong></label>
            <select name="provincia_id" id="prov_distrito" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);margin-bottom:10px;">
                <option value="">Seleccione provincia</option>
            </select>
            <label style="display:block;margin-bottom:6px;"><strong>Nombre</strong></label>
            <input type="text" name="nombre_distrito" required maxlength="100" placeholder="Ej. Miraflores" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);margin-bottom:12px;">
            <button type="submit" class="btn-primary">Agregar distrito</button>
        </form>
    </div>

    <div class="card" style="padding:16px;border-radius:12px;margin-bottom:14px;">
        <h3 style="margin-bottom:10px;">Departamentos registrados</h3>
        <?php if (empty($departamentos)): ?>
            <p>No hay departamentos.</p>
        <?php else: ?>
            <ul style="margin:0;padding-left:18px;display:grid;gap:10px;">
                <?php foreach ($departamentos as $dep): ?>
                    <li>
                        <form method="post" action="/gestion-lugares" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="editar_departamento">
                            <input type="hidden" name="departamento_id" value="<?php echo (int)$dep['id']; ?>">
                            <input type="text" name="nombre_departamento" value="<?php echo htmlspecialchars($dep['nombre']); ?>" required maxlength="100" style="min-width:220px;padding:8px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);">
                            <small style="color:var(--muted);">(<?php echo htmlspecialchars($dep['estado']); ?>)</small>
                            <button type="submit" class="btn-outline" style="min-height:34px;padding:0 10px;">Editar</button>
                        </form>
                        <form method="post" action="/gestion-lugares" style="margin-top:6px;" onsubmit="return confirm('¿Eliminar este departamento?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="eliminar_departamento">
                            <input type="hidden" name="departamento_id" value="<?php echo (int)$dep['id']; ?>">
                            <button type="submit" class="btn-primary" style="min-height:34px;padding:0 10px;background:#b42318;box-shadow:none;">Eliminar</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card" style="padding:16px;border-radius:12px;margin-bottom:14px;">
        <h3 style="margin-bottom:10px;">Provincias registradas</h3>
        <?php if (empty($provincias)): ?>
            <p>No hay provincias.</p>
        <?php else: ?>
            <ul style="margin:0;padding-left:18px;display:grid;gap:10px;">
                <?php foreach ($provincias as $prov): ?>
                    <li>
                        <form method="post" action="/gestion-lugares" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="editar_provincia">
                            <input type="hidden" name="provincia_id" value="<?php echo (int)$prov['id']; ?>">
                            <input type="text" name="nombre_provincia" value="<?php echo htmlspecialchars($prov['nombre']); ?>" required maxlength="100" style="min-width:220px;padding:8px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);">
                            <small><?php echo htmlspecialchars($prov['departamento_nombre']); ?></small>
                            <small style="color:var(--muted);">(<?php echo htmlspecialchars($prov['estado']); ?>)</small>
                            <button type="submit" class="btn-outline" style="min-height:34px;padding:0 10px;">Editar</button>
                        </form>
                        <form method="post" action="/gestion-lugares" style="margin-top:6px;" onsubmit="return confirm('¿Eliminar esta provincia?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="eliminar_provincia">
                            <input type="hidden" name="provincia_id" value="<?php echo (int)$prov['id']; ?>">
                            <button type="submit" class="btn-primary" style="min-height:34px;padding:0 10px;background:#b42318;box-shadow:none;">Eliminar</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card" style="padding:16px;border-radius:12px;">
        <h3 style="margin-bottom:10px;">Distritos registrados</h3>
        <?php if (empty($distritos)): ?>
            <p>No hay distritos.</p>
        <?php else: ?>
            <ul style="margin:0;padding-left:18px;display:grid;gap:10px;">
                <?php foreach ($distritos as $dist): ?>
                    <li>
                        <form method="post" action="/gestion-lugares" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="editar_distrito">
                            <input type="hidden" name="distrito_id" value="<?php echo (int)$dist['id']; ?>">
                            <input type="text" name="nombre_distrito" value="<?php echo htmlspecialchars($dist['nombre']); ?>" required maxlength="100" style="min-width:220px;padding:8px;border-radius:8px;border:1px solid rgba(15,23,42,0.12);">
                            <small><?php echo htmlspecialchars($dist['provincia_nombre']); ?>, <?php echo htmlspecialchars($dist['departamento_nombre']); ?></small>
                            <small style="color:var(--muted);">(<?php echo htmlspecialchars($dist['estado']); ?>)</small>
                            <button type="submit" class="btn-outline" style="min-height:34px;padding:0 10px;">Editar</button>
                        </form>
                        <form method="post" action="/gestion-lugares" style="margin-top:6px;" onsubmit="return confirm('¿Eliminar este distrito?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="eliminar_distrito">
                            <input type="hidden" name="distrito_id" value="<?php echo (int)$dist['id']; ?>">
                            <button type="submit" class="btn-primary" style="min-height:34px;padding:0 10px;background:#b42318;box-shadow:none;">Eliminar</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var depSelect = document.getElementById('dep_distrito');
    var provSelect = document.getElementById('prov_distrito');

    if (!depSelect || !provSelect) return;

    depSelect.addEventListener('change', function () {
        var depId = this.value;
        provSelect.innerHTML = '<option value="">Seleccione provincia</option>';
        if (!depId) return;

        fetch('/provincias?departamento_id=' + encodeURIComponent(depId))
            .then(function (response) { return response.json(); })
            .then(function (items) {
                items.forEach(function (item) {
                    var option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.nombre;
                    provSelect.appendChild(option);
                });
            })
            .catch(function () {
                provSelect.innerHTML = '<option value="">No se pudieron cargar provincias</option>';
            });
    });
})();
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>