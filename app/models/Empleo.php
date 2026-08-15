<?php

class Empleo
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerDestacados($limit = 7)
    {
        $sql = "SELECT * FROM empleos WHERE (estado = 'publicado' OR estado = '' OR estado IS NULL) AND destacado = 1 ORDER BY fecha_publicacion DESC LIMIT ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRecientes($limit = 12)
    {
        $sql = "SELECT * FROM empleos WHERE estado = 'publicado' OR estado = '' OR estado IS NULL ORDER BY fecha_publicacion DESC LIMIT ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerInicioPriorizado($limit = 6)
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT *
                FROM empleos
                WHERE (estado = 'publicado' OR estado = '' OR estado IS NULL)
                ORDER BY CASE WHEN destacado = 1 THEN 0 ELSE 1 END ASC,
                         fecha_publicacion DESC,
                         id DESC
            LIMIT {$limit}";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar($q = null, $categoria_id = null, $limit = 50)
    {
        $params = [];
        $where = "WHERE (estado = 'publicado' OR estado = '' OR estado IS NULL)";

        if (!empty($q)) {
            $where .= " AND (titulo LIKE :q OR descripcion LIKE :q OR tags LIKE :q)";
            $params[':q'] = "%" . $q . "%";
        }

        if (!empty($categoria_id)) {
            $where .= " AND (categoria_id = :cat OR subcategoria_id = :cat)";
            $params[':cat'] = $categoria_id;
        }

        $sql = "SELECT * FROM empleos " . $where . " ORDER BY fecha_publicacion DESC LIMIT :limit";
        $stmt = $this->conexion->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorSlug($slug)
    {
        $sql = "SELECT * FROM empleos WHERE slug = ? AND (estado = 'publicado' OR estado = '' OR estado IS NULL) LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}
