<?php

session_start();

define('BASE_PATH', '');

require_once 'config/database.php';
require_once 'app/helpers/functions.php';

$database = new Database();
$conexion = $database->connect();

require_once 'app/controllers/HomeController.php';
require_once 'app/controllers/LoginController.php';
require_once 'app/controllers/RegistroController.php';
require_once 'app/controllers/AuthController.php';
require_once 'app/controllers/EmpleoController.php';
require_once 'app/controllers/CategoriaController.php';
require_once 'app/models/Publicidad.php';

$url = $_GET['url'] ?? '';

switch ($url) {

    case 'robots.txt':
        header('Content-Type: text/plain; charset=UTF-8');
        $appCfg = require 'config/app.php';
        $appUrl = rtrim((string)($appCfg['app_url'] ?? 'http://localhost'), '/');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Sitemap: " . $appUrl . "/sitemap.xml\n";
        exit;

    case 'sitemap.xml':
        header('Content-Type: application/xml; charset=UTF-8');
        $appCfg = require 'config/app.php';
        $appUrl = rtrim((string)($appCfg['app_url'] ?? 'http://localhost'), '/');

        $urls = [
            $appUrl . '/',
            $appUrl . '/empleos',
            $appUrl . '/publicidades',
            $appUrl . '/publicar-empleo',
        ];

        $stmtMap = $conexion->prepare("SELECT slug, COALESCE(fecha_actualizacion, fecha_publicacion, fecha_creacion) AS lastmod FROM empleos WHERE estado = 'publicado' OR estado = '' OR estado IS NULL ORDER BY id DESC LIMIT 5000");
        $stmtMap->execute();
        $jobs = $stmtMap->fetchAll(PDO::FETCH_ASSOC);

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $u) {
            echo "  <url><loc>" . htmlspecialchars($u, ENT_XML1) . "</loc></url>\n";
        }

        foreach ($jobs as $j) {
            if (empty($j['slug'])) continue;
            $loc = $appUrl . '/empleo/' . rawurlencode((string)$j['slug']);
            $lastmod = !empty($j['lastmod']) ? date('c', strtotime((string)$j['lastmod'])) : null;
            echo "  <url><loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>";
            if ($lastmod) {
                echo "<lastmod>" . htmlspecialchars($lastmod, ENT_XML1) . "</lastmod>";
            }
            echo "</url>\n";
        }

        echo "</urlset>";
        exit;

    case '':
        (new HomeController())->index();
        break;

    case 'login':
        // compatibilidad: antes LoginController, ahora AuthController::login
        (new AuthController())->login();
        break;

    case 'forgot-password':
    case 'olvido-password':
        (new AuthController())->forgotPassword();
        break;

    case 'reset-password':
        (new AuthController())->resetPassword();
        break;

    case 'registro':
        (new AuthController())->registro();
        break;
    case 'mi-cuenta':
        (new AuthController())->miCuenta();
        break;
    case 'gestion-usuarios':
        (new AuthController())->gestionUsuarios();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;

    case 'empleos':
        (new EmpleoController())->index();
        break;

    case 'buscar':
        // ruta: /?url=buscar&q=texto
        require_once 'app/models/Empleo.php';
        $empleoModel = new Empleo($conexion);
        $q = $_GET['q'] ?? null;
        $categoria = $_GET['categoria'] ?? null;
        $resultados = $empleoModel->buscar($q, $categoria, 50);
        require_once 'app/views/empleos.php';
        break;

    case (preg_match('#^empleo/.+#', $url) ? $url : ''):
        // ruta amigable: /empleo/slug
        $parts = explode('/', $url);
        if (isset($parts[0]) && $parts[0] === 'empleo' && isset($parts[1])) {
            require_once 'app/models/Empleo.php';
            $empleoModel = new Empleo($conexion);
            $slug = $parts[1];
            $empleo = $empleoModel->obtenerPorSlug($slug);
            if ($empleo) {
                require_once 'app/views/empleo-detalle.php';
            } else {
                echo 'Empleo no encontrado';
            }
            break;
        }

    case 'publicar-empleo':
        (new EmpleoController())->publicar();
        break;

    case 'subcategorias':
        (new EmpleoController())->subcategorias();
        break;

    case 'provincias':
        (new EmpleoController())->provincias();
        break;

    case 'distritos':
        (new EmpleoController())->distritos();
        break;
    
    case 'publicar-empleo-detalles':
        (new EmpleoController())->detalles();
        break;

    case 'publicar-avance':
        (new EmpleoController())->avance();
        break;

    case 'publicar-revisar':
        (new EmpleoController())->revisar();
        break;

    case 'publicar-gracias':
        (new EmpleoController())->gracias();
        break;

    case 'publicar-empleo-guardar':
        (new EmpleoController())->guardar();
        break;

    case 'mis-anuncios':
        (new EmpleoController())->misAnuncios();
        break;

    case 'gestion-lugares':
        (new EmpleoController())->gestionLugares();
        break;

    case 'admin-publicidad-guardar':
        (new HomeController())->guardarPublicidad();
        break;

    case 'admin-publicidad-actualizar':
        (new HomeController())->actualizarPublicidad();
        break;

    case 'admin-publicidad-eliminar':
        (new HomeController())->eliminarPublicidad();
        break;

    case 'admin-publicidad':
        (new HomeController())->adminPublicidad();
        break;

    case 'publicidades':
        (new HomeController())->publicidades();
        break;

    case 'admin-categorias':
        (new CategoriaController())->gestion();
        break;

    case 'admin-categorias-crear':
        (new CategoriaController())->crear();
        break;

    case 'admin-categorias-actualizar':
        (new CategoriaController())->actualizar();
        break;

    case 'admin-categorias-eliminar':
        (new CategoriaController())->eliminar();
        break;

    case 'editar-empleo':
        (new EmpleoController())->editar();
        break;

    case 'eliminar-empleo':
        (new EmpleoController())->eliminar();
        break;

    case 'aprobar-destacado':
        (new EmpleoController())->aprobarDestacado();
        break;

    default:
        echo 'Página no encontrada';
        break;
}