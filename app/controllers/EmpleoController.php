<?php

require_once 'app/models/Categoria.php';
require_once 'app/helpers/notifications.php';

// sesión segura (solo si no existe)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class EmpleoController
{
    public function index()
    {
        global $conexion;
        $this->limpiarSolicitudesDestacadasExpiradas();

        try {

        // filtros
        $q = trim($_GET['q'] ?? '');
        $departamento_id = isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : 0;
        $provincia_id = isset($_GET['provincia_id']) ? (int)$_GET['provincia_id'] : 0;
        $distrito_id = isset($_GET['distrito_id']) ? (int)$_GET['distrito_id'] : 0;
        $tipo = $_GET['tipo'] ?? '';
        $lugar = trim($_GET['lugar'] ?? '');

        // paginación
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        // orden (recientes por defecto) -> soporta 'recientes' y 'mas_vistos'
        // recordar preferencia en sesión
        if (!empty($_GET['order'])) {
            $_SESSION['empleos_order'] = $_GET['order'];
        }
        $order = $_GET['order'] ?? ($_SESSION['empleos_order'] ?? 'recientes');
        $hasVisitasColumn = $this->hasTableColumn('empleos', 'visitas');
        $featuredOrder = "CASE WHEN destacado = 1 AND imagen IS NOT NULL AND imagen <> '' THEN 0 ELSE 1 END";
        $allowedOrders = [
            'recientes' => $featuredOrder . ', fecha_publicacion DESC',
            'mas_vistos' => $hasVisitasColumn
                ? ($featuredOrder . ', fecha_publicacion DESC, visitas DESC')
                : ($featuredOrder . ', fecha_publicacion DESC'),
        ];
        $orderSql = $allowedOrders[$order] ?? $allowedOrders['recientes'];

        $fromSql = "FROM empleos e
            LEFT JOIN departamentos dep ON dep.id = e.departamento_id
            LEFT JOIN provincias prov ON prov.id = e.provincia_id
            LEFT JOIN distritos dist ON dist.id = e.distrito_id";

        $where = "WHERE (e.estado = 'publicado' OR e.estado = '' OR e.estado IS NULL)";
        $params = [];

        if ($q !== '') {
            $where .= " AND (e.titulo LIKE :q OR e.descripcion LIKE :q OR e.empresa LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if ($lugar !== '') {
            $where .= " AND (dep.nombre LIKE :lugar OR prov.nombre LIKE :lugar OR dist.nombre LIKE :lugar)";
            $params[':lugar'] = "%{$lugar}%";
        }
        if ($departamento_id > 0) {
            $where .= " AND e.departamento_id = :departamento_id";
            $params[':departamento_id'] = $departamento_id;
        }
        if ($provincia_id > 0) {
            $where .= " AND e.provincia_id = :provincia_id";
            $params[':provincia_id'] = $provincia_id;
        }
        if ($distrito_id > 0) {
            $where .= " AND e.distrito_id = :distrito_id";
            $params[':distrito_id'] = $distrito_id;
        }
        if ($tipo !== '') {
            $where .= " AND e.tipo_trabajo = :tipo";
            $params[':tipo'] = $tipo;
        }

        // total
        $countSql = "SELECT COUNT(*) " . $fromSql . ' ' . $where;
        $stmt = $conexion->prepare($countSql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        // resultados
        $sql = "SELECT e.*, dep.nombre AS departamento, prov.nombre AS provincia, dist.nombre AS distrito " . $fromSql . ' ' . $where . " ORDER BY " . $orderSql . " LIMIT :limit OFFSET :offset";
        try {
            $stmt = $conexion->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $safeSql = "SELECT e.*, dep.nombre AS departamento, prov.nombre AS provincia, dist.nombre AS distrito "
                . $fromSql . ' ' . $where . " ORDER BY " . $allowedOrders['recientes'] . " LIMIT :limit OFFSET :offset";
            $stmt = $conexion->prepare($safeSql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            @file_put_contents(
                __DIR__ . '/../../storage/logs/app.log',
                '[' . date('Y-m-d H:i:s') . '] EmpleoController::index fallback order: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }

        $locationSuggestions = [];
        $departamentos = [];
        $provincias = [];
        $distritos = [];
        $locationsSql = "
            SELECT DISTINCT lugar FROM (
                SELECT dist.nombre AS lugar
                FROM empleos e
                INNER JOIN distritos dist ON dist.id = e.distrito_id
                WHERE (e.estado = 'publicado' OR e.estado = '' OR e.estado IS NULL)
                    AND dist.nombre IS NOT NULL AND dist.nombre <> ''
                UNION
                SELECT prov.nombre AS lugar
                FROM empleos e
                INNER JOIN provincias prov ON prov.id = e.provincia_id
                WHERE (e.estado = 'publicado' OR e.estado = '' OR e.estado IS NULL)
                    AND prov.nombre IS NOT NULL AND prov.nombre <> ''
                UNION
                SELECT dep.nombre AS lugar
                FROM empleos e
                INNER JOIN departamentos dep ON dep.id = e.departamento_id
                WHERE (e.estado = 'publicado' OR e.estado = '' OR e.estado IS NULL)
                    AND dep.nombre IS NOT NULL AND dep.nombre <> ''
            ) lugares
            ORDER BY lugar ASC";
        $locStmt = $conexion->prepare($locationsSql);
        $locStmt->execute();
        $locationSuggestions = $locStmt->fetchAll(PDO::FETCH_COLUMN);

        $depStmt = $conexion->prepare("SELECT id, nombre FROM departamentos WHERE estado = 'activo' ORDER BY nombre ASC");
        $depStmt->execute();
        $departamentos = $depStmt->fetchAll(PDO::FETCH_ASSOC);

        $provStmt = $conexion->prepare("SELECT id, nombre FROM provincias WHERE estado = 'activo' ORDER BY nombre ASC");
        $provStmt->execute();
        $provincias = $provStmt->fetchAll(PDO::FETCH_ASSOC);

        $distStmt = $conexion->prepare("SELECT id, nombre FROM distritos WHERE estado = 'activo' ORDER BY nombre ASC");
        $distStmt->execute();
        $distritos = $distStmt->fetchAll(PDO::FETCH_ASSOC);

        // construir paginación simple
        $totalPages = (int)ceil($total / $perPage);
        $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
        // preserve other query params
        $qs = $_GET;
        unset($qs['page']);
        $queryBase = http_build_query($qs);
        $paginacion_html = '';
        if ($totalPages > 1) {
            $paginacion_html .= '<nav class="pagination">';
            if ($page > 1) {
                $qs['page'] = $page - 1;
                $paginacion_html .= '<a class="btn-outline" href="' . $baseUrl . '?' . http_build_query($qs) . '">Anterior</a>';
            }
            for ($i = 1; $i <= $totalPages; $i++) {
                $qs['page'] = $i;
                $cls = $i == $page ? 'btn-primary' : 'btn-outline';
                $paginacion_html .= ' <a class="' . $cls . '" href="' . $baseUrl . '?' . http_build_query($qs) . '">' . $i . '</a> ';
            }
            if ($page < $totalPages) {
                $qs['page'] = $page + 1;
                $paginacion_html .= '<a class="btn-outline" href="' . $baseUrl . '?' . http_build_query($qs) . '">Siguiente</a>';
            }
            $paginacion_html .= '</nav>';
        }

        require_once 'app/views/empleos.php';
        } catch (Throwable $e) {
            @file_put_contents(
                __DIR__ . '/../../storage/logs/app.log',
                '[' . date('Y-m-d H:i:s') . '] EmpleoController::index fatal: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['flash_error'] = 'Ocurrio un error al cargar empleos. Intenta nuevamente.';
            }

            $resultados = [];
            $total = 0;
            $locationSuggestions = [];
            $departamentos = [];
            $provincias = [];
            $distritos = [];
            $paginacion_html = '';

            require_once 'app/views/empleos.php';
        }
    }

    // =========================
    // PASO 1
    // =========================
    public function publicar()
    {
        global $conexion;
        $this->limpiarSolicitudesDestacadasExpiradas();

        // Requiere usuario autenticado
        if (empty($_SESSION['user_id'])) {
            // redirigir a login con retorno
            header('Location: /login?redirect=/publicar-empleo');
            exit;
        }

        // Si se abre el paso 1 por GET, iniciar un flujo limpio de publicación.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            unset($_SESSION['post_detalles']);
            unset($_SESSION['post_detalles_image']);
            unset($_SESSION['publicacion_result']);
        }

        $categoriaModel = new Categoria($conexion);
        $categorias = $categoriaModel->obtenerPrincipales();

        if (isset($_POST['continuar'])) {
            // verificar CSRF
            require_once 'app/helpers/functions.php';
            verify_csrf();

            $categoria_id = $_POST['categoria_id'] ?? null;
            $subcategoria_id = $_POST['subcategoria_id'] ?? null;

            if (empty($categoria_id) || empty($subcategoria_id)) {

                echo '<p style="color:red;">Debe seleccionar categoría y subcategoría.</p>';

            } else {

                $_SESSION['categoria_id'] = $categoria_id;
                $_SESSION['subcategoria_id'] = $subcategoria_id;

                header("Location: /publicar-empleo-detalles");
                exit;
            }
        }

        require_once 'app/views/publicar-empleo.php';
    }

    // =========================
    // AJAX subcategorías
    // =========================
    public function subcategorias()
    {
        global $conexion;

        $categoria_id = $_GET['categoria_id'] ?? 0;

        $categoriaModel = new Categoria($conexion);
        $subcategorias = $categoriaModel->obtenerSubcategorias($categoria_id);

        header('Content-Type: application/json');
        echo json_encode($subcategorias);
        exit;
    }

    // =========================
    // PASO 2
    // =========================
    public function detalles()
    {
        if (!isset($_SESSION['categoria_id']) || !isset($_SESSION['subcategoria_id'])) {
            header("Location: /publicar-empleo");
            exit;
        }

        // cargar departamentos para el formulario de ubicación
        global $conexion;
        $stmt = $conexion->prepare("SELECT id,nombre FROM departamentos WHERE estado = 'activo' ORDER BY nombre");
        $stmt->execute();
        $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once 'app/views/publicar-empleo-detalles.php';
    }

    // paso: avance (guardar temporalmente en sesión y mostrar resumen)
    public function avance()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /publicar-empleo-detalles'); exit;
        }
        // verificar CSRF
        require_once 'app/helpers/functions.php';
        verify_csrf();

        $erroresDetalles = $this->validarCamposObligatoriosDetalles($_POST);
        if (!empty($erroresDetalles)) {
            $_SESSION['flash_error'] = implode(' ', $erroresDetalles);
            header('Location: /publicar-empleo-detalles');
            exit;
        }

        // almacenar campos del paso detalles en sesión
        $_SESSION['post_detalles'] = $_POST;

        // si se subio imagen en este paso, guardarla temporalmente para el paso final
        $this->limpiarImagenTemporal();
        if (isset($_FILES['image_portada']) && ($_FILES['image_portada']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $f = $_FILES['image_portada'];
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if (($f['size'] ?? 0) > 2 * 1024 * 1024) {
                $_SESSION['flash_error'] = 'La imagen supera 2MB.';
                header('Location: /publicar-empleo-detalles');
                exit;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);

            if (!array_key_exists($mime, $allowed)) {
                $_SESSION['flash_error'] = 'Formato de imagen no permitido. Use JPG, PNG o WebP.';
                header('Location: /publicar-empleo-detalles');
                exit;
            }

            $tmpDir = __DIR__ . '/../../storage/cache/uploads_tmp';
            if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

            $tmpName = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
            $tmpPath = $tmpDir . '/' . $tmpName;

            if (!move_uploaded_file($f['tmp_name'], $tmpPath)) {
                $_SESSION['flash_error'] = 'No se pudo preparar la imagen de portada.';
                header('Location: /publicar-empleo-detalles');
                exit;
            }

            $_SESSION['post_detalles_image'] = [
                'path' => $tmpPath,
                'mime' => $mime,
                'size' => filesize($tmpPath),
                'name' => $f['name'] ?? 'portada.' . $allowed[$mime],
            ];
        }

        require_once 'app/views/publicar-avance.php';
    }

    // paso: revisar (mostrar vista de revisión y aceptar condiciones)
    public function revisar()
    {
        if (empty($_SESSION['post_detalles'])) { header('Location: /publicar-empleo-detalles'); exit; }
        $turnstileEnabled = $this->turnstileEnabled();
        $turnstileSiteKey = $this->turnstileSiteKey();
        require_once 'app/views/publicar-revisar.php';
    }

    // paso: gracias (finalizar guardado si se confirma desde revisar)
    public function gracias()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (empty($_SESSION['publicacion_result'])) {
                header('Location: /publicar-empleo');
                exit;
            }
            $publicacion = $_SESSION['publicacion_result'];
            unset($_SESSION['publicacion_result']);
            require_once 'app/views/publicar-gracias.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /publicar-empleo'); exit; }
        // verificar CSRF
        require_once 'app/helpers/functions.php';
        verify_csrf();

        if (empty($_SESSION['post_detalles']) || !is_array($_SESSION['post_detalles'])) {
            $_SESSION['flash_error'] = 'La sesion de publicacion expiro. Completa nuevamente el formulario.';
            header('Location: /publicar-empleo-detalles');
            exit;
        }

        if (empty($_POST['acepta'])) {
            $_SESSION['flash_error'] = 'Debes aceptar los terminos y condiciones para publicar.';
            header('Location: /publicar-revisar');
            exit;
        }

        if ($this->turnstileEnabled()) {
            $token = trim((string)($_POST['cf-turnstile-response'] ?? ''));
            if ($token === '') {
                $_SESSION['flash_error'] = 'Confirma que no eres un bot para continuar.';
                header('Location: /publicar-revisar');
                exit;
            }

            $verify = $this->verifyTurnstileToken($token);
            if (empty($verify['ok'])) {
                $_SESSION['flash_error'] = $verify['message'] ?? 'No se pudo validar la verificacion anti-bots. Intenta nuevamente.';
                header('Location: /publicar-revisar');
                exit;
            }
        }

        // Rehidratar datos del formulario previo para que guardar() reciba todos los campos.
        $_POST = array_merge($_SESSION['post_detalles'], $_POST);
        $this->guardar(true);
    }

    // AJAX: obtener provincias por departamento
    public function provincias()
    {
        global $conexion;
        $departamento_id = $_GET['departamento_id'] ?? 0;
        $stmt = $conexion->prepare("SELECT id,nombre FROM provincias WHERE departamento_id = ? AND estado = 'activo' ORDER BY nombre");
        $stmt->execute([$departamento_id]);
        $provincias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($provincias);
        exit;
    }

    // AJAX: obtener distritos por provincia
    public function distritos()
    {
        global $conexion;
        $provincia_id = $_GET['provincia_id'] ?? 0;
        $stmt = $conexion->prepare("SELECT id,nombre FROM distritos WHERE provincia_id = ? AND estado = 'activo' ORDER BY nombre");
        $stmt->execute([$provincia_id]);
        $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($distritos);
        exit;
    }

    // =========================
    // PASO 3
    // =========================
    public function guardar($fromWizard = false) {
        global $conexion;

        // Compatibilidad: permitir texto libre en tipo_trabajo si la columna viene de esquema antiguo ENUM.
        $this->ensureTipoTrabajoIsTextColumn();
        // Compatibilidad: permitir texto libre en salario (ej. "A tratar") en esquemas antiguos numéricos.
        $this->ensureSalarioIsTextColumn();

        $isEdit = !empty($_POST['empleo_id']);
        $isAdminUser = is_admin_user($conexion);

        // validar sesión solo para creación (en edición no pasa por paso 1)
        if (!$isEdit && (!isset($_SESSION['categoria_id']) || !isset($_SESSION['subcategoria_id']))) {
            $_SESSION['flash_error'] = 'La sesión de publicación expiró. Vuelve al paso 1.';
            header('Location: /publicar-empleo');
            exit;
        }

        $erroresDetalles = $this->validarCamposObligatoriosDetalles($_POST);
        if (!empty($erroresDetalles)) {
            $_SESSION['flash_error'] = implode(' ', $erroresDetalles);
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/publicar-empleo-detalles'));
            exit;
        }

        $sql = "INSERT INTO empleos (
            usuario_id,
            categoria_id,
            subcategoria_id,
            titulo,
            slug,
            empresa,
            salario,
            tipo_trabajo,
            modalidad,
            departamento_id,
            provincia_id,
            distrito_id,
            telefono,
            email_contacto,
            tags,
            descripcion,
            destacado,
            imagen,
            estado,
            visitas,
            meta_title,
            meta_description
        ) VALUES (
            :usuario_id,
            :categoria_id,
            :subcategoria_id,
            :titulo,
            :slug,
            :empresa,
            :salario,
            :tipo_trabajo,
            :modalidad,
            :departamento_id,
            :provincia_id,
            :distrito_id,
            :telefono,
            :email_contacto,
            :tags,
            :descripcion,
            :destacado,
            :imagen,
            :estado,
            :visitas,
            :meta_title,
            :meta_description
        )";

        $stmt = $conexion->prepare($sql);

        $titulo = trim($_POST['titulo'] ?? '');
        $empresa = trim((string)($_POST['empresa'] ?? ''));
        $salarioRaw = trim((string)($_POST['salario'] ?? ''));
        if ($salarioRaw === '__MONTO__') {
            $salarioRaw = trim((string)($_POST['salario_monto'] ?? ''));
        }
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $emailContacto = trim((string)($_POST['email_contacto'] ?? ''));
        $tags = trim((string)($_POST['tags'] ?? ''));
        $slugBase = $this->slugify($titulo);

        $tipoTrabajo = $_POST['tipo_trabajo'] ?? '';
        $tipoTrabajo = trim((string)$tipoTrabajo);

        $modalidad = $_POST['modalidad'] ?? '';
        $modalidadesValidas = ['presencial', 'remoto', 'hibrido'];

        if ($titulo === '' || $empresa === '' || $salarioRaw === '' || $telefono === '' || $descripcion === '' || $tipoTrabajo === '' || !in_array($modalidad, $modalidadesValidas, true)) {
            $_SESSION['flash_error'] = 'Completa todos los campos obligatorios (email y tags son opcionales).';
            $back = $isEdit ? '/editar-empleo?id=' . intval($_POST['empleo_id'] ?? 0) : '/publicar-empleo-detalles';
            header('Location: ' . $back);
            exit;
        }

        $salario = mb_substr($salarioRaw, 0, 120);

        // Evita IDs vacíos ("" => 0) que rompen FK al guardar desde el paso 4.
        $departamentoId = (int)($_POST['departamento_id'] ?? 0);
        $provinciaId = (int)($_POST['provincia_id'] ?? 0);
        $distritoId = (int)($_POST['distrito_id'] ?? 0);
        if ($modalidad === 'presencial' || $modalidad === 'hibrido') {
            if ($departamentoId <= 0 || $provinciaId <= 0 || $distritoId <= 0) {
                $_SESSION['flash_error'] = 'Para modalidad presencial o hibrida debes seleccionar departamento, provincia y distrito.';
                $back = $isEdit ? '/editar-empleo?id=' . intval($_POST['empleo_id'] ?? 0) : '/publicar-empleo-detalles';
                header('Location: ' . $back);
                exit;
            }
        } else {
            if ($departamentoId <= 0) $departamentoId = 1;
            if ($provinciaId <= 0) $provinciaId = 1;
            if ($distritoId <= 0) $distritoId = 1;
        }

        $usuario_id = $_SESSION['user_id'] ?? 1;
        $ownerUserId = $usuario_id;

        // si viene empleo_id => edición
        $existingImagen = '';
        $existingDestacado = 0;
        if ($isEdit) {
            $eid = intval($_POST['empleo_id']);
            if ($isAdminUser) {
                $s = $conexion->prepare("SELECT usuario_id, imagen, destacado FROM empleos WHERE id = ? LIMIT 1");
                $s->execute([$eid]);
            } else {
                $s = $conexion->prepare("SELECT usuario_id, imagen, destacado FROM empleos WHERE id = ? AND usuario_id = ? LIMIT 1");
                $s->execute([$eid, $usuario_id]);
            }
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $ownerUserId = (int)($row['usuario_id'] ?? $usuario_id);
                $existingImagen = $row['imagen'];
                $existingDestacado = (int)($row['destacado'] ?? 0);
            } else {
                $_SESSION['flash_error'] = 'No se encontro el anuncio para editar.';
                header('Location: /mis-anuncios');
                exit;
            }
        }

        $duplicados = $this->detectarDuplicadosUsuario(
            $ownerUserId,
            $titulo,
            $telefono,
            $descripcion,
            $salario,
            $isEdit ? intval($_POST['empleo_id']) : null
        );
        if (!empty($duplicados)) {
            $_SESSION['flash_error'] = 'No puedes crear anuncios repetidos. Campos duplicados: ' . implode(', ', $duplicados) . '.';
            $back = $isEdit ? '/editar-empleo?id=' . intval($_POST['empleo_id'] ?? 0) : '/publicar-empleo-detalles';
            header('Location: ' . $back);
            exit;
        }

        $destacadoValue = isset($_POST['destacado']) ? 1 : 0;
        if ($isEdit && !isset($_POST['destacado'])) {
            $destacadoValue = 0;
        }

        $slug = $this->generarSlugUnico($slugBase, $isEdit ? intval($_POST['empleo_id']) : null);

        // manejar imagen si se sube (permitir upload tanto en nuevo como en edición)
        $imagenRuta = '';
        $tempImage = $_SESSION['post_detalles_image'] ?? null;
        $sourceTmp = null;
        $sourceSize = 0;
        $sourceName = '';

        if (isset($_FILES['image_portada']) && ($_FILES['image_portada']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $sourceTmp = $_FILES['image_portada']['tmp_name'];
            $sourceSize = (int)($_FILES['image_portada']['size'] ?? 0);
            $sourceName = $_FILES['image_portada']['name'] ?? 'portada';
        } elseif (!empty($tempImage['path']) && is_file($tempImage['path'])) {
            $sourceTmp = $tempImage['path'];
            $sourceSize = (int)($tempImage['size'] ?? filesize($tempImage['path']));
            $sourceName = $tempImage['name'] ?? basename($tempImage['path']);
        }

        if (!empty($sourceTmp) && is_file($sourceTmp)) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if ($sourceSize > 2 * 1024 * 1024) {
                $_SESSION['flash_error'] = 'La imagen supera 2MB.';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/publicar-empleo-detalles'));
                exit;
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $sourceTmp);
            finfo_close($finfo);
            if (!array_key_exists($mime, $allowed)) {
                $_SESSION['flash_error'] = 'Formato de imagen no permitido. Use JPG, PNG o WebP.';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/publicar-empleo-detalles'));
                exit;
            }
            $ext = $allowed[$mime];
            $dir = __DIR__ . '/../../uploads/empleos';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $nombre = bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = $dir . '/' . $nombre;
            $moved = false;
            if (isset($_FILES['image_portada']) && ($_FILES['image_portada']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $moved = move_uploaded_file($sourceTmp, $dest);
            } else {
                $moved = @rename($sourceTmp, $dest);
                if (!$moved) {
                    $moved = @copy($sourceTmp, $dest);
                    if ($moved) @unlink($sourceTmp);
                }
            }

            if (!$moved) {
                $_SESSION['flash_error'] = 'Error al guardar la imagen.';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/publicar-empleo-detalles'));
                exit;
            }
            // ruta accesible desde web
            $imagenRuta = 'uploads/empleos/' . $nombre;
            // generar miniatura
            $thumbName = 'thumb_' . $nombre;
            $thumbDest = $dir . '/' . $thumbName;
            try {
                if (!function_exists('getimagesize') || !function_exists('imagecreatetruecolor')) {
                    throw new RuntimeException('GD no disponible para generar miniatura.');
                }

                $sizeData = getimagesize($dest);
                if ($sizeData === false) {
                    throw new RuntimeException('No se pudo leer el tamano de la imagen.');
                }

                list($srcW, $srcH) = $sizeData;
                $maxW = 320; $maxH = 180;
                $ratio = min($maxW / $srcW, $maxH / $srcH, 1);
                $newW = (int)($srcW * $ratio);
                $newH = (int)($srcH * $ratio);

                $srcImg = null;
                if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) $srcImg = imagecreatefromjpeg($dest);
                elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) $srcImg = imagecreatefrompng($dest);
                elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $srcImg = imagecreatefromwebp($dest);

                if ($srcImg) {
                    $thumb = imagecreatetruecolor($newW, $newH);
                    // preserve transparency for PNG/WebP
                    if ($mime === 'image/png' || $mime === 'image/webp') {
                        imagealphablending($thumb, false);
                        imagesavealpha($thumb, true);
                        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                        imagefilledrectangle($thumb, 0, 0, $newW, $newH, $transparent);
                    }
                    imagecopyresampled($thumb, $srcImg, 0,0,0,0, $newW, $newH, $srcW, $srcH);
                    if ($ext === 'jpg') imagejpeg($thumb, $thumbDest, 85);
                    elseif ($ext === 'png') imagepng($thumb, $thumbDest, 6);
                    elseif ($ext === 'webp' && function_exists('imagewebp')) imagewebp($thumb, $thumbDest, 85);
                    imagedestroy($thumb);
                    imagedestroy($srcImg);
                }
            } catch (Throwable $e) {
                // no bloquear publicación por fallo en miniatura
            }
            // si había una imagen previa y estamos en edición, eliminarla
            if ($isEdit && $existingImagen) {
                $old = __DIR__ . '/../../' . $existingImagen;
                if (is_file($old)) @unlink($old);
                // eliminar miniatura previa si existe
                $oldBase = basename($existingImagen);
                $oldThumb = __DIR__ . '/../../uploads/empleos/thumb_' . $oldBase;
                if (is_file($oldThumb)) @unlink($oldThumb);
            }
        }

        $this->limpiarImagenTemporal();

        // si el usuario solicitó eliminar la imagen existente
        if ($isEdit && isset($_POST['remove_image']) && $_POST['remove_image']=='1') {
            if ($existingImagen) {
                $old = __DIR__ . '/../../' . $existingImagen;
                if (is_file($old)) @unlink($old);
                $oldBase = basename($existingImagen);
                $oldThumb = __DIR__ . '/../../uploads/empleos/thumb_' . $oldBase;
                if (is_file($oldThumb)) @unlink($oldThumb);
            }
            $imagenRuta = '';
        }

        // Si estamos en edición y no se subió una nueva imagen ni se solicitó eliminarla,
        // conservar la ruta de la imagen existente en la base de datos.
        if ($isEdit && empty($imagenRuta) && !(isset($_POST['remove_image']) && $_POST['remove_image']=='1')) {
            $imagenRuta = $existingImagen;
        }

        $estadoNuevo = $destacadoValue === 1 ? 'pendiente_aprobacion' : 'publicado';

        try {
            if ($isEdit) {
                // update existing
                $sqlUpdate = "UPDATE empleos SET 
                    titulo = :titulo, slug = :slug, empresa = :empresa, salario = :salario, tipo_trabajo = :tipo_trabajo, modalidad = :modalidad,
                    departamento_id = :departamento_id, provincia_id = :provincia_id, distrito_id = :distrito_id,
                    telefono = :telefono, email_contacto = :email_contacto, tags = :tags, descripcion = :descripcion,
                    destacado = :destacado, imagen = :imagen, estado = :estado
                    WHERE id = :id";

                if (!$isAdminUser) {
                    $sqlUpdate .= ' AND usuario_id = :usuario_id';
                }

                $stmtU = $conexion->prepare($sqlUpdate);
                $paramsUpdate = [
                    'titulo' => $titulo,
                    'slug' => $slug,
                    'empresa' => $empresa,
                    'salario' => $salario,
                    'tipo_trabajo' => $tipoTrabajo,
                    'modalidad' => $modalidad,
                    'departamento_id' => $departamentoId,
                    'provincia_id' => $provinciaId,
                    'distrito_id' => $distritoId,
                    'telefono' => $telefono,
                    'email_contacto' => $emailContacto,
                    'tags' => $tags,
                    'descripcion' => $descripcion,
                    'destacado' => $destacadoValue,
                    'imagen' => $imagenRuta,
                    'estado' => $estadoNuevo,
                    'id' => intval($_POST['empleo_id']),
                ];

                if (!$isAdminUser) {
                    $paramsUpdate['usuario_id'] = $usuario_id;
                }

                $stmtU->execute($paramsUpdate);
            } else {
                $stmt->execute([
                    'usuario_id' => $ownerUserId,

                    'categoria_id' => $_SESSION['categoria_id'],
                    'subcategoria_id' => $_SESSION['subcategoria_id'],

                    'titulo' => $titulo,
                    'slug' => $slug,
                    'empresa' => $empresa,
                    'salario' => $salario,
                    'tipo_trabajo' => $tipoTrabajo,
                    'modalidad' => $modalidad,

                    'departamento_id' => $departamentoId,
                    'provincia_id' => $provinciaId,
                    'distrito_id' => $distritoId,

                    'telefono' => $telefono,
                    'email_contacto' => $emailContacto,
                    'tags' => $tags,
                    'descripcion' => $descripcion,

                    'destacado' => $destacadoValue,

                    'imagen' => $imagenRuta,
                    'estado' => $estadoNuevo,
                    'visitas' => 0,

                    'meta_title' => $titulo,
                    'meta_description' => substr($titulo, 0, 150)
                ]);
            }
        } catch (Throwable $e) {
            error_log('[EmpleoController::guardar] Error al guardar anuncio: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo guardar el anuncio. Verifica los datos e intenta nuevamente.';
            $back = $isEdit ? '/editar-empleo?id=' . intval($_POST['empleo_id'] ?? 0) : '/publicar-revisar';
            header('Location: ' . $back);
            exit;
        }

        // limpiar sesión solo si no es edición (para nueva publicación)
        if (!$isEdit) {
            notify_new_listing(
                $conexion,
                $usuario_id,
                $titulo,
                $destacadoValue,
                $estadoNuevo
            );

            unset($_SESSION['categoria_id']);
            unset($_SESSION['subcategoria_id']);
            unset($_SESSION['post_detalles']);
            unset($_SESSION['post_detalles_image']);
        }

        if ($fromWizard) {
            $_SESSION['publicacion_result'] = [
                'destacado' => (int)$destacadoValue,
                'estado' => $estadoNuevo,
            ];
            header('Location: /publicar-gracias');
            exit;
        }

        // flash de éxito y redirigir a mis anuncios
        $_SESSION['flash_success'] = $estadoNuevo === 'publicado'
            ? 'Empleo publicado correctamente.'
            : 'Tu anuncio destacado fue enviado y está pendiente de aprobación.';
        header('Location: /mis-anuncios');
        exit;
    }

    private function ensureTipoTrabajoIsTextColumn()
    {
        global $conexion;

        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $stmt = $conexion->prepare("SHOW COLUMNS FROM empleos LIKE 'tipo_trabajo'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            $type = strtolower((string)($col['Type'] ?? ''));

            if ($type !== '' && strpos($type, 'enum(') === 0) {
                $conexion->exec("ALTER TABLE empleos MODIFY tipo_trabajo VARCHAR(120) NOT NULL");
            }
        } catch (Throwable $e) {
            @file_put_contents(
                __DIR__ . '/../../storage/logs/app.log',
                '[' . date('Y-m-d H:i:s') . '] ensureTipoTrabajoIsTextColumn: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    private function ensureSalarioIsTextColumn()
    {
        global $conexion;

        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $stmt = $conexion->prepare("SHOW COLUMNS FROM empleos LIKE 'salario'");
            $stmt->execute();
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            $type = strtolower((string)($col['Type'] ?? ''));

            $isNumeric = strpos($type, 'int') !== false
                || strpos($type, 'decimal') !== false
                || strpos($type, 'float') !== false
                || strpos($type, 'double') !== false;

            if ($type !== '' && $isNumeric) {
                $conexion->exec("ALTER TABLE empleos MODIFY salario VARCHAR(120) NOT NULL");
            }
        } catch (Throwable $e) {
            @file_put_contents(
                __DIR__ . '/../../storage/logs/app.log',
                '[' . date('Y-m-d H:i:s') . '] ensureSalarioIsTextColumn: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    private function slugify($text)
    {
        $text = trim((string)$text);
        if ($text === '') return '';
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-zA-Z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = strtolower(trim($text, '-'));
        return $text;
    }

    private function generarSlugUnico($baseSlug, $excludeId = null)
    {
        global $conexion;

        $baseSlug = trim((string)$baseSlug);
        if ($baseSlug === '') {
            $baseSlug = 'anuncio-' . date('Ymd-His');
        }

        $slug = $baseSlug;
        $i = 1;
        while (true) {
            $sql = 'SELECT id FROM empleos WHERE slug = ? LIMIT 1';
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$slug]);
            $found = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$found || (!empty($excludeId) && (int)$found['id'] === (int)$excludeId)) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $i;
            $i++;
        }
    }

    private function limpiarImagenTemporal()
    {
        if (!empty($_SESSION['post_detalles_image']['path'])) {
            $tmpPath = $_SESSION['post_detalles_image']['path'];
            if (is_file($tmpPath)) @unlink($tmpPath);
        }
        unset($_SESSION['post_detalles_image']);
    }

    public function misAnuncios()
    {
        global $conexion;
        $this->limpiarSolicitudesDestacadasExpiradas();
        $this->ensureFeaturedExpiryColumn();
        $this->ensureFeaturedNoticeColumn();
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $isAdminView = is_admin_user($conexion);
        $searchTerm = trim((string)($_GET['q'] ?? ''));
        $autoDeleteConfig = $this->getAutoDeleteConfig();

        if ($isAdminView && $_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'update_auto_delete_config') {
            verify_csrf();

            $allowedDays = [7, 15, 30, 45, 60, 90, 120, 180, 365];
            $freeDays = (int)($_POST['free_days'] ?? $autoDeleteConfig['free_days']);

            if (!in_array($freeDays, $allowedDays, true)) {
                $_SESSION['flash_error'] = 'Selecciona valores validos para la vigencia de anuncios.';
                header('Location: /mis-anuncios');
                exit;
            }

            $autoDeleteConfig['free_days'] = $freeDays;
            $this->saveAutoDeleteConfig($autoDeleteConfig);

            // Aplicar limpieza inmediatamente tras actualizar reglas.
            $this->limpiarSolicitudesDestacadasExpiradas();

            $_SESSION['flash_success'] = 'Reglas de eliminacion automatica actualizadas correctamente.';
            header('Location: /mis-anuncios');
            exit;
        }

        if ($isAdminView) {
            $sql = "SELECT e.*, u.username AS usuario_nombre
                    FROM empleos e
                    INNER JOIN usuarios u ON u.id = e.usuario_id
                    WHERE e.estado <> 'eliminado'";

            $params = [];
            if ($searchTerm !== '') {
                $sql .= " AND (
                    e.titulo LIKE :q
                    OR e.empresa LIKE :q
                    OR e.telefono LIKE :q
                    OR u.username LIKE :q
                )";
                $params[':q'] = '%' . $searchTerm . '%';
            }

            $sql .= " ORDER BY e.id DESC";
            $stmt = $conexion->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
        } else {
            $sql = "SELECT * FROM empleos WHERE usuario_id = ? AND estado <> 'eliminado' ORDER BY id DESC";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
        }
        $mis = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $pendientesDestacados = [];
                if ($isAdminView) {
                    $pSql = "SELECT e.*, u.username AS usuario_nombre
                             FROM empleos e
                             INNER JOIN usuarios u ON u.id = e.usuario_id
                             WHERE e.destacado = 1
                                 AND e.estado = 'pendiente_aprobacion'
                                 AND e.estado <> 'eliminado'";

                    $pParams = [];
                    if ($searchTerm !== '') {
                        $pSql .= " AND (
                            e.titulo LIKE :q
                            OR e.empresa LIKE :q
                            OR e.telefono LIKE :q
                            OR u.username LIKE :q
                        )";
                        $pParams[':q'] = '%' . $searchTerm . '%';
                    }

                    $pSql .= " ORDER BY e.id DESC";
                        $pStmt = $conexion->prepare($pSql);
                    foreach ($pParams as $k => $v) {
                        $pStmt->bindValue($k, $v);
                    }
                        $pStmt->execute();
                        $pendientesDestacados = $pStmt->fetchAll(PDO::FETCH_ASSOC);
                }

        require_once 'app/views/mis-anuncios.php';
    }

    public function editar()
    {
        global $conexion;
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $isAdminUser = is_admin_user($conexion);
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /mis-anuncios'); exit; }
        if ($isAdminUser) {
            $sql = "SELECT * FROM empleos WHERE id = ? LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$id]);
        } else {
            $sql = "SELECT * FROM empleos WHERE id = ? AND usuario_id = ? LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$id, $_SESSION['user_id']]);
        }
        $empleo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$empleo) { header('Location: /mis-anuncios'); exit; }

        // Evita arrastrar datos previos en el formulario cuando se entra a editar.
        unset($_SESSION['post_detalles']);
        unset($_SESSION['post_detalles_image']);

        $deptStmt = $conexion->prepare("SELECT id,nombre FROM departamentos WHERE estado = 'activo' ORDER BY nombre");
        $deptStmt->execute();
        $departamentos = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

        require_once 'app/views/publicar-empleo-detalles.php';
    }

    public function eliminar()
    {
        global $conexion;

        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /mis-anuncios');
            exit;
        }

        verify_csrf();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'No se pudo identificar el anuncio a eliminar.';
            header('Location: /mis-anuncios');
            exit;
        }

        $isAdminView = is_admin_user($conexion);

        if ($isAdminView) {
            $sql = "UPDATE empleos
                    SET estado = 'eliminado', fecha_actualizacion = NOW()
                    WHERE id = ?
                    LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $ok = $stmt->execute([$id]);
        } else {
            $sql = "UPDATE empleos
                    SET estado = 'eliminado', fecha_actualizacion = NOW()
                    WHERE id = ? AND usuario_id = ?
                    LIMIT 1";
            $stmt = $conexion->prepare($sql);
            $ok = $stmt->execute([$id, $_SESSION['user_id']]);
        }

        if ($ok && $stmt->rowCount() > 0) {
            $_SESSION['flash_success'] = $isAdminView
                ? 'Anuncio eliminado correctamente por administrador.'
                : 'Anuncio eliminado correctamente.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo eliminar el anuncio.';
        }

        header('Location: /mis-anuncios');
        exit;
    }

    public function aprobarDestacado()
    {
        global $conexion;
        $this->limpiarSolicitudesDestacadasExpiradas();
        $this->ensureFeaturedExpiryColumn();
        $this->ensureFeaturedNoticeColumn();

        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if (!is_admin_user($conexion)) {
            $_SESSION['flash_error'] = 'No tienes permisos para aprobar anuncios destacados.';
            header('Location: /mis-anuncios');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /mis-anuncios');
            exit;
        }

        verify_csrf();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $featuredDays = isset($_POST['featured_days']) ? (int)$_POST['featured_days'] : 30;
        $allowedDays = [7, 15, 30, 45, 60, 90, 120, 180, 365];
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Anuncio invalido para aprobar.';
            header('Location: /mis-anuncios');
            exit;
        }
        if (!in_array($featuredDays, $allowedDays, true)) {
            $_SESSION['flash_error'] = 'Selecciona una vigencia valida para el destacado.';
            header('Location: /mis-anuncios');
            exit;
        }

        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $featuredDays . ' days'));

        $sql = "UPDATE empleos
                SET estado = 'publicado',
                    fecha_expiracion_destacado = :expires_at,
                    destacado_aviso_24h_enviado_at = NULL,
                    fecha_actualizacion = NOW()
                WHERE id = :id AND destacado = 1 AND estado = 'pendiente_aprobacion'
                LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $ok = $stmt->execute([
            ':expires_at' => $expiresAt,
            ':id' => $id,
        ]);

        if ($ok && $stmt->rowCount() > 0) {
            notify_featured_approved($conexion, $id);
            $_SESSION['flash_success'] = 'Anuncio destacado aprobado y publicado.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo aprobar el anuncio destacado.';
        }

        header('Location: /mis-anuncios');
        exit;
    }

    private function limpiarSolicitudesDestacadasExpiradas()
    {
        global $conexion;

        try {
            $this->ensureFeaturedExpiryColumn();
            $this->ensureFeaturedNoticeColumn();
            $this->enviarAvisosDestacadosPorExpirar();
            $cfg = $this->getAutoDeleteConfig();
            $freeDays = max(1, (int)($cfg['free_days'] ?? 30));

            $sqlFeatured = "UPDATE empleos
                    SET estado = 'eliminado', fecha_actualizacion = NOW()
                    WHERE estado <> 'eliminado'
                      AND destacado = 1
                                            AND fecha_expiracion_destacado IS NOT NULL
                                            AND fecha_expiracion_destacado <= NOW()";
            $conexion->exec($sqlFeatured);

            $sqlFree = "UPDATE empleos
                    SET estado = 'eliminado', fecha_actualizacion = NOW()
                    WHERE estado <> 'eliminado'
                      AND (destacado = 0 OR destacado IS NULL)
                      AND COALESCE(fecha_creacion, fecha_publicacion) <= DATE_SUB(NOW(), INTERVAL {$freeDays} DAY)";
            $conexion->exec($sqlFree);
        } catch (Throwable $e) {
            error_log('[EmpleoController::limpiarSolicitudesDestacadasExpiradas] ' . $e->getMessage());
        }
    }

    private function getAutoDeleteConfig()
    {
        $defaults = [
            'free_days' => 30,
        ];

        $path = __DIR__ . '/../../storage/cache/anuncios_auto_delete.json';
        if (!is_file($path)) {
            return $defaults;
        }

        try {
            $raw = file_get_contents($path);
            $json = json_decode((string)$raw, true);
            if (!is_array($json)) {
                return $defaults;
            }

            return [
                'free_days' => max(1, (int)($json['free_days'] ?? $defaults['free_days'])),
            ];
        } catch (Throwable $e) {
            return $defaults;
        }
    }

    private function saveAutoDeleteConfig(array $config)
    {
        $path = __DIR__ . '/../../storage/cache/anuncios_auto_delete.json';
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = [
            'free_days' => max(1, (int)($config['free_days'] ?? 30)),
        ];

        @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function ensureFeaturedExpiryColumn()
    {
        global $conexion;

        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $stmt = $conexion->prepare("SHOW COLUMNS FROM empleos LIKE 'fecha_expiracion_destacado'");
            $stmt->execute();
            $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

            if (!$exists) {
                $conexion->exec("ALTER TABLE empleos ADD COLUMN fecha_expiracion_destacado DATETIME NULL AFTER destacado");
            }
        } catch (Throwable $e) {
            @file_put_contents(
                __DIR__ . '/../../storage/logs/app.log',
                '[' . date('Y-m-d H:i:s') . '] ensureFeaturedExpiryColumn: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    private function ensureFeaturedNoticeColumn()
    {
        global $conexion;

        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $stmt = $conexion->prepare("SHOW COLUMNS FROM empleos LIKE 'destacado_aviso_24h_enviado_at'");
            $stmt->execute();
            $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

            if (!$exists) {
                $conexion->exec("ALTER TABLE empleos ADD COLUMN destacado_aviso_24h_enviado_at DATETIME NULL AFTER fecha_expiracion_destacado");
            }
        } catch (Throwable $e) {
            @file_put_contents(
                __DIR__ . '/../../storage/logs/app.log',
                '[' . date('Y-m-d H:i:s') . '] ensureFeaturedNoticeColumn: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    private function enviarAvisosDestacadosPorExpirar()
    {
        global $conexion;

        try {
            $sql = "SELECT e.id, e.titulo, e.fecha_expiracion_destacado, u.email, u.username
                    FROM empleos e
                    INNER JOIN usuarios u ON u.id = e.usuario_id
                    WHERE e.estado = 'publicado'
                      AND e.destacado = 1
                      AND e.fecha_expiracion_destacado IS NOT NULL
                      AND e.fecha_expiracion_destacado > NOW()
                      AND e.fecha_expiracion_destacado <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
                      AND e.destacado_aviso_24h_enviado_at IS NULL";
            $stmt = $conexion->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return;
            }

            foreach ($rows as $row) {
                $email = trim((string)($row['email'] ?? ''));
                if ($email === '') {
                    continue;
                }

                $titulo = trim((string)($row['titulo'] ?? 'Tu anuncio destacado'));
                $fechaExp = trim((string)($row['fecha_expiracion_destacado'] ?? ''));
                $fechaTexto = $fechaExp !== '' ? date('d/m/Y H:i', strtotime($fechaExp)) : 'en menos de 24 horas';

                $sent = notifications_send(
                    $email,
                    '[Anuncios Lima] Tu anuncio destacado sera eliminado en 24 horas',
                    'Vencimiento proximo de anuncio destacado',
                    'Tu anuncio destacado "' . $titulo . '" sera eliminado automaticamente en 24 horas (' . $fechaTexto . '). Si deseas renovarlo, la renovacion sera de paga. Si no renuevas, se eliminara automaticamente al vencer.',
                    [
                        'Anuncio' => $titulo,
                        'Vence' => $fechaTexto,
                        'Accion' => 'Ingresa a tu cuenta para gestionar la renovacion',
                    ]
                );

                if ($sent) {
                    $upd = $conexion->prepare("UPDATE empleos
                                             SET destacado_aviso_24h_enviado_at = NOW()
                                             WHERE id = :id
                                             LIMIT 1");
                    $upd->execute([':id' => (int)($row['id'] ?? 0)]);
                }
            }
        } catch (Throwable $e) {
            @file_put_contents(
                __DIR__ . '/../../storage/logs/app.log',
                '[' . date('Y-m-d H:i:s') . '] enviarAvisosDestacadosPorExpirar: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    private function appConfig()
    {
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg;
        }

        try {
            $cfg = require __DIR__ . '/../../config/app.php';
        } catch (Throwable $e) {
            $cfg = [];
        }

        return is_array($cfg) ? $cfg : [];
    }

    private function hasTableColumn($table, $column)
    {
        global $conexion;

        static $cache = [];
        $key = strtolower((string)$table) . '.' . strtolower((string)$column);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = $conexion->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
            $stmt->bindValue(':col', (string)$column);
            $stmt->execute();
            $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $cache[$key] = false;
        }

        return $cache[$key];
    }

    private function validarCamposObligatoriosDetalles(array $data)
    {
        $errores = [];

        $camposTexto = [
            'titulo' => 'El titulo del empleo es obligatorio.',
            'empresa' => 'La empresa es obligatoria.',
            'salario' => 'El salario es obligatorio.',
            'tipo_trabajo' => 'El tipo de trabajo es obligatorio.',
            'modalidad' => 'La modalidad es obligatoria.',
            'telefono' => 'El telefono es obligatorio.',
            'descripcion' => 'La descripcion es obligatoria.',
        ];

        foreach ($camposTexto as $campo => $mensaje) {
            if (trim((string)($data[$campo] ?? '')) === '') {
                $errores[] = $mensaje;
            }
        }

        // Ubicacion solo cuando la modalidad requiere presencia fisica.
        $modalidad = trim((string)($data['modalidad'] ?? ''));
        $requiereUbicacion = in_array($modalidad, ['presencial', 'hibrido'], true);
        if ($requiereUbicacion) {
            $camposUbicacion = [
                'departamento_id' => 'Debes seleccionar un departamento.',
                'provincia_id' => 'Debes seleccionar una provincia.',
                'distrito_id' => 'Debes seleccionar un distrito.',
            ];

            foreach ($camposUbicacion as $campo => $mensaje) {
                if ((int)($data[$campo] ?? 0) <= 0) {
                    $errores[] = $mensaje;
                }
            }
        }

        // Si es destacado, exigir imagen (nueva, temporal o existente sin eliminar).
        $destacado = (int)($data['destacado'] ?? 0) === 1;
        if ($destacado) {
            $tieneUploadNuevo = isset($_FILES['image_portada']) && (int)($_FILES['image_portada']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

            $tempImage = $_SESSION['post_detalles_image'] ?? null;
            $tieneTemporal = !empty($tempImage['path']) && is_file((string)$tempImage['path']);

            $tieneImagenActual = !empty($data['imagen_actual']) || !empty($data['imagen']);
            $eliminaActual = (int)($data['remove_image'] ?? 0) === 1;

            if (!$tieneUploadNuevo && !$tieneTemporal && (!$tieneImagenActual || $eliminaActual)) {
                $errores[] = 'Si marcas publicacion destacada, debes subir una imagen de portada.';
            }
        }

        return $errores;
    }

    private function detectarDuplicadosUsuario($usuarioId, $titulo, $telefono, $descripcion, $salario, $excludeId = null)
    {
        global $conexion;

                $sql = "SELECT titulo, descripcion
                FROM empleos
                WHERE usuario_id = :usuario_id
                  AND estado <> 'eliminado'";

        if (!empty($excludeId)) {
            $sql .= ' AND id <> :exclude_id';
        }

        $sql .= " AND (
                    LOWER(TRIM(titulo)) = LOWER(TRIM(:titulo))
                 OR LOWER(TRIM(descripcion)) = LOWER(TRIM(:descripcion))
                )
                LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->bindValue(':usuario_id', (int)$usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', (string)$titulo);
        $stmt->bindValue(':descripcion', (string)$descripcion);
        if (!empty($excludeId)) {
            $stmt->bindValue(':exclude_id', (int)$excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [];
        }

        $duplicados = [];
        if (mb_strtolower(trim((string)($row['titulo'] ?? ''))) === mb_strtolower(trim((string)$titulo))) {
            $duplicados[] = 'titulo';
        }
        if (mb_strtolower(trim((string)($row['descripcion'] ?? ''))) === mb_strtolower(trim((string)$descripcion))) {
            $duplicados[] = 'descripcion';
        }

        return $duplicados;
    }

    private function turnstileEnabled()
    {
        $cfg = $this->appConfig();
        return !empty($cfg['turnstile']['enabled']);
    }

    private function turnstileSiteKey()
    {
        $cfg = $this->appConfig();
        $siteKey = trim((string)($cfg['turnstile']['site_key'] ?? ''));
        if ($this->isLocalEnvironment()) {
            return '1x00000000000000000000AA';
        }
        return $siteKey;
    }

    private function turnstileSecretKey()
    {
        $cfg = $this->appConfig();
        $secretKey = trim((string)($cfg['turnstile']['secret_key'] ?? ''));
        if ($this->isLocalEnvironment()) {
            return '1x0000000000000000000000000000000AA';
        }
        return $secretKey;
    }

    private function isLocalEnvironment()
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $host = trim(explode(':', $host)[0]);

        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        return (bool)preg_match('/(^|\.)local$/', $host);
    }

    private function verifyTurnstileToken($token)
    {
        $secret = $this->turnstileSecretKey();
        if ($secret === '') {
            return [
                'ok' => false,
                'message' => 'La configuracion anti-bots esta incompleta en el servidor.',
            ];
        }

        $payload = http_build_query([
            'secret' => $secret,
            'response' => (string)$token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $raw = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $raw = curl_exec($ch);
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $payload,
                    'timeout' => 20,
                ],
            ]);
            $raw = @file_get_contents($url, false, $ctx);
        }

        if ($raw === false) {
            return [
                'ok' => false,
                'message' => 'No se pudo conectar con el verificador anti-bots.',
            ];
        }

        $json = json_decode((string)$raw, true);
        if (!is_array($json)) {
            return [
                'ok' => false,
                'message' => 'Respuesta invalida del verificador anti-bots.',
            ];
        }

        if (!empty($json['success'])) {
            return ['ok' => true];
        }

        $errorCodes = [];
        if (!empty($json['error-codes']) && is_array($json['error-codes'])) {
            $errorCodes = $json['error-codes'];
        }
        $errorText = !empty($errorCodes) ? (' (' . implode(', ', $errorCodes) . ')') : '';

        return [
            'ok' => false,
            'message' => 'Verificacion anti-bots rechazada' . $errorText . '.',
        ];
    }

    public function gestionLugares()
    {
        global $conexion;

        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if (!is_admin_user($conexion)) {
            $_SESSION['flash_error'] = 'No tienes permisos para gestionar lugares.';
            header('Location: /');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? '';

            if ($action === 'crear_departamento') {
                $nombre = trim((string)($_POST['nombre_departamento'] ?? ''));
                if ($nombre === '') {
                    $_SESSION['flash_error'] = 'Debes ingresar el nombre del departamento.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $check = $conexion->prepare('SELECT id FROM departamentos WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
                $check->execute([$nombre]);
                if ($check->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['flash_error'] = 'Ese departamento ya existe.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare("INSERT INTO departamentos (nombre, estado) VALUES (?, 'activo')");
                $ok = $stmt->execute([mb_substr($nombre, 0, 100)]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Departamento creado correctamente.'
                    : 'No se pudo crear el departamento.';
                header('Location: /gestion-lugares');
                exit;
            }

            if ($action === 'crear_provincia') {
                $departamentoId = (int)($_POST['departamento_id'] ?? 0);
                $nombre = trim((string)($_POST['nombre_provincia'] ?? ''));

                if ($departamentoId <= 0 || $nombre === '') {
                    $_SESSION['flash_error'] = 'Selecciona departamento e ingresa el nombre de la provincia.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $dep = $conexion->prepare('SELECT id FROM departamentos WHERE id = ? LIMIT 1');
                $dep->execute([$departamentoId]);
                if (!$dep->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['flash_error'] = 'El departamento seleccionado no existe.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $check = $conexion->prepare('SELECT id FROM provincias WHERE departamento_id = ? AND LOWER(nombre) = LOWER(?) LIMIT 1');
                $check->execute([$departamentoId, $nombre]);
                if ($check->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['flash_error'] = 'Esa provincia ya existe en el departamento seleccionado.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare("INSERT INTO provincias (departamento_id, nombre, estado) VALUES (?, ?, 'activo')");
                $ok = $stmt->execute([$departamentoId, mb_substr($nombre, 0, 100)]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Provincia creada correctamente.'
                    : 'No se pudo crear la provincia.';
                header('Location: /gestion-lugares');
                exit;
            }

            if ($action === 'crear_distrito') {
                $provinciaId = (int)($_POST['provincia_id'] ?? 0);
                $nombre = trim((string)($_POST['nombre_distrito'] ?? ''));

                if ($provinciaId <= 0 || $nombre === '') {
                    $_SESSION['flash_error'] = 'Selecciona provincia e ingresa el nombre del distrito.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $prov = $conexion->prepare('SELECT id FROM provincias WHERE id = ? LIMIT 1');
                $prov->execute([$provinciaId]);
                if (!$prov->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['flash_error'] = 'La provincia seleccionada no existe.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $check = $conexion->prepare('SELECT id FROM distritos WHERE provincia_id = ? AND LOWER(nombre) = LOWER(?) LIMIT 1');
                $check->execute([$provinciaId, $nombre]);
                if ($check->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['flash_error'] = 'Ese distrito ya existe en la provincia seleccionada.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare("INSERT INTO distritos (provincia_id, nombre, estado) VALUES (?, ?, 'activo')");
                $ok = $stmt->execute([$provinciaId, mb_substr($nombre, 0, 100)]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Distrito creado correctamente.'
                    : 'No se pudo crear el distrito.';
                header('Location: /gestion-lugares');
                exit;
            }

            if ($action === 'editar_departamento') {
                $id = (int)($_POST['departamento_id'] ?? 0);
                $nombre = trim((string)($_POST['nombre_departamento'] ?? ''));

                if ($id <= 0 || $nombre === '') {
                    $_SESSION['flash_error'] = 'Datos invalidos para editar departamento.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $check = $conexion->prepare('SELECT id FROM departamentos WHERE LOWER(nombre) = LOWER(?) AND id <> ? LIMIT 1');
                $check->execute([$nombre, $id]);
                if ($check->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['flash_error'] = 'Ya existe otro departamento con ese nombre.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare('UPDATE departamentos SET nombre = ? WHERE id = ?');
                $ok = $stmt->execute([mb_substr($nombre, 0, 100), $id]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Departamento actualizado.'
                    : 'No se pudo actualizar el departamento.';
                header('Location: /gestion-lugares');
                exit;
            }

            if ($action === 'eliminar_departamento') {
                $id = (int)($_POST['departamento_id'] ?? 0);
                if ($id <= 0) {
                    $_SESSION['flash_error'] = 'Departamento invalido.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $countProv = $conexion->prepare('SELECT COUNT(*) FROM provincias WHERE departamento_id = ?');
                $countProv->execute([$id]);
                if ((int)$countProv->fetchColumn() > 0) {
                    $_SESSION['flash_error'] = 'No se puede eliminar: el departamento tiene provincias asociadas.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $countEmp = $conexion->prepare('SELECT COUNT(*) FROM empleos WHERE departamento_id = ?');
                $countEmp->execute([$id]);
                if ((int)$countEmp->fetchColumn() > 0) {
                    $_SESSION['flash_error'] = 'No se puede eliminar: el departamento tiene empleos asociados.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare('DELETE FROM departamentos WHERE id = ?');
                $ok = $stmt->execute([$id]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Departamento eliminado.'
                    : 'No se pudo eliminar el departamento.';
                header('Location: /gestion-lugares');
                exit;
            }

            if ($action === 'editar_provincia') {
                $id = (int)($_POST['provincia_id'] ?? 0);
                $nombre = trim((string)($_POST['nombre_provincia'] ?? ''));

                if ($id <= 0 || $nombre === '') {
                    $_SESSION['flash_error'] = 'Datos invalidos para editar provincia.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $current = $conexion->prepare('SELECT departamento_id FROM provincias WHERE id = ? LIMIT 1');
                $current->execute([$id]);
                $row = $current->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    $_SESSION['flash_error'] = 'La provincia no existe.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $check = $conexion->prepare('SELECT id FROM provincias WHERE departamento_id = ? AND LOWER(nombre) = LOWER(?) AND id <> ? LIMIT 1');
                $check->execute([(int)$row['departamento_id'], $nombre, $id]);
                if ($check->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['flash_error'] = 'Ya existe otra provincia con ese nombre en el departamento.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare('UPDATE provincias SET nombre = ? WHERE id = ?');
                $ok = $stmt->execute([mb_substr($nombre, 0, 100), $id]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Provincia actualizada.'
                    : 'No se pudo actualizar la provincia.';
                header('Location: /gestion-lugares');
                exit;
            }

            if ($action === 'eliminar_provincia') {
                $id = (int)($_POST['provincia_id'] ?? 0);
                if ($id <= 0) {
                    $_SESSION['flash_error'] = 'Provincia invalida.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $countDist = $conexion->prepare('SELECT COUNT(*) FROM distritos WHERE provincia_id = ?');
                $countDist->execute([$id]);
                if ((int)$countDist->fetchColumn() > 0) {
                    $_SESSION['flash_error'] = 'No se puede eliminar: la provincia tiene distritos asociados.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $countEmp = $conexion->prepare('SELECT COUNT(*) FROM empleos WHERE provincia_id = ?');
                $countEmp->execute([$id]);
                if ((int)$countEmp->fetchColumn() > 0) {
                    $_SESSION['flash_error'] = 'No se puede eliminar: la provincia tiene empleos asociados.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare('DELETE FROM provincias WHERE id = ?');
                $ok = $stmt->execute([$id]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Provincia eliminada.'
                    : 'No se pudo eliminar la provincia.';
                header('Location: /gestion-lugares');
                exit;
            }

            if ($action === 'editar_distrito') {
                $id = (int)($_POST['distrito_id'] ?? 0);
                $nombre = trim((string)($_POST['nombre_distrito'] ?? ''));

                if ($id <= 0 || $nombre === '') {
                    $_SESSION['flash_error'] = 'Datos invalidos para editar distrito.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $current = $conexion->prepare('SELECT provincia_id FROM distritos WHERE id = ? LIMIT 1');
                $current->execute([$id]);
                $row = $current->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    $_SESSION['flash_error'] = 'El distrito no existe.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $check = $conexion->prepare('SELECT id FROM distritos WHERE provincia_id = ? AND LOWER(nombre) = LOWER(?) AND id <> ? LIMIT 1');
                $check->execute([(int)$row['provincia_id'], $nombre, $id]);
                if ($check->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION['flash_error'] = 'Ya existe otro distrito con ese nombre en la provincia.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare('UPDATE distritos SET nombre = ? WHERE id = ?');
                $ok = $stmt->execute([mb_substr($nombre, 0, 100), $id]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Distrito actualizado.'
                    : 'No se pudo actualizar el distrito.';
                header('Location: /gestion-lugares');
                exit;
            }

            if ($action === 'eliminar_distrito') {
                $id = (int)($_POST['distrito_id'] ?? 0);
                if ($id <= 0) {
                    $_SESSION['flash_error'] = 'Distrito invalido.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $countEmp = $conexion->prepare('SELECT COUNT(*) FROM empleos WHERE distrito_id = ?');
                $countEmp->execute([$id]);
                if ((int)$countEmp->fetchColumn() > 0) {
                    $_SESSION['flash_error'] = 'No se puede eliminar: el distrito tiene empleos asociados.';
                    header('Location: /gestion-lugares');
                    exit;
                }

                $stmt = $conexion->prepare('DELETE FROM distritos WHERE id = ?');
                $ok = $stmt->execute([$id]);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Distrito eliminado.'
                    : 'No se pudo eliminar el distrito.';
                header('Location: /gestion-lugares');
                exit;
            }

            $_SESSION['flash_error'] = 'Accion no valida.';
            header('Location: /gestion-lugares');
            exit;
        }

        $departamentosStmt = $conexion->query("SELECT id, nombre, estado FROM departamentos ORDER BY nombre ASC");
        $departamentos = $departamentosStmt ? $departamentosStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $provinciasStmt = $conexion->query("SELECT p.id, p.nombre, p.estado, d.nombre AS departamento_nombre FROM provincias p INNER JOIN departamentos d ON d.id = p.departamento_id ORDER BY d.nombre ASC, p.nombre ASC");
        $provincias = $provinciasStmt ? $provinciasStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $distritosStmt = $conexion->query("SELECT di.id, di.nombre, di.estado, p.nombre AS provincia_nombre, d.nombre AS departamento_nombre FROM distritos di INNER JOIN provincias p ON p.id = di.provincia_id INNER JOIN departamentos d ON d.id = p.departamento_id ORDER BY d.nombre ASC, p.nombre ASC, di.nombre ASC");
        $distritos = $distritosStmt ? $distritosStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        require_once 'app/views/gestion-lugares.php';
    }

}