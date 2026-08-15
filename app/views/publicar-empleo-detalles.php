<?php require_once 'app/views/layouts/header.php'; ?>
<?php $wizard_step = 2; require 'app/views/partials/wizard.php'; ?>
<?php $departamentos = $departamentos ?? []; ?>

<div class="container">

    

        <div class="publish-panel">
        <form method="post" action="/publicar-avance" enctype="multipart/form-data" class="card" style="padding:18px;border-radius:12px;">
            <?php echo csrf_field(); ?>
            <?php if (!empty($empleo)): ?>
                <input type="hidden" name="empleo_id" value="<?php echo intval($empleo['id']); ?>">
            <?php endif; ?>

        <?php $old = $_SESSION['post_detalles'] ?? []; ?>
        <?php
            $selectedDepartamentoId = (int)($empleo['departamento_id'] ?? $old['departamento_id'] ?? 0);
            $selectedProvinciaId = (int)($empleo['provincia_id'] ?? $old['provincia_id'] ?? 0);
            $selectedDistritoId = (int)($empleo['distrito_id'] ?? $old['distrito_id'] ?? 0);
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label>Título del empleo</label>
                <input class="" type="text" name="titulo" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)" value="<?php echo htmlspecialchars($empleo['titulo'] ?? $old['titulo'] ?? ''); ?>">
            </div>
            <div>
                <label>Empresa</label>
                <input type="text" name="empresa" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)" value="<?php echo htmlspecialchars($empleo['empresa'] ?? $old['empresa'] ?? ''); ?>">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label>Salario</label>
                <?php $salarioVal = (string)($empleo['salario'] ?? $old['salario'] ?? ''); ?>
                <?php $salarioOpciones = ['A tratar', 'Segun experiencia']; ?>
                <?php $salarioEsMonto = $salarioVal !== '' && !in_array($salarioVal, $salarioOpciones, true); ?>
                <select name="salario" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)">
                    <option value="" <?php if ($salarioVal === '') echo 'selected'; ?>>Seleccionar</option>
                    <?php foreach ($salarioOpciones as $op): ?>
                        <option value="<?php echo htmlspecialchars($op); ?>" <?php if ($salarioVal === $op) echo 'selected'; ?>><?php echo htmlspecialchars($op); ?></option>
                    <?php endforeach; ?>
                    <option value="__MONTO__" <?php if ($salarioEsMonto) echo 'selected'; ?>>Monto</option>
                </select>
                <input
                    id="salario_monto"
                    type="text"
                    name="salario_monto"
                    placeholder="Ej: S/ 1850"
                    value="<?php echo $salarioEsMonto ? htmlspecialchars($salarioVal) : ''; ?>"
                    style="<?php echo $salarioEsMonto ? 'display:block;' : 'display:none;'; ?>width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06);margin-top:8px;"
                >
            </div>
            <div>
                <label>Tipo de trabajo</label>
                    <input type="text" name="tipo_trabajo" placeholder="Ej: Tiempo completo" value="<?php echo htmlspecialchars($empleo['tipo_trabajo'] ?? $old['tipo_trabajo'] ?? ''); ?>" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label>Modalidad</label>
                <?php $modalidadVal = $empleo['modalidad'] ?? $old['modalidad'] ?? ''; ?>
                <select name="modalidad" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)">
                    <option value="" <?php if ($modalidadVal=='') echo 'selected'; ?>>Seleccionar</option>
                    <option value="presencial" <?php if ($modalidadVal=='presencial') echo 'selected'; ?>>Presencial</option>
                    <option value="remoto" <?php if ($modalidadVal=='remoto') echo 'selected'; ?>>Remoto</option>
                    <option value="hibrido" <?php if ($modalidadVal=='hibrido') echo 'selected'; ?>>Híbrido</option>
                </select>
            </div>
            <div>
                <label>Teléfono</label>
                <input type="text" name="telefono" required style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)" value="<?php echo htmlspecialchars($empleo['telefono'] ?? $old['telefono'] ?? ''); ?>">
            </div>
        </div>

        <div id="ubicacion-fields" style="display:none;margin-bottom:12px;">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div>
                    <label>Departamento</label>
                    <select id="departamento_select" name="departamento_id" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)">
                        <option value="">Seleccione departamento</option>
                        <?php foreach($departamentos as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php if ($selectedDepartamentoId === (int)$d['id']) echo 'selected'; ?>><?php echo htmlspecialchars($d['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Provincia</label>
                    <select id="provincia_select" name="provincia_id" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)">
                        <option value="">Seleccione provincia</option>
                    </select>
                </div>
                <div>
                    <label>Distrito</label>
                    <select id="distrito_select" name="distrito_id" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)">
                        <option value="">Seleccione distrito</option>
                    </select>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
                <label>Email contacto</label>
                <input type="email" name="email_contacto" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)" value="<?php echo htmlspecialchars($empleo['email_contacto'] ?? $old['email_contacto'] ?? ''); ?>">
            </div>
            <div>
                <label>Tags (separados por comas)</label>
                <input type="text" name="tags" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)" value="<?php echo htmlspecialchars($empleo['tags'] ?? $old['tags'] ?? ''); ?>">
            </div>
        </div>

        <div style="margin-bottom:12px;">
            <label>Descripción</label>
            <textarea name="descripcion" rows="6" required style="width:100%;padding:12px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)"><?php echo htmlspecialchars($empleo['descripcion'] ?? $old['descripcion'] ?? ''); ?></textarea>
        </div>

        <?php $destVal = ($empleo['destacado'] ?? $old['destacado'] ?? 0) ? 1 : 0; ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:8px"><input id="chk-destacado" type="checkbox" name="destacado" value="1" <?php if ($destVal) echo 'checked'; ?>> Publicación destacada</label>
            <div id="portada-wrapper" style="<?php echo $destVal ? 'display:block' : 'display:none'; ?>;margin-left:8px;">
                <label style="display:block;margin-bottom:6px">Imagen de portada (JPG/PNG/WebP, máx 2MB)</label>
                <input type="file" name="image_portada" accept="image/png,image/jpeg,image/webp">
            </div>
        </div>

        <?php if (!empty($empleo) && !empty($empleo['imagen'])): ?>
            <input type="hidden" name="imagen_actual" value="1">
            <div style="margin-bottom:12px;display:flex;gap:12px;align-items:center;">
                <div style="max-width:160px;">
                    <img src="/<?php echo htmlspecialchars($empleo['imagen']); ?>" alt="Portada" style="width:160px;border-radius:8px;object-fit:cover;border:1px solid rgba(15,23,42,0.04)">
                </div>
                <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="remove_image" value="1"> Eliminar portada actual</label>
            </div>
        <?php endif; ?>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn-primary">Siguiente</button>
            <a href="/publicar-empleo" class="btn-outline">Regresar</a>
            <a href="/" class="btn-outline">Cancelar</a>
        </div>

    </form>
    </div>

    <script>
        const chk = document.getElementById('chk-destacado');
        const portada = document.getElementById('portada-wrapper');
        const portadaInput = document.querySelector('input[name="image_portada"]');
        const removeImageChk = document.querySelector('input[name="remove_image"]');
        const hasExistingImage = <?php echo (!empty($empleo) && !empty($empleo['imagen'])) ? 'true' : 'false'; ?>;

        const deptSelect = document.getElementById('departamento_select');
        const provSelect = document.getElementById('provincia_select');
        const distSelect = document.getElementById('distrito_select');
        const salarioSelect = document.querySelector('select[name="salario"]');
        const salarioMontoInput = document.getElementById('salario_monto');
        const initialDepartamentoId = <?php echo $selectedDepartamentoId > 0 ? $selectedDepartamentoId : 'null'; ?>;
        const initialProvinciaId = <?php echo $selectedProvinciaId > 0 ? $selectedProvinciaId : 'null'; ?>;
        const initialDistritoId = <?php echo $selectedDistritoId > 0 ? $selectedDistritoId : 'null'; ?>;

        // mostrar campos de ubicación si modalidad presencial o híbrido
        const modalidadSelect = document.querySelector('select[name="modalidad"]');
        const ubicacionFields = document.getElementById('ubicacion-fields');

        const handleModalidad = () => {
            const v = modalidadSelect ? modalidadSelect.value : '';
            const needsLocation = (v === 'presencial' || v === 'hibrido');
            if (ubicacionFields) {
                ubicacionFields.style.display = needsLocation ? 'block' : 'none';
            }
            if (deptSelect && provSelect && distSelect) {
                deptSelect.required = needsLocation;
                provSelect.required = needsLocation;
                distSelect.required = needsLocation;
            }
        };

        const handleDestacado = () => {
            const wantsFeatured = !!(chk && chk.checked);
            if (portada) {
                portada.style.display = wantsFeatured ? 'block' : 'none';
            }
            if (!portadaInput) return;

            const removingExisting = !!(removeImageChk && removeImageChk.checked);
            const needsNewUpload = wantsFeatured && (!hasExistingImage || removingExisting);
            portadaInput.required = needsNewUpload;
        };

        const handleSalario = () => {
            if (!salarioSelect || !salarioMontoInput) return;
            const isMonto = salarioSelect.value === '__MONTO__';
            salarioMontoInput.style.display = isMonto ? 'block' : 'none';
            salarioMontoInput.required = isMonto;
            if (!isMonto) {
                salarioMontoInput.value = '';
            }
        };

        if (modalidadSelect) {
            modalidadSelect.addEventListener('change', handleModalidad);
        }
        if (chk) {
            chk.addEventListener('change', handleDestacado);
        }
        if (removeImageChk) {
            removeImageChk.addEventListener('change', handleDestacado);
        }
        if (salarioSelect) {
            salarioSelect.addEventListener('change', handleSalario);
        }

        handleModalidad();
        handleDestacado();
        handleSalario();

        // cargar provincias y distritos dependientes
        async function loadProvincias(departamentoId, selectedProv) {
            provSelect.innerHTML = '<option value="">Cargando...</option>';
            const res = await fetch('/provincias?departamento_id=' + departamentoId);
            const data = await res.json();
            provSelect.innerHTML = '<option value="">Seleccione provincia</option>';
            data.forEach(p => {
                const o = document.createElement('option');
                o.value = p.id; o.textContent = p.nombre;
                if (selectedProv && selectedProv == p.id) o.selected = true;
                provSelect.appendChild(o);
            });
            // si hay provincia seleccionada cargar distritos
            if (provSelect.value) loadDistritos(provSelect.value, initialDistritoId);
        }

        async function loadDistritos(provinciaId, selectedDist) {
            distSelect.innerHTML = '<option value="">Cargando...</option>';
            const res = await fetch('/distritos?provincia_id=' + provinciaId);
            const data = await res.json();
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            data.forEach(d => {
                const o = document.createElement('option');
                o.value = d.id; o.textContent = d.nombre;
                if (selectedDist && selectedDist == d.id) o.selected = true;
                distSelect.appendChild(o);
            });
        }

        deptSelect.addEventListener('change', () => {
            if (deptSelect.value) loadProvincias(deptSelect.value);
            else { provSelect.innerHTML = '<option value="">Seleccione provincia</option>'; distSelect.innerHTML = '<option value="">Seleccione distrito</option>'; }
        });

        provSelect.addEventListener('change', () => {
            if (provSelect.value) loadDistritos(provSelect.value);
            else distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
        });

        // si ya hay departamento (edición), cargar provincias iniciales
        if (initialDepartamentoId) {
            loadProvincias(initialDepartamentoId, initialProvinciaId);
        }
    </script>

</div>

<?php require_once 'app/views/layouts/footer.php'; ?>