<?php
class DatabaseHandler
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;

        // Verificar si la conexión es exitosa
        if ($this->conexion->connect_error) {
            throw new Exception("Error de conexión a la base de datos: " . $this->conexion->connect_error);
        }
    }

    /**
     * Realizar una consulta SELECT a la base de datos.
     * 
     * @param array|string $columnas Columnas a seleccionar, o '*' para todas.
     * @param string $nombre_tabla Nombre de la tabla desde la que se seleccionan los datos.
     * @param string $condicion Condición en formato SQL con operadores (=, !=, <, >, <=, >=).
     * @param array $order_by Opcional. Array con la estructura [['campo' => 'nombre_campo', 'order' => 'ASC|DESC']].
     * @param array $join Opcional. Array con la estructura ['nombre_tabla' => 'condicion_join'] para realizar INNER JOIN.
     * @throws Exception Si hay un error al preparar o ejecutar la consulta.
     * @return string Resultado de la consulta en formato JSON, con los datos seleccionados.
     */
    public function select($columnas = ['*'], $nombre_tabla, $condicion = "", $order_by = [], $join = [])
    {
        $data = [];

        // Generar lista de columnas para SELECT
        $campos = is_array($columnas) ? implode(", ", $columnas) : "*";

        // Generar consulta base
        $query = "SELECT $campos FROM `$nombre_tabla`";

        // Agregar INNER JOIN si está presente
        if (!empty($join)) {
            foreach ($join as $tabla => $condicion_join) {
                $query .= " INNER JOIN $tabla ON $condicion_join";
            }
        }
        $valores = [];
        if (!empty($condicion)) {
            // Eliminar espacios alrededor de los operadores en $condicion
            $condicion = preg_replace('/\s*([=<>!]+)\s*/', '$1', $condicion);

            // Reemplazar valores después de operadores con '?' y eliminar comillas alrededor del valor
            $condicion = preg_replace_callback('/([=<>!]+)(["\'`]?)([^"\']+?)\2/', function ($matches) use (&$valores) {
                $valores[] = trim($matches[3]);  // Agrega el valor sin comillas al array
                return $matches[1] . ' ?';       // Retorna el operador seguido de '?'
            }, $condicion);

            $query .= " WHERE $condicion";
        }


        // Agregar orden si está presente
        if (!empty($order_by)) {
            $order_clauses = [];
            foreach ($order_by as $order) {
                if (isset($order['campo'], $order['order'])) {
                    $order_clauses[] = $order['campo'] . " " . strtoupper($order['order']);
                }
            }
            if (!empty($order_clauses)) {
                $query .= " ORDER BY " . implode(", ", $order_clauses);
            }
        }


        // Preparar consulta
        $stmt = mysqli_prepare($this->conexion, $query);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $this->conexion->error);
        }


        // Determinar tipos y vincular parámetros
        if (!empty($valores)) {
            $tipos = '';
            foreach ($valores as $valor) {
                // Obtener el tipo del valor utilizando getParamType
                $tipos .= $this->getParamType($valor);
            }
            $stmt->bind_param($tipos, ...$valores);
        }

        // Ejecutar
        if (!$stmt->execute()) {
            throw new Exception("Error  al ejecutar la consulta: " . $stmt->error);
        }

        // Obtener resultados
        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception("Error  al obtener los resultados: " . $stmt->error);
        }

        // Almacenar resultados
        while ($row = $result->fetch_assoc()) {
            array_push($data, $row);
        }

        $stmt->close();

        return json_encode(['success' => $data]);
    }


    /**
     * Devuelve el tipo de parámetro para registro según el tipo de valor.
     *
     * @param mixed $param Valor del parámetro.
     * @return string Tipo de parámetro
     */
    private function getParamType($param)
    {
        switch (gettype($param)) {
            case 'integer':
                return 'i';
            case 'double':
                return 'd';
            case 'string':
                return 's';
            default:
                return 'b';
        }
    }

    /**
     * Inserta un nuevo registro en una tabla de la base de datos, verificando previamente
     * la unicidad de los campos si es necesario.
     *
     * @param string $tabla Nombre de la tabla donde se insertará el registro.
     * @param array $campos_valores Array asociativo con los datos a insertar, en donde cada elemento es un array que contiene la información de un campo en el siguiente formato:
     *                              [
     *                                  0 => 'nombre_columna', // Nombre de la columna en la base de datos
     *                                  1 => 'valor',          // Valor a insertar
     *                                  2 => 'unicidad'        // (Opcional) Indica si el campo debe ser único. Si es true, se verificará la existencia del valor.
     *                              ]
     * @return array Resultado de la operación, incluyendo el ID insertado si se realizó con éxito.
     *
     * @throws Exception Si no se especifican campos y valores, si alguno de los campos ya existe cuando debe ser único, o si ocurre un error en la consulta.
     *
     * Ejemplo de Uso:
     * $campos_valores = [
     *     ['nombre_columna1', 'valor1', true],   // campo único
     *     ['nombre_columna2', 'valor2']          // sin verificación de unicidad
     * ];
     */
    public function insert($tabla, $campos_valores)
    {
        if (empty($campos_valores) || !is_array($campos_valores)) {
            throw new Exception("Se requiere un array de campos y valores para la inserción.");
        }

        $columnas = [];
        $placeholders = [];
        $param_types = "";
        $params = [];
        $condicion_unicidad = [];

        foreach ($campos_valores as $campo_info) {
            if (!is_array($campo_info) || count($campo_info) < 2) {
                throw new Exception("Cada campo debe ser un array con al menos la columna y el valor.");
            }

            $columna = $campo_info[0];
            $valor = $campo_info[1];
            $tipo = $this->getParamType($valor);
            $unicidad = isset($campo_info[2]) ? $campo_info[2] : false;

            // Agregar el campo a la condición de unicidad si es necesario
            if ($unicidad === true) {
                $condicion_unicidad[] = "$columna = '$valor'";
            }

            // Construcción de los valores para la inserción
            $columnas[] = $columna;
            $placeholders[] = "?";
            $param_types .= $tipo;
            $params[] = $valor;
        }

        // Verificar unicidad solo si hay campos marcados como únicos
        if (!empty($condicion_unicidad)) {
            $condicion_unica = implode(" AND ", $condicion_unicidad);

            $coincidencias = $this->comprobar_existencia([
                ['tabla' => $tabla, 'condicion' => "$condicion_unica"]
            ]);

            if ($coincidencias > 0) {
                return ['error' => 'Uno o más valores de campos únicos ya existen en la base de datos.'];
            }
        }

        // Generar la consulta de inserción
        $columnas_string = implode(", ", $columnas);
        $placeholders_string = implode(", ", $placeholders);
        $query = "INSERT INTO `$tabla` ($columnas_string) VALUES ($placeholders_string)";

        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta INSERT: " . $this->conexion->error);
        }

        $stmt->bind_param($param_types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Error al ejecutar la consulta INSERT: " . $stmt->error);
        }


        $insert_id = $stmt->insert_id;
        $stmt->close();

        return [
            'success' => true,
            'insert_id' => $insert_id
        ];
    }

    /**
     * Eliminar registros de una tabla en la base de datos.
     * 
     * Este método elimina registros de la tabla especificada, utilizando la 
     * condición proporcionada. Si la condición no es especificada, se lanza 
     * una excepción para evitar la eliminación accidental de todos los registros.
     * Además, registra la acción en el log de auditoría, indicando cuántas 
     * filas fueron afectadas.
     *
     * @param string $nombre_tabla Nombre de la tabla de la cual se eliminarán los registros.
     * @param string $condicion Condición que especifica qué registros se deben eliminar (ej. 'id = 1').
     * @throws Exception Si la condición está vacía o si ocurre un error al preparar o ejecutar la consulta.
     * @return array Resultado de la operación, incluyendo un indicador de éxito y el número de filas afectadas.
     * 
     * 
     */



    public function delete($nombre_tabla, $condicion)
    {
        if (empty($condicion)) {
            throw new Exception("No se ha recibido la información requerida.");
        }

        $valores = [];
        // Eliminar espacios alrededor de los operadores en $condicion
        $condicion = preg_replace('/\s*([=<>!]+)\s*/', '$1', $condicion);

        // Reemplazar valores después de operadores con '?'
        $condicion = preg_replace_callback('/([=<>!]+)(["\'`]?)(\S+)\2/', function ($matches) use (&$valores) {
            // Eliminar comillas y añadir el valor limpio al array
            $valor = trim($matches[3], "'\"`");
            $valores[] = $valor; // Añadir el valor al array
            return $matches[1] . ' ?'; // Retornar el operador seguido de '?'
        }, $condicion);

        $query = "DELETE FROM `$nombre_tabla` WHERE $condicion";

        // Preparar la consulta
        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta DELETE: " . $this->conexion->error);
        }

        // Determinar tipos y vincular parámetros
        if (!empty($valores)) {
            $tipos = '';
            foreach ($valores as $valor) {
                $tipos .= $this->getParamType($valor);
            }
            $stmt->bind_param($tipos, ...$valores);
        }

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Error al ejecutar la consulta DELETE: " . $stmt->error);
        }

        // Cerrar la sentencia y registrar la acción
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        // Registrar la acción
        $this->logAction('DELETE', $nombre_tabla, $condicion, $affected_rows);

        return [
            'success' => true,
            'affected_rows' => $affected_rows
        ];
    }

    /**
     * Actualizar registros en la base de datos.
     * 
     * Este método permite actualizar uno o varios campos de registros en una tabla específica. 
     *
     * @param string $nombre_tabla Nombre de la tabla donde se realizarán las actualizaciones.
     * @param array $valores Array con los campos, valores y tipos a actualizar en el formato [$campo, $valor, $tipo].
     * @param string $where Condición que indica qué registros se deben actualizar (ej. 'id = 1').
     * @throws Exception Si ocurre un error al preparar, ejecutar la consulta o al registrar la acción.
     * @return array Resultado de la operación, indicando si fue exitosa y cuántas filas fueron afectadas.
     */
    public function update($nombre_tabla, $valores, $where)
    {
        if (empty($valores) || empty($where)) {
            throw new Exception("Se requieren valores para actualizar y una condición WHERE.");
        }

        $set_clause = [];
        $param_types = "";
        $params = [];

        foreach ($valores as $item) {
            if (count($item) < 2) {
                throw new Exception("Cada elemento del array de valores debe contener al menos el campo y el valor.");
            }

            $campo = $item[0];
            $valor = $item[1];

            // Si el tipo está definido en el índice 2, se usa; de lo contrario, se determina con getParamType
            $tipo = isset($item[2]) ? $item[2] : $this->getParamType($valor);

            $set_clause[] = "$campo = ?";
            $param_types .= $tipo;
            $params[] = $valor;
        }

        $set_clause_string = implode(", ", $set_clause);

        // Preparar la consulta SQL
        $query = "UPDATE `$nombre_tabla` SET $set_clause_string WHERE $where";
        $stmt = $this->conexion->prepare($query);

        // Verificar si la preparación fue exitosa
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta UPDATE: " . $this->conexion->error);
        }

        // Vincular los parámetros
        $stmt->bind_param($param_types, ...$params);

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Error al ejecutar la consulta UPDATE: " . $stmt->error);
        }

        // Cerrar la sentencia y registrar la acción
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        $this->logAction('UPDATE', $nombre_tabla, $where, $affected_rows);

        return [
            'success' => true,
            'affected_rows' => $affected_rows
        ];
    }

    /**
     * Almacenar las acciones realizada por el usuario.
     * 
     * Este método inserta un registro en la tabla de auditoría con la información 
     * sobre la acción realizada, incluyendo el tipo de acción, la tabla afectada, 
     * la condición bajo la cual se realizó la acción y la cantidad de filas afectadas. 
     * También almacena el ID del usuario que realizó la acción.
     *
     * @param string $action_type Tipo de acción realizada (ej. 'INSERT', 'DELETE', 'UPDATE').
     * @param string $table_name Nombre de la tabla afectada por la acción.
     * @param string $condition Condición que describe la acción (ej. 'id = 1').
     * @param int $affected_rows Número de filas afectadas por la acción.
     * @throws Exception Si ocurre un error al preparar o ejecutar la consulta.
     */
    public function logAction($action_type, $table_name, $condition, $affected_rows)
    {
        $user_id = $_SESSION['u_id'];
        $query = "INSERT INTO audit_logs (action_type, table_name, situation, affected_rows, user_id) VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($query);

        if (!$stmt) {
            throw new Exception("Error al preparar la consulta para registrar acción: " . $this->conexion->error);
        }

        $stmt->bind_param('sssis', $action_type, $table_name, $condition, $affected_rows, $user_id);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Error al registrar la acción: " . $stmt->error);
        }
        $stmt->close();
    }


    /**
     * Comprobar la existencia de registros en múltiples tablas.
     * 
     * @param array $tablas tablas con sus condiciones a verificar.
     * @return int Número total de coincidencias encontradas.
     */
    public function comprobar_existencia(array $tablas)
    {
        $totalCoincidencias = 0;

        foreach ($tablas as $tabla) {
            if (isset($tabla['tabla']) && isset($tabla['condicion'])) {
                $resultado = json_decode($this->select(null, $tabla['tabla'], $tabla['condicion'], null, null));

                if (isset($resultado->success) && is_array($resultado->success)) {
                    $totalCoincidencias += count($resultado->success);
                }
            } else {
                throw new Exception("Cada entrada debe contener 'tabla' y 'condicion'.");
            }
        }

        return $totalCoincidencias;
    }
}
