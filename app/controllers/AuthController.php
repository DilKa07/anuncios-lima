<?php
require_once 'app/helpers/notifications.php';

require_once 'app/models/Usuario.php';
require_once 'app/helpers/functions.php';
require_once 'app/helpers/mail.php';

class AuthController
{
    public function registro()
    {
        global $conexion;
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // verificar CSRF
                require_once 'app/helpers/functions.php';
                verify_csrf();
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($email) || empty($password)) {
                $errors[] = 'Todos los campos son obligatorios.';
            } else {
                $usuarioModel = new Usuario($conexion);
                if ($usuarioModel->obtenerPorUsername($username)) {
                    $errors[] = 'El nombre de usuario ya está registrado.';
                } elseif ($usuarioModel->obtenerPorEmail($email)) {
                    $errors[] = 'El email ya está registrado.';
                } else {
                    $created = false;
                    try {
                        $created = $usuarioModel->crear($username, $email, $password);
                    } catch (PDOException $e) {
                        if ((string)$e->getCode() === '23000') {
                            $errors[] = 'El usuario o email ya existe. Prueba con otros datos.';
                        } else {
                            throw $e;
                        }
                    }

                    if (!empty($errors)) {
                        require_once 'app/views/registro.php';
                        return;
                    }

                    $userMailOk = notifications_send(
                        $email,
                        '[Anuncios Lima] Bienvenido a la plataforma',
                        'Registro exitoso',
                        'Tu cuenta fue creada correctamente. Ya puedes publicar y gestionar anuncios.',
                        [
                            'Usuario' => $username,
                            'Email' => $email,
                        ]
                    );
                    $adminMailOk = notifications_send(
                        notifications_admin_email(),
                        '[Anuncios Lima] Nuevo usuario registrado',
                        'Alta de usuario',
                        'Se registró un nuevo usuario en la plataforma.',
                        [
                            'Usuario' => $username,
                            'Email' => $email,
                        ]
                    );

                    if ($created) {
                        $_SESSION['flash_success'] = 'Cuenta creada correctamente. Ya puedes iniciar sesion.';
                        if (!$userMailOk || !$adminMailOk) {
                            $_SESSION['flash_warning'] = 'La cuenta se creo, pero el correo automatico no pudo enviarse. Revisa SMTP en Brevo.';
                        }
                    }

                    header('Location: /login');
                    exit;
                }
            }
        }

        require_once 'app/views/registro.php';
    }

    public function login()
    {
        global $conexion;
        $errors = [];
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashWarning = $_SESSION['flash_warning'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_warning']);
        $usuarioModel = new Usuario($conexion);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // verificar CSRF
                require_once 'app/helpers/functions.php';
                verify_csrf();
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $ip = $this->getClientIp();
            if ($usuarioModel->ipEstaBloqueada($ip)) {
                $errors[] = 'Tu acceso fue restringido por seguridad. Contacta al administrador.';
            } else {
                $user = $usuarioModel->verificarCredenciales($email, $password);
                if ($user) {
                    if ((int)($user['cuenta_bloqueada'] ?? 0) === 1) {
                        $errors[] = 'Tu cuenta se encuentra bloqueada. Contacta al administrador.';
                    } else {
                        $usuarioModel->registrarLoginExitoso((int)$user['id'], $ip);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['username'];
                        $_SESSION['user_role'] = $user['rol'] ?? 'usuario';
                        header('Location: /');
                        exit;
                    }
                } else {
                    $errors[] = 'Credenciales inválidas.';
                }
            }
        }

        require_once 'app/views/login.php';
    }

    public function logout()
    {
        session_destroy();
        header('Location: /');
        exit;
    }

    public function miCuenta()
    {
        global $conexion;

        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $usuarioModel = new Usuario($conexion);
        $errors = [];
        $success = null;
        $isAdmin = is_admin_user($conexion);
        $usuario = $usuarioModel->obtenerPorId($_SESSION['user_id']);

        if (!$usuario) {
            $_SESSION['flash_error'] = 'No se pudo cargar tu cuenta.';
            header('Location: /');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $action = $_POST['action'] ?? 'actualizar_cuenta';

            if ($action === 'crear_admin') {
                if (!$isAdmin) {
                    $errors[] = 'No tienes permisos para crear cuentas administradoras.';
                } else {
                    $newUsername = trim($_POST['admin_username'] ?? '');
                    $newEmail = trim($_POST['admin_email'] ?? '');
                    $newPassword = $_POST['admin_password'] ?? '';

                    if ($newUsername === '' || $newEmail === '' || $newPassword === '') {
                        $errors[] = 'Todos los campos para crear administrador son obligatorios.';
                    }

                    if ($newEmail !== '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = 'El email del nuevo administrador no es valido.';
                    }

                    if (strlen($newPassword) > 0 && strlen($newPassword) < 6) {
                        $errors[] = 'La contrasena del nuevo administrador debe tener al menos 6 caracteres.';
                    }

                    if ($newUsername !== '' && $usuarioModel->obtenerPorUsername($newUsername)) {
                        $errors[] = 'Ya existe una cuenta con ese nombre de usuario.';
                    }

                    if ($usuarioModel->obtenerPorEmail($newEmail)) {
                        $errors[] = 'Ya existe una cuenta con ese email.';
                    }

                    if (empty($errors)) {
                        $okAdmin = false;
                        try {
                            $okAdmin = $usuarioModel->crearAdmin(
                                mb_substr($newUsername, 0, 50),
                                mb_substr($newEmail, 0, 150),
                                $newPassword
                            );
                        } catch (PDOException $e) {
                            if ((string)$e->getCode() === '23000') {
                                $errors[] = 'El usuario o email del nuevo administrador ya existe.';
                            } else {
                                throw $e;
                            }
                        }

                        if (!empty($errors)) {
                            require_once 'app/views/mi-cuenta.php';
                            return;
                        }

                        if ($okAdmin) {
                            notifications_send(
                                $newEmail,
                                '[Anuncios Lima] Cuenta administradora creada',
                                'Cuenta administradora habilitada',
                                'Tu cuenta fue creada con rol administrador.',
                                [
                                    'Usuario' => $newUsername,
                                    'Email' => $newEmail,
                                ]
                            );
                            notifications_send(
                                notifications_admin_email(),
                                '[Anuncios Lima] Nuevo administrador creado',
                                'Creación de administrador',
                                'Se creó una nueva cuenta administradora en la plataforma.',
                                [
                                    'Usuario' => $newUsername,
                                    'Email' => $newEmail,
                                ]
                            );
                            $success = 'Cuenta administradora creada correctamente.';
                        } else {
                            $errors[] = 'No se pudo crear la cuenta administradora.';
                        }
                    }
                }

                require_once 'app/views/mi-cuenta.php';
                return;
            }

            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmNewPassword = $_POST['confirm_new_password'] ?? '';

            if ($username === '' || $email === '') {
                $errors[] = 'Nombre de usuario y email son obligatorios.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'El email no es valido.';
            }

            if ($usuarioModel->existeEmailEnOtroUsuario($email, (int)$usuario['id'])) {
                $errors[] = 'Ese email ya esta en uso por otra cuenta.';
            }

            if ($usuarioModel->existeUsernameEnOtroUsuario($username, (int)$usuario['id'])) {
                $errors[] = 'Ese nombre de usuario ya esta en uso por otra cuenta.';
            }

            if ($currentPassword === '') {
                $errors[] = 'Debes ingresar tu contrasena actual para confirmar cambios.';
            }

            $storedPassword = (string)($usuario['password'] ?? '');
            $validCurrentPassword = false;
            if ($currentPassword !== '') {
                $validCurrentPassword = password_verify($currentPassword, $storedPassword)
                    || hash_equals($storedPassword, $currentPassword);
                if (!$validCurrentPassword) {
                    $errors[] = 'La contrasena actual no coincide.';
                }
            }

            if ($newPassword !== '') {
                if (strlen($newPassword) < 6) {
                    $errors[] = 'La nueva contrasena debe tener al menos 6 caracteres.';
                }
                if (!hash_equals($newPassword, $confirmNewPassword)) {
                    $errors[] = 'La confirmacion de la nueva contrasena no coincide.';
                }
            }

            if (empty($errors) && $validCurrentPassword) {
                $passwordToSave = $newPassword !== '' ? $newPassword : $currentPassword;
                    $passwordHash = password_hash($passwordToSave, PASSWORD_DEFAULT);
                $ok = $usuarioModel->actualizarCuenta(
                    (int)$usuario['id'],
                    mb_substr($username, 0, 120),
                    mb_substr($email, 0, 150),
                    mb_substr($telefono, 0, 20),
                    $passwordHash
                );
                    if ($ok && $newPassword !== '') {
                        notify_password_changed($conexion, (int)$usuario['id']);
                    }

                if ($ok) {
                    $_SESSION['user_name'] = mb_substr($username, 0, 120);
                    $success = 'Tus datos fueron actualizados correctamente.';
                    $usuario = $usuarioModel->obtenerPorId($_SESSION['user_id']);
                } else {
                    $errors[] = 'No se pudieron guardar los cambios.';
                }
            }
        }

        require_once 'app/views/mi-cuenta.php';
    }

    public function forgotPassword()
    {
        global $conexion;
        $errors = [];
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $email = trim((string)($_POST['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Ingresa un email valido.';
            } else {
                $usuarioModel = new Usuario($conexion);
                $user = $usuarioModel->obtenerPorEmail($email);

                if ($user) {
                    $rawToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $rawToken);
                    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
                    $usuarioModel->guardarResetToken((int)$user['id'], $tokenHash, $expiresAt);

                    $appUrl = $this->buildAppUrl();
                    $resetLink = $appUrl . '/reset-password?token=' . urlencode($rawToken);

                    $html = '<p>Hola ' . htmlspecialchars((string)($user['username'] ?? '')) . ',</p>'
                        . '<p>Recibimos una solicitud para restablecer tu contrasena.</p>'
                        . '<p><a href="' . htmlspecialchars($resetLink) . '">Haz clic aqui para crear una nueva contrasena</a></p>'
                        . '<p>Este enlace vence en 60 minutos.</p>';

                    mail_send((string)$user['email'], '[Anuncios Lima] Recuperar contrasena', $html);
                }

                // Mensaje neutro para no filtrar usuarios existentes.
                $success = 'Si el email existe, te enviamos un enlace para recuperar tu contrasena.';
            }
        }

        require_once 'app/views/forgot-password.php';
    }

    public function resetPassword()
    {
        global $conexion;
        $errors = [];
        $success = null;

        $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
        $usuarioModel = new Usuario($conexion);
        $user = $token !== '' ? $usuarioModel->obtenerPorResetTokenValido($token) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $password = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['password_confirm'] ?? '');

            if (!$user) {
                $errors[] = 'El enlace de recuperacion no es valido o ya vencio.';
            }
            if (strlen($password) < 6) {
                $errors[] = 'La nueva contrasena debe tener al menos 6 caracteres.';
            }
            if (!hash_equals($password, $confirm)) {
                $errors[] = 'La confirmacion de contrasena no coincide.';
            }

            if (empty($errors) && $user) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $ok = $usuarioModel->actualizarPasswordPorReset((int)$user['id'], $hash);
                } catch (Throwable $e) {
                    $ok = false;
                    @file_put_contents(
                        __DIR__ . '/../../storage/logs/app.log',
                        '[' . date('Y-m-d H:i:s') . '] AuthController::resetPassword error: ' . $e->getMessage() . PHP_EOL,
                        FILE_APPEND
                    );
                }
                if ($ok) {
                    $_SESSION['flash_success'] = 'Contrasena actualizada correctamente. Ahora inicia sesion.';
                    header('Location: /login');
                    exit;
                }
                $errors[] = 'No se pudo actualizar la contrasena.';
            }
        }

        require_once 'app/views/reset-password.php';
    }

    public function gestionUsuarios()
    {
        global $conexion;

        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        if (!is_admin_user($conexion)) {
            $_SESSION['flash_error'] = 'Solo administradores pueden acceder a esta seccion.';
            header('Location: /');
            exit;
        }

        $usuarioModel = new Usuario($conexion);
        $q = trim((string)($_GET['q'] ?? ''));
        $ipsOcultas = [
            '132.191.1.96',
        ];
        $ipEstaOculta = static function ($ip) use ($ipsOcultas) {
            $ip = trim((string)$ip);
            if ($ip === '') {
                return false;
            }
            return in_array($ip, $ipsOcultas, true);
        };

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $action = (string)($_POST['action'] ?? '');
            $targetUserId = (int)($_POST['user_id'] ?? 0);

            if ($targetUserId <= 0) {
                $_SESSION['flash_error'] = 'Usuario invalido para gestionar.';
                header('Location: /gestion-usuarios' . ($q !== '' ? ('?q=' . urlencode($q)) : ''));
                exit;
            }

            if ($action === 'bloquear_cuenta') {
                if ($targetUserId === (int)($_SESSION['user_id'] ?? 0)) {
                    $_SESSION['flash_error'] = 'No puedes bloquear tu propia cuenta.';
                } else {
                    $ok = $usuarioModel->bloquearCuenta($targetUserId);
                    $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                        ? 'Cuenta bloqueada correctamente.'
                        : 'No se pudo bloquear la cuenta.';
                }
            } elseif ($action === 'desbloquear_cuenta') {
                $ok = $usuarioModel->desbloquearCuenta($targetUserId);
                $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                    ? 'Cuenta desbloqueada correctamente.'
                    : 'No se pudo desbloquear la cuenta.';
            } elseif ($action === 'bloquear_ip') {
                $target = $usuarioModel->obtenerPorId($targetUserId);
                $targetIp = trim((string)($target['last_login_ip'] ?? ''));
                if ($targetIp === '') {
                    $_SESSION['flash_error'] = 'El usuario no tiene IP registrada de ultimo acceso.';
                } elseif ($ipEstaOculta($targetIp)) {
                    $_SESSION['flash_error'] = 'Esa IP esta en lista protegida y no se puede bloquear desde este panel.';
                } else {
                    $ok = $usuarioModel->bloquearIpDeUsuario($targetUserId, $targetIp, 'Bloqueo manual desde gestion de usuarios');
                    $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                        ? ('IP bloqueada correctamente: ' . $targetIp)
                        : 'No se pudo bloquear la IP del usuario.';
                }
            } elseif ($action === 'eliminar_cuenta') {
                if ($targetUserId === (int)($_SESSION['user_id'] ?? 0)) {
                    $_SESSION['flash_error'] = 'No puedes eliminar tu propia cuenta desde este panel.';
                } else {
                    $target = $usuarioModel->obtenerPorId($targetUserId);
                    if (!$target) {
                        $_SESSION['flash_error'] = 'La cuenta seleccionada no existe.';
                    } elseif (($target['rol'] ?? '') === 'admin' && !$usuarioModel->existeOtroAdministrador($targetUserId)) {
                        $_SESSION['flash_error'] = 'No puedes eliminar el ultimo administrador activo.';
                    } else {
                        $ok = $usuarioModel->eliminarCuentaCompleta($targetUserId);
                        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                            ? 'Cuenta eliminada de forma permanente junto a sus anuncios e imagenes.'
                            : 'No se pudo eliminar la cuenta seleccionada.';
                    }
                }
            }

            header('Location: /gestion-usuarios' . ($q !== '' ? ('?q=' . urlencode($q)) : ''));
            exit;
        }

        $rows = $usuarioModel->obtenerUsuariosGestion($q);
        $admins = [];
        $usuarios = [];
        foreach ($rows as $row) {
            $row['last_login_ip'] = trim((string)($row['last_login_ip'] ?? ''));
            if ($ipEstaOculta($row['last_login_ip'])) {
                $row['last_login_ip'] = '';
            }

            if (($row['rol'] ?? '') === 'admin') {
                $admins[] = $row;
            } else {
                $usuarios[] = $row;
            }
        }

        $adminsCount = count($admins);
        $usuariosCount = count($usuarios);

        require_once 'app/views/gestion-usuarios.php';
    }

    private function getClientIp()
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        foreach ($candidates as $item) {
            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }
            if (strpos($item, ',') !== false) {
                $parts = explode(',', $item);
                $item = trim((string)($parts[0] ?? ''));
            }
            if (filter_var($item, FILTER_VALIDATE_IP)) {
                return $item;
            }
        }

        return '';
    }

    private function buildAppUrl()
    {
        $cfg = require __DIR__ . '/../../config/app.php';
        $appUrl = rtrim((string)($cfg['app_url'] ?? ''), '/');
        $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        if ($appUrl === '' || strpos(strtolower($appUrl), 'localhost') !== false || strpos($appUrl, '127.0.0.1') !== false) {
            $appUrl = $scheme . '://' . $host;
        }

        return $appUrl;
    }
}
