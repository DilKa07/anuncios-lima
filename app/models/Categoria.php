<?php

class Categoria
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodas() {
        $sql = "
            SELECT *
            FROM categorias
            WHERE estado = 'activo'
            ORDER BY nombre ASC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPrincipales() {
        $sql = "
            SELECT *
            FROM categorias
            WHERE parent_id IS NULL
            AND estado = 'activo'
            ORDER BY nombre ASC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSubcategorias($parent_id) {
        $sql = "
            SELECT *
            FROM categorias
            WHERE parent_id = ?
            AND estado = 'activo'
            ORDER BY nombre ASC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$parent_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerParaGestion()
    {
        $sql = "
            SELECT c.id, c.nombre, c.slug, c.parent_id, c.estado,
                   p.nombre AS parent_nombre
            FROM categorias c
            LEFT JOIN categorias p ON p.id = c.parent_id
            WHERE c.estado = 'activo'
            ORDER BY (c.parent_id IS NOT NULL), c.parent_id, c.nombre ASC
        ";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeSlug($slug, $excludeId = null)
    {
        if ($excludeId) {
            $sql = "SELECT id FROM categorias WHERE slug = ? AND id <> ? LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$slug, $excludeId]);
        } else {
            $sql = "SELECT id FROM categorias WHERE slug = ? LIMIT 1";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([$slug]);
        }
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($nombre, $slug, $parentId = null)
    {
        $sql = "INSERT INTO categorias (nombre, slug, parent_id, estado, fecha_creacion)
                VALUES (:nombre, :slug, :parent_id, 'activo', NOW())";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':slug' => $slug,
            ':parent_id' => $parentId,
        ]);
    }

    public function actualizar($id, $nombre, $slug, $parentId = null)
    {
        $sql = "UPDATE categorias
                SET nombre = :nombre,
                    slug = :slug,
                    parent_id = :parent_id
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':slug' => $slug,
            ':parent_id' => $parentId,
            ':id' => $id,
        ]);
    }

    public function inactivar($id)
    {
        $sql = "UPDATE categorias SET estado = 'inactivo' WHERE id = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$id]);
    }

}