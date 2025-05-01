<?php

class Tablas
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerDatosTablas($tabla, $condiciones = [])
    {
        $sql = "SELECT * FROM `$tabla`";
        $tipos = "";
        $valores = [];

        if (!empty($condiciones)) {
            $campos = [];
            foreach ($condiciones as $columna => $valor) {
                $campos[] = "`$columna` = ?";
                $tipos .= is_int($valor) ? "i" : "s";
                $valores[] = $valor;
            }
            $sql .= " WHERE " . implode(" AND ", $campos);
        }

        $stmt = $this->conexion->prepare($sql);

        if (!empty($valores)) {
            $stmt->bind_param($tipos, ...$valores);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();
        $filas = $resultado->fetch_all(MYSQLI_ASSOC);

        if (!empty($filas)) {
            return [
                "status" => "success",
                "data" => $filas
            ];
        }

        return [
            "status" => "error",
            "message" => "No se encontraron registros en $tabla."
        ];
    }
}
