<?php

class HomeController
{
    public function index()
    {
        global $conexion;

        require_once 'app/models/Empleo.php';
        require_once 'app/models/Categoria.php';
        require_once 'app/models/Publicidad.php';

        $empleoModel = new Empleo($conexion);
        $categoriaModel = new Categoria($conexion);
        $publicidadModel = new Publicidad($conexion);

        $empleos = $empleoModel->obtenerDestacados(7);
        // Si no hay anuncios marcados como destacados, mostrar los más recientes
        if (empty($empleos)) {
            $empleos = $empleoModel->obtenerRecientes(7);
        }
        try {
            $recientes = $empleoModel->obtenerInicioPriorizado(24);
        } catch (Throwable $e) {
            // Fallback de seguridad para evitar caida de portada en host.
            @file_put_contents(
                __DIR__ . '/../../storage/logs/app.log',
                '[' . date('Y-m-d H:i:s') . '] HomeController::index fallback: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
            $recientes = $empleoModel->obtenerRecientes(24);
        }
        $categorias = $categoriaModel->obtenerPrincipales();
        $publicidades = $publicidadModel->obtenerActivas(20);

        $stmtDep = $conexion->prepare("SELECT id, nombre FROM departamentos WHERE estado = 'activo' ORDER BY nombre");
        $stmtDep->execute();
        $departamentos = $stmtDep->fetchAll(PDO::FETCH_ASSOC);

        require_once 'app/views/home.php';
    }

    public function guardarPublicidad()
    {
        global $conexion;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin-publicidad');
            exit;
        }

        verify_csrf();

        if (!is_admin_user($conexion)) {
            $_SESSION['flash_error'] = 'Solo el administrador puede gestionar publicidades.';
            header('Location: /');
            exit;
        }

        require_once 'app/models/Publicidad.php';
        $publicidadModel = new Publicidad($conexion);

        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $enlace = trim($_POST['enlace'] ?? '');

        if ($enlace === '') {
            $_SESSION['flash_error'] = 'Completa el enlace para crear la publicidad.';
            header('Location: /admin-publicidad');
            exit;
        }

        if (!preg_match('#^https?://#i', $enlace)) {
            $enlace = 'https://' . $enlace;
        }

        if (!filter_var($enlace, FILTER_VALIDATE_URL)) {
            $_SESSION['flash_error'] = 'El enlace de la publicidad no es valido.';
            header('Location: /admin-publicidad');
            exit;
        }

        $imagenPath = null;
        if (!empty($_FILES['imagen']['name']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($_FILES['imagen']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $_SESSION['flash_error'] = 'No se pudo subir la imagen de la publicidad.';
                header('Location: /admin-publicidad');
                exit;
            }

            $maxSize = 5 * 1024 * 1024;
            if (($_FILES['imagen']['size'] ?? 0) > $maxSize) {
                $_SESSION['flash_error'] = 'La imagen supera el maximo de 5MB.';
                header('Location: /admin-publicidad');
                exit;
            }

            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                $_SESSION['flash_error'] = 'Formato no permitido. Usa JPG, PNG o WEBP.';
                header('Location: /admin-publicidad');
                exit;
            }

            $uploadDir = __DIR__ . '/../../uploads/publicidad';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $filename = 'pub_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destino = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                $_SESSION['flash_error'] = 'No se pudo guardar la imagen subida.';
                header('Location: /admin-publicidad');
                exit;
            }

            $imagenPath = 'uploads/publicidad/' . $filename;
        }

        $ok = $publicidadModel->crear(
            mb_substr($titulo, 0, 180),
            mb_substr($descripcion, 0, 2000),
            mb_substr($enlace, 0, 500),
            $imagenPath,
            $_SESSION['user_id'] ?? null
        );

        $_SESSION['flash_' . ($ok ? 'success' : 'error')] = $ok
            ? 'Publicidad creada correctamente. La mas reciente aparece primero.'
            : 'No se pudo crear la publicidad.';

        header('Location: /admin-publicidad');
        exit;
    }

    public function adminPublicidad()
    {
        global $conexion;

        if (!is_admin_user($conexion)) {
            $_SESSION['flash_error'] = 'Solo el administrador puede acceder a esta seccion.';
            header('Location: /');
            exit;
        }

        require_once 'app/models/Publicidad.php';
        $publicidadModel = new Publicidad($conexion);
        $publicidades = $publicidadModel->obtenerActivas(50);
        $publicidadEditar = null;

        $editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
        if ($editId > 0) {
            $publicidadEditar = $publicidadModel->obtenerPorId($editId);
        }

        require_once 'app/views/admin-publicidad.php';
        exit;
    }

    public function publicidades()
    {
        global $conexion;

        require_once 'app/models/Publicidad.php';
        $publicidadModel = new Publicidad($conexion);
        $publicidades = $publicidadModel->obtenerActivas(100);

        require_once 'app/views/publicidades.php';
        exit;
    }

    public function actualizarPublicidad()
    {
        global $conexion;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin-publicidad');
            exit;
        }

        verify_csrf();

        if (!is_admin_user($conexion)) {
            $_SESSION['flash_error'] = 'Solo el administrador puede editar publicidades.';
            header('Location: /');
            exit;
        }

        require_once 'app/models/Publicidad.php';
        $publicidadModel = new Publicidad($conexion);

        $id = (int)($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $enlace = trim($_POST['enlace'] ?? '');

        if ($id <= 0 || $enlace === '') {
            $_SESSION['flash_error'] = 'Completa el enlace para actualizar la publicidad.';
            header('Location: /admin-publicidad?edit_id=' . max(0, $id));
            exit;
        }

        if (!preg_match('#^https?://#i', $enlace)) {
            $enlace = 'https://' . $enlace;
        }

        if (!filter_var($enlace, FILTER_VALIDATE_URL)) {
            $_SESSION['flash_error'] = 'El enlace de la publicidad no es valido.';
            header('Location: /admin-publicidad?edit_id=' . $id);
            exit;
        }

        $actual = $publicidadModel->obtenerPorId($id);
        if (!$actual) {
            $_SESSION['flash_error'] = 'La publicidad que intentas editar no existe.';
            header('Location: /admin-publicidad');
            exit;
        }

        $imagenPath = null;
        if (!empty($_FILES['imagen']['name']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($_FILES['imagen']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $_SESSION['flash_error'] = 'No se pudo subir la nueva imagen de la publicidad.';
                header('Location: /admin-publicidad?edit_id=' . $id);
                exit;
            }

            $maxSize = 5 * 1024 * 1024;
            if (($_FILES['imagen']['size'] ?? 0) > $maxSize) {
                $_SESSION['flash_error'] = 'La imagen supera el maximo de 5MB.';
                header('Location: /admin-publicidad?edit_id=' . $id);
                exit;
            }

            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                $_SESSION['flash_error'] = 'Formato no permitido. Usa JPG, PNG o WEBP.';
                header('Location: /admin-publicidad?edit_id=' . $id);
                exit;
            }

            $uploadDir = __DIR__ . '/../../uploads/publicidad';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $filename = 'pub_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destino = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                $_SESSION['flash_error'] = 'No se pudo guardar la imagen subida.';
                header('Location: /admin-publicidad?edit_id=' . $id);
                exit;
            }

            $imagenPath = 'uploads/publicidad/' . $filename;

            if (!empty($actual['imagen'])) {
                $old = __DIR__ . '/../../' . ltrim($actual['imagen'], '/');
                if (is_file($old)) {
                    @unlink($old);
                }
            }
        }

        $ok = $publicidadModel->actualizar(
            $id,
            mb_substr($titulo, 0, 180),
            mb_substr($descripcion, 0, 2000),
            mb_substr($enlace, 0, 500),
            $imagenPath
        );

        $_SESSION['flash_' . ($ok ? 'success' : 'error')] = $ok
            ? 'Publicidad actualizada correctamente.'
            : 'No se pudo actualizar la publicidad.';

        header('Location: /admin-publicidad');
        exit;
    }

    public function eliminarPublicidad()
    {
        global $conexion;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin-publicidad');
            exit;
        }

        verify_csrf();

        if (!is_admin_user($conexion)) {
            $_SESSION['flash_error'] = 'Solo el administrador puede eliminar publicidades.';
            header('Location: /');
            exit;
        }

        require_once 'app/models/Publicidad.php';
        $publicidadModel = new Publicidad($conexion);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Publicidad invalida para eliminar.';
            header('Location: /admin-publicidad');
            exit;
        }

        $actual = $publicidadModel->obtenerPorId($id);
        if (!$actual) {
            $_SESSION['flash_error'] = 'La publicidad no existe o ya fue eliminada.';
            header('Location: /admin-publicidad');
            exit;
        }

        $ok = $publicidadModel->desactivar($id);
        if ($ok && !empty($actual['imagen'])) {
            $old = __DIR__ . '/../../' . ltrim($actual['imagen'], '/');
            if (is_file($old)) {
                @unlink($old);
            }
        }

        $_SESSION['flash_' . ($ok ? 'success' : 'error')] = $ok
            ? 'Publicidad eliminada correctamente.'
            : 'No se pudo eliminar la publicidad.';

        header('Location: /admin-publicidad');
        exit;
    }
}