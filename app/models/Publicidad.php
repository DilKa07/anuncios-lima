<?php

class Publicidad
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS publicidades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(180) NOT NULL,
            descripcion TEXT NOT NULL,
            enlace VARCHAR(500) NOT NULL,
            imagen VARCHAR(255) DEFAULT NULL,
            estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
            creado_por INT DEFAULT NULL,
            fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_publicidades_estado_fecha (estado, fecha_creacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conexion->exec($sql);
    }

    public function obtenerActivas($limit = 20)
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT id, titulo, descripcion, enlace, imagen, fecha_creacion
                FROM publicidades
                WHERE estado = 'activo'
                ORDER BY fecha_creacion DESC, id DESC
                LIMIT {$limit}";

        $stmt = $this->conexion->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function crear($titulo, $descripcion, $enlace, $imagen = null, $creadoPor = null)
    {
        $sql = "INSERT INTO publicidades (titulo, descripcion, enlace, imagen, creado_por)
                VALUES (:titulo, :descripcion, :enlace, :imagen, :creado_por)";
        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':enlace' => $enlace,
            ':imagen' => $imagen,
            ':creado_por' => $creadoPor,
        ]);
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conexion->prepare("SELECT id, titulo, descripcion, enlace, imagen, estado, fecha_creacion FROM publicidades WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function actualizar($id, $titulo, $descripcion, $enlace, $imagen = null)
    {
        $params = [
            ':id' => (int)$id,
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':enlace' => $enlace,
        ];

        $setImagenSql = '';
        if ($imagen !== null) {
            $setImagenSql = ', imagen = :imagen';
            $params[':imagen'] = $imagen;
        }

        $sql = "UPDATE publicidades
                SET titulo = :titulo,
                    descripcion = :descripcion,
                    enlace = :enlace
                    {$setImagenSql}
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute($params);
    }

    public function desactivar($id)
    {
        $stmt = $this->conexion->prepare("UPDATE publicidades SET estado = 'inactivo' WHERE id = ? LIMIT 1");
        return $stmt->execute([(int)$id]);
    }
}
