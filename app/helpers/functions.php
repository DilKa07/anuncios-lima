<?php

function dd($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die();
}

// CSRF helpers
function csrf_token()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return "<input type='hidden' name='csrf_token' value='{$t}'>";
}

function verify_csrf()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        die('CSRF token inválido');
    }
}

function is_admin_user($conexion = null)
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['user_id'])) {
        return false;
    }

    if (isset($_SESSION['user_role'])) {
        return $_SESSION['user_role'] === 'admin';
    }

    if (!$conexion) {
        return false;
    }

    try {
        $stmt = $conexion->prepare('SELECT rol FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $rol = $stmt->fetchColumn();
        $_SESSION['user_role'] = $rol ?: 'usuario';
        return $_SESSION['user_role'] === 'admin';
    } catch (Throwable $e) {
        return false;
    }
}