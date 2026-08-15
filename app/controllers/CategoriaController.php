<?php

require_once 'app/models/Categoria.php';
require_once 'app/helpers/functions.php';

class CategoriaController
{
    private function requireAdmin($conexion)
    {
        if (!is_admin_user($conexion)) {
            $_SESSION['flash_error'] = 'Solo un administrador puede gestionar categorias.';
            header('Location: /');
            exit;
        }
    }

    private function makeUniqueSlug($categoriaModel, $nombre, $excludeId = null)
    {
        $slug = strtolower(trim($nombre));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'categoria';

        $base = $slug;
        $i = 1;
        while ($categoriaModel->existeSlug($slug, $excludeId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    public function gestion()
    {
        global $conexion;
        $this->requireAdmin($conexion);

        $categoriaModel = new Categoria($conexion);
        $categorias = $categoriaModel->obtenerParaGestion();
        $principales = $categoriaModel->obtenerPrincipales();

        require_once 'app/views/gestion-categorias.php';
    }

    public function crear()
    {
        global $conexion;
        $this->requireAdmin($conexion);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin-categorias');
            exit;
        }

        verify_csrf();
        $categoriaModel = new Categoria($conexion);

        $nombre = trim($_POST['nombre'] ?? '');
        $createType = trim($_POST['create_type'] ?? 'principal');
        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

        if ($nombre === '') {
            $_SESSION['flash_error'] = 'Debes ingresar el nombre de la categoria.';
            header('Location: /admin-categorias');
            exit;
        }

        if ($createType === 'subcategoria') {
            if ($parentId === null || $parentId <= 0) {
                $_SESSION['flash_error'] = 'Para crear una subcategoria debes elegir una categoria principal.';
                header('Location: /admin-categorias');
                exit;
            }

            $principales = $categoriaModel->obtenerPrincipales();
            $idsPrincipales = array_map(static function ($item) {
                return (int)$item['id'];
            }, $principales);

            if (!in_array($parentId, $idsPrincipales, true)) {
                $_SESSION['flash_error'] = 'La categoria principal seleccionada no es valida.';
                header('Location: /admin-categorias');
                exit;
            }
        } else {
            $parentId = null;
        }

        $slug = $this->makeUniqueSlug($categoriaModel, $nombre);
        $ok = $categoriaModel->crear(mb_substr($nombre, 0, 150), $slug, $parentId);

        $_SESSION['flash_' . ($ok ? 'success' : 'error')] = $ok
            ? ($createType === 'subcategoria' ? 'Subcategoria creada correctamente.' : 'Categoria creada correctamente.')
            : ($createType === 'subcategoria' ? 'No se pudo crear la subcategoria.' : 'No se pudo crear la categoria.');

        header('Location: /admin-categorias');
        exit;
    }

    public function actualizar()
    {
        global $conexion;
        $this->requireAdmin($conexion);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin-categorias');
            exit;
        }

        verify_csrf();
        $categoriaModel = new Categoria($conexion);

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

        if ($id <= 0 || $nombre === '') {
            $_SESSION['flash_error'] = 'Datos invalidos para actualizar categoria.';
            header('Location: /admin-categorias');
            exit;
        }

        if ($parentId !== null && $parentId === $id) {
            $_SESSION['flash_error'] = 'Una categoria no puede depender de si misma.';
            header('Location: /admin-categorias');
            exit;
        }

        $slug = $this->makeUniqueSlug($categoriaModel, $nombre, $id);
        $ok = $categoriaModel->actualizar($id, mb_substr($nombre, 0, 150), $slug, $parentId);

        $_SESSION['flash_' . ($ok ? 'success' : 'error')] = $ok
            ? 'Categoria actualizada correctamente.'
            : 'No se pudo actualizar la categoria.';

        header('Location: /admin-categorias');
        exit;
    }

    public function eliminar()
    {
        global $conexion;
        $this->requireAdmin($conexion);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin-categorias');
            exit;
        }

        verify_csrf();
        $categoriaModel = new Categoria($conexion);

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'No se pudo identificar la categoria a eliminar.';
            header('Location: /admin-categorias');
            exit;
        }

        // Si se elimina una principal, también inactivar subcategorias directas.
        $subs = $categoriaModel->obtenerSubcategorias($id);
        foreach ($subs as $sub) {
            $categoriaModel->inactivar((int)$sub['id']);
        }

        $ok = $categoriaModel->inactivar($id);

        $_SESSION['flash_' . ($ok ? 'success' : 'error')] = $ok
            ? 'Categoria eliminada correctamente.'
            : 'No se pudo eliminar la categoria.';

        header('Location: /admin-categorias');
        exit;
    }
}
