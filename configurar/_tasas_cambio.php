<?php
class TasasCambio
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    private function obtenerDatos($tabla, $id)
    {
        $stmt = $this->conexion->prepare("SELECT * FROM $tabla WHERE bss_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            return [
                "status" => "success",
                "data" => $fila
            ];
        }

        return [
            "status" => "error",
            "message" => "Información no encontrada en $tabla."
        ];
    }

    public function obtenerCambio($id)
    {
        return json_encode($this->obtenerDatos('cambio', $id));
    }

    public function tasasMostradas($id)
    {
        $resultado = $this->obtenerDatos('cambios_mostrados', $id);

        if ($resultado['status'] === 'success' && isset($resultado['data']['tasas'])) {
            $resultado['data'] = json_decode($resultado['data']['tasas'], true); // suponiendo que está almacenado como JSON
        }

        return json_encode($resultado);
    }


    public function actualizarTasasMostradas($id, $listaTasas)
    {
        $tasasJson = json_encode($listaTasas);

        $stmt = $this->conexion->prepare("UPDATE cambios_mostrados SET tasas = ? WHERE bss_id = ?");
        $stmt->bind_param("si", $tasasJson, $id);

        if ($stmt->execute()) {
            return json_encode([
                "status" => "success",
                "message" => "Tasas actualizadas correctamente."
            ]);
        }

        return json_encode([
            "status" => "error",
            "message" => "Error al actualizar las tasas: " . $stmt->error
        ]);
    }
}
