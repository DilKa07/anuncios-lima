<?php

class Usuario
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
        $this->ensureSecuritySchema();
    }

    private function ensureSecuritySchema()
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $stmt = $this->conexion->prepare("SHOW COLUMNS FROM usuarios LIKE 'cuenta_bloqueada'");
            $stmt->execute();
            if (!(bool)$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->conexion->exec("ALTER TABLE usuarios ADD COLUMN cuenta_bloqueada TINYINT(1) NOT NULL DEFAULT 0 AFTER estado");
            }

            $stmt = $this->conexion->prepare("SHOW COLUMNS FROM usuarios LIKE 'last_login_ip'");
            $stmt->execute();
            if (!(bool)$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->conexion->exec("ALTER TABLE usuarios ADD COLUMN last_login_ip VARCHAR(64) NULL AFTER cuenta_bloqueada");
            }

            $this->conexion->exec("CREATE TABLE IF NOT EXISTS usuarios_ip_bloqueadas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                ip VARCHAR(64) NOT NULL,
                motivo VARCHAR(255) DEFAULT NULL,
                estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
                fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_ip_activa (ip, estado),
                INDEX idx_usuario_estado (usuario_id, estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            // Evitar romper la app en hostings con permisos limitados de DDL.
        }
    }

    public function crear($username, $email, $password)
    {
        return $this->crearConRol($username, $email, $password, 'usuario');
    }

    public function crearAdmin($username, $email, $password)
    {
        return $this->crearConRol($username, $email, $password, 'admin');
    }

    private function crearConRol($username, $email, $password, $rol)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (username,email,password,rol,estado) VALUES (:username,:email,:password,:rol,'activo')";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hash,
            ':rol' => $rol,
        ]);
    }

    public function obtenerPorEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPorUsername($username)
    {
        $sql = "SELECT * FROM usuarios WHERE username = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verificarCredenciales($email, $password)
    {
        $user = $this->obtenerPorEmail($email);
        if (!$user) return false;
        if (password_verify($password, $user['password'])) return $user;
        return false;
    }

    public function registrarLoginExitoso($userId, $ip)
    {
        $params = [
            ':id' => (int)$userId,
            ':ip' => mb_substr((string)$ip, 0, 64),
        ];

        try {
            $sql = "UPDATE usuarios
                    SET last_login_ip = :ip,
                        fecha_actualizacion = NOW()
                    WHERE id = :id
                    LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($params);
        } catch (Throwable $e) {
            $sql = "UPDATE usuarios
                    SET last_login_ip = :ip
                    WHERE id = :id
                    LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($params);
        }
    }

    public function bloquearCuenta($userId)
    {
        $sql = "UPDATE usuarios SET cuenta_bloqueada = 1 WHERE id = :id LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([':id' => (int)$userId]);
    }

    public function desbloquearCuenta($userId)
    {
        $sql = "UPDATE usuarios SET cuenta_bloqueada = 0 WHERE id = :id LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([':id' => (int)$userId]);
    }

    public function ipEstaBloqueada($ip)
    {
        $ip = trim((string)$ip);
        if ($ip === '') {
            return false;
        }
        $sql = "SELECT id FROM usuarios_ip_bloqueadas WHERE ip = :ip AND estado = 'activo' LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':ip' => $ip]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function bloquearIpDeUsuario($userId, $ip, $motivo = null)
    {
        $ip = trim((string)$ip);
        if ($ip === '') {
            return false;
        }

        $sql = "INSERT INTO usuarios_ip_bloqueadas (usuario_id, ip, motivo, estado)
                VALUES (:usuario_id, :ip, :motivo, 'activo')
                ON DUPLICATE KEY UPDATE
                    usuario_id = VALUES(usuario_id),
                    motivo = VALUES(motivo),
                    estado = 'activo'";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':usuario_id' => (int)$userId,
            ':ip' => $ip,
            ':motivo' => $motivo !== null ? mb_substr((string)$motivo, 0, 255) : null,
        ]);
    }

    public function obtenerUsuariosGestion($search = '')
    {
        $search = trim((string)$search);
        $params = [];

        $sql = "SELECT
                    u.id,
                    u.username,
                    u.email,
                    u.rol,
                    u.estado,
                    COALESCE(u.cuenta_bloqueada, 0) AS cuenta_bloqueada,
                    u.last_login_ip,
                    COUNT(e.id) AS publicaciones_count,
                    MAX(e.titulo) AS ultimo_anuncio_titulo
                FROM usuarios u
                LEFT JOIN empleos e
                    ON e.usuario_id = u.id
                   AND e.estado <> 'eliminado'";

        if ($search !== '') {
            $sql .= " WHERE (
                        u.username LIKE :q
                        OR u.email LIKE :q
                        OR EXISTS (
                            SELECT 1
                            FROM empleos ex
                            WHERE ex.usuario_id = u.id
                              AND ex.estado <> 'eliminado'
                              AND ex.titulo LIKE :q2
                        )
                    )";
            $params[':q'] = '%' . $search . '%';
            $params[':q2'] = '%' . $search . '%';
        }

        $sql .= " GROUP BY u.id
                  ORDER BY
                    CASE WHEN u.rol = 'admin' THEN 0 ELSE 1 END,
                    u.username ASC";

        $stmt = $this->conexion->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM usuarios WHERE id = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function existeOtroAdministrador($excludeUserId)
    {
        $sql = "SELECT id FROM usuarios WHERE rol = 'admin' AND id <> :id LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':id' => (int)$excludeUserId]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function eliminarCuentaCompleta($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }

        $startedTx = false;
        $imagenes = [];

        try {
            if (!$this->conexion->inTransaction()) {
                $this->conexion->beginTransaction();
                $startedTx = true;
            }

            $existsStmt = $this->conexion->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
            $existsStmt->execute([':id' => $userId]);
            if (!(bool)$existsStmt->fetch(PDO::FETCH_ASSOC)) {
                if ($startedTx && $this->conexion->inTransaction()) {
                    $this->conexion->rollBack();
                }
                return false;
            }

            $imgStmt = $this->conexion->prepare("SELECT imagen FROM empleos WHERE usuario_id = :usuario_id");
            $imgStmt->execute([':usuario_id' => $userId]);
            foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $img = trim((string)($row['imagen'] ?? ''));
                if ($img !== '') {
                    $imagenes[] = $img;
                }
            }

            $delEmpleosStmt = $this->conexion->prepare("DELETE FROM empleos WHERE usuario_id = :usuario_id");
            $delEmpleosStmt->execute([':usuario_id' => $userId]);

            $delIpsStmt = $this->conexion->prepare("DELETE FROM usuarios_ip_bloqueadas WHERE usuario_id = :usuario_id");
            $delIpsStmt->execute([':usuario_id' => $userId]);

            $delUserStmt = $this->conexion->prepare("DELETE FROM usuarios WHERE id = :id LIMIT 1");
            $delUserStmt->execute([':id' => $userId]);

            if ($delUserStmt->rowCount() < 1) {
                if ($startedTx && $this->conexion->inTransaction()) {
                    $this->conexion->rollBack();
                }
                return false;
            }

            if ($startedTx && $this->conexion->inTransaction()) {
                $this->conexion->commit();
            }

            $this->eliminarArchivosDeEmpleos($imagenes);
            return true;
        } catch (Throwable $e) {
            if ($startedTx && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return false;
        }
    }

    private function eliminarArchivosDeEmpleos(array $imagenes)
    {
        $baseDir = realpath(__DIR__ . '/../../uploads/empleos');
        if ($baseDir === false) {
            return;
        }

        $imagenes = array_unique($imagenes);
        foreach ($imagenes as $imagen) {
            $base = basename((string)$imagen);
            if ($base === '' || $base === '.' || $base === '..') {
                continue;
            }

            $originalPath = $baseDir . DIRECTORY_SEPARATOR . $base;
            $thumbPath = $baseDir . DIRECTORY_SEPARATOR . 'thumb_' . $base;

            if (is_file($originalPath)) {
                @unlink($originalPath);
            }
            if (is_file($thumbPath)) {
                @unlink($thumbPath);
            }
        }
    }

    public function existeEmailEnOtroUsuario($email, $userId)
    {
        $sql = "SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$email, $userId]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function existeUsernameEnOtroUsuario($username, $userId)
    {
        $sql = "SELECT id FROM usuarios WHERE username = ? AND id <> ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$username, $userId]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarCuenta($id, $username, $email, $telefono, $passwordHash)
    {
        $params = [
            ':username' => $username,
            ':email' => $email,
            ':telefono' => $telefono,
            ':password' => $passwordHash,
            ':id' => (int)$id,
        ];

        $queries = [
            "UPDATE usuarios
             SET username = :username,
                 email = :email,
                 telefono = :telefono,
                 password = :password,
                 fecha_actualizacion = NOW()
             WHERE id = :id
             LIMIT 1",
            "UPDATE usuarios
             SET username = :username,
                 email = :email,
                 telefono = :telefono,
                 password = :password
             WHERE id = :id
             LIMIT 1",
            "UPDATE usuarios
             SET username = :username,
                 email = :email,
                 password = :password,
                 fecha_actualizacion = NOW()
             WHERE id = :id
             LIMIT 1",
            "UPDATE usuarios
             SET username = :username,
                 email = :email,
                 password = :password
             WHERE id = :id
             LIMIT 1",
        ];

        foreach ($queries as $sql) {
            try {
                $stmt = $this->conexion->prepare($sql);
                return $stmt->execute($params);
            } catch (Throwable $e) {
                // Intentar con el siguiente fallback para esquemas legacy.
            }
        }

        return false;
    }

    private function ensureResetColumns()
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $stmt = $this->conexion->prepare("SHOW COLUMNS FROM usuarios LIKE 'reset_token'");
            $stmt->execute();
            $hasResetToken = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->conexion->prepare("SHOW COLUMNS FROM usuarios LIKE 'reset_expires_at'");
            $stmt->execute();
            $hasResetExpires = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

            if (!$hasResetToken) {
                $this->conexion->exec("ALTER TABLE usuarios ADD COLUMN reset_token VARCHAR(128) NULL AFTER password");
            }
            if (!$hasResetExpires) {
                $this->conexion->exec("ALTER TABLE usuarios ADD COLUMN reset_expires_at DATETIME NULL AFTER reset_token");
            }
        } catch (Throwable $e) {
            // Si el hosting restringe ALTER, no romper flujo de aplicación.
        }
    }

    public function guardarResetToken($userId, $tokenHash, $expiresAt)
    {
        $this->ensureResetColumns();
        $sql = "UPDATE usuarios
                SET reset_token = :token,
                    reset_expires_at = :expires
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':token' => $tokenHash,
            ':expires' => $expiresAt,
            ':id' => (int)$userId,
        ]);
    }

    public function obtenerPorResetTokenValido($rawToken)
    {
        $this->ensureResetColumns();
        $tokenHash = hash('sha256', (string)$rawToken);
        $sql = "SELECT *
                FROM usuarios
                WHERE reset_token = :token
                  AND reset_expires_at IS NOT NULL
                  AND reset_expires_at >= NOW()
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':token' => $tokenHash]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarPasswordPorReset($userId, $passwordHash)
    {
        $this->ensureResetColumns();
        $params = [
            ':password' => $passwordHash,
            ':id' => (int)$userId,
        ];

        try {
            $sql = "UPDATE usuarios
                    SET password = :password,
                        reset_token = NULL,
                        reset_expires_at = NULL,
                        fecha_actualizacion = NOW()
                    WHERE id = :id
                    LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($params);
        } catch (Throwable $e) {
            // Fallback para esquemas antiguos sin columna fecha_actualizacion.
            $sql = "UPDATE usuarios
                    SET password = :password,
                        reset_token = NULL,
                        reset_expires_at = NULL
                    WHERE id = :id
                    LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute($params);
        }
    }
}
