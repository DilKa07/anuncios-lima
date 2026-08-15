<?php

require_once __DIR__ . '/mail.php';

if (!function_exists('notifications_app_config')) {
    function notifications_app_config()
    {
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg;
        }

        $cfg = [];
        try {
            $cfg = require __DIR__ . '/../../config/app.php';
        } catch (Throwable $e) {
            $cfg = [];
        }

        return is_array($cfg) ? $cfg : [];
    }
}

if (!function_exists('notifications_admin_email')) {
    function notifications_admin_email()
    {
        $cfg = notifications_app_config();
        $fromDefault = 'anuncioslimaclasificados@gmail.com';
        return $cfg['mail']['admin_email'] ?? $cfg['mail']['from_email'] ?? $fromDefault;
    }
}

if (!function_exists('notifications_user_data')) {
    function notifications_user_data($conexion, $usuarioId)
    {
        $stmt = $conexion->prepare('SELECT id, username, email FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    }
}

if (!function_exists('notifications_send')) {
    function notifications_send($to, $subject, $title, $message, $meta = [])
    {
        if (empty($to)) {
            return false;
        }

        $payload = [
            'title' => (string)$title,
            'message' => (string)$message,
            'meta' => is_array($meta) ? $meta : [],
            'site_name' => 'Anuncios Lima',
        ];

        try {
            return mail_send_template(
                $to,
                (string)$subject,
                __DIR__ . '/../views/emails/notificacion_sistema.php',
                $payload
            );
        } catch (Throwable $e) {
            error_log('[notifications_send] ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('notify_new_listing')) {
    function notify_new_listing($conexion, $usuarioId, $titulo, $destacado, $estado)
    {
        $user = notifications_user_data($conexion, $usuarioId);
        $adminEmail = notifications_admin_email();

        $tipoPublicacion = ((int)$destacado === 1) ? 'Destacado' : 'Estándar';
        $estadoTxt = (string)$estado;

        if (!empty($user['email'])) {
            notifications_send(
                $user['email'],
                '[Anuncios Lima] Publicación recibida: ' . $titulo,
                'Tu anuncio fue registrado',
                'Registramos tu anuncio correctamente. Te compartimos el estado actual de tu publicación.',
                [
                    'Título' => $titulo,
                    'Tipo de publicación' => $tipoPublicacion,
                    'Estado' => $estadoTxt,
                ]
            );
        }

        notifications_send(
            $adminEmail,
            '[Anuncios Lima] Nuevo anuncio creado: ' . $titulo,
            'Nuevo anuncio en la plataforma',
            'Se registró un nuevo anuncio y requiere seguimiento según su estado.',
            [
                'Título' => $titulo,
                'Usuario' => $user['username'] ?? ('ID ' . (int)$usuarioId),
                'Tipo de publicación' => $tipoPublicacion,
                'Estado' => $estadoTxt,
            ]
        );
    }
}

if (!function_exists('notify_featured_approved')) {
    function notify_featured_approved($conexion, $empleoId)
    {
        $stmt = $conexion->prepare('SELECT e.titulo, u.username, u.email FROM empleos e INNER JOIN usuarios u ON u.id = e.usuario_id WHERE e.id = ? LIMIT 1');
        $stmt->execute([(int)$empleoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }

        if (!empty($row['email'])) {
            notifications_send(
                $row['email'],
                '[Anuncios Lima] Tu anuncio destacado fue aprobado',
                'Aprobación de anuncio destacado',
                'Tu anuncio destacado ya fue aprobado por el administrador y ahora está visible para todos los usuarios.',
                [
                    'Título' => $row['titulo'] ?? '',
                    'Estado' => 'publicado',
                ]
            );
        }

        notifications_send(
            notifications_admin_email(),
            '[Anuncios Lima] Destacado aprobado: ' . ($row['titulo'] ?? ''),
            'Aprobación registrada',
            'Se completó la aprobación de una publicación destacada.',
            [
                'Título' => $row['titulo'] ?? '',
                'Usuario' => $row['username'] ?? '',
                'Estado' => 'publicado',
            ]
        );
    }
}

if (!function_exists('notify_password_changed')) {
    function notify_password_changed($conexion, $usuarioId)
    {
        $user = notifications_user_data($conexion, $usuarioId);
        if (empty($user['email'])) {
            return;
        }

        notifications_send(
            $user['email'],
            '[Anuncios Lima] Cambio de contraseña exitoso',
            'Tu contraseña fue actualizada',
            'Te confirmamos que el cambio de contraseña se realizó correctamente. Si no reconoces esta acción, contáctanos de inmediato.',
            [
                'Usuario' => $user['username'] ?? '',
                'Fecha' => date('d/m/Y H:i'),
            ]
        );

        notifications_send(
            notifications_admin_email(),
            '[Anuncios Lima] Usuario cambió contraseña',
            'Cambio de contraseña registrado',
            'Se registró un cambio de contraseña en la plataforma.',
            [
                'Usuario' => $user['username'] ?? ('ID ' . (int)$usuarioId),
                'Email' => $user['email'] ?? '',
                'Fecha' => date('d/m/Y H:i'),
            ]
        );
    }
}
