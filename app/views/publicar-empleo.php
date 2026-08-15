<?php require_once 'app/views/layouts/header.php'; ?>
<?php $wizard_step = 1; require 'app/views/partials/wizard.php'; ?>

<div class="container">

    

        <div class="publish-panel">
        <form method="post" action="/publicar-empleo" class="card" style="padding:16px;border-radius:12px;">
            <?php echo csrf_field(); ?>

        <label style="display:block;margin-bottom:8px"><strong>Categoría</strong></label>
        <select name="categoria_id" id="categoria_id" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)">
            <option value="">Seleccione una categoría</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo $categoria['id']; ?>"><?php echo $categoria['nombre']; ?></option>
            <?php endforeach; ?>
        </select>

        <div id="contenedor-subcategoria" style="display:none;margin-top:12px;">
            <label style="display:block;margin-bottom:8px"><strong>Subcategoría</strong></label>
            <select name="subcategoria_id" id="subcategoria_id" style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(15,23,42,0.06)">
                <option value="">Seleccione una subcategoría</option>
            </select>
        </div>

        <div style="margin-top:14px;display:flex;gap:8px;align-items:center;">
            <button type="submit" name="continuar" id="btn-siguiente" class="btn-primary" style="display:none;">Siguiente</button>
            <a href="#" class="btn-outline" style="margin-left:8px;" onclick="history.back();return false;">Regresar</a>
            <a href="/" class="btn-outline" style="margin-left:8px;">Cancelar</a>
        </div>

    </form>
    </div>

    <script>
        const categoria = document.getElementById('categoria_id');
        const subcategoria = document.getElementById('subcategoria_id');
        const contenedorSubcategoria = document.getElementById('contenedor-subcategoria');
        const btnSiguiente = document.getElementById('btn-siguiente');

        categoria.addEventListener('change', function () {
            const categoriaId = this.value;
            subcategoria.innerHTML = '<option value="">Seleccione una subcategoría</option>';
            btnSiguiente.style.display = 'none';

            if (!categoriaId) {
                contenedorSubcategoria.style.display = 'none';
                return;
            }

            fetch('subcategorias?categoria_id=' + categoriaId)
                .then(response => response.json())
                .then(data => {
                    contenedorSubcategoria.style.display = 'block';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.nombre;
                        subcategoria.appendChild(option);
                    });
                });
        });

        subcategoria.addEventListener('change', function () {
            if (this.value) {
                btnSiguiente.style.display = 'inline-block';
            } else {
                btnSiguiente.style.display = 'none';
            }
        });
    </script>

</div>

<?php require_once 'app/views/layouts/footer.php'; ?>