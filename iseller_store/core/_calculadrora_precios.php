<?php
class CalculadoraPrecios
{
    private float $pesoDolar;
    private float $peso_bolivar;
    private float $dolarBolivar;
    private float $bolivar_peso;
    private float $bcv;
    private array $data_monedas;
    private array $recompensas;

    /** @var object|null Conexión mysqli para resolver tasas personalizadas. */
    private $conexion;
    private int $bssId;
    private array $tasasProductos = [];
    private bool $tasasProductosCargadas = false;
    private static array $cacheTasasProductos = [];

    public function __construct(
        float $pesoDolar,
        float $peso_bolivar,
        float $dolarBolivar,
        float $bolivar_peso,
        float $bcv,
        array $data_monedas,
        array $recompensas = [],
        $conexion = null,
        int $bssId = 0
    ) {
        $this->pesoDolar = $pesoDolar;
        $this->peso_bolivar = $peso_bolivar;
        $this->dolarBolivar = $dolarBolivar;
        $this->bolivar_peso = $bolivar_peso;
        $this->bcv = $bcv;
        $this->data_monedas = $data_monedas;
        $this->recompensas = $recompensas;
        $this->conexion = $conexion;
        $this->bssId = $bssId;
    }

    /** Carga una sola vez las tasas personalizadas asignadas a los productos del negocio. */
    private function cargarTasasProductos(): void
    {
        if ($this->tasasProductosCargadas) {
            return;
        }
        $this->tasasProductosCargadas = true;

        if (!$this->conexion || $this->bssId <= 0) {
            return;
        }
        if (isset(self::$cacheTasasProductos[$this->bssId])) {
            $this->tasasProductos = self::$cacheTasasProductos[$this->bssId];
            return;
        }

        $sql = "SELECT pt.producto_id, pt.par, tp.valor
                FROM producto_tasas_personalizadas pt
                INNER JOIN cambio_tasas_personalizadas tp
                    ON tp.id = pt.tasa_id
                   AND tp.bss_id = pt.bss_id
                   AND tp.par = pt.par
                WHERE pt.bss_id = ?";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            self::$cacheTasasProductos[$this->bssId] = [];
            return;
        }
        $stmt->bind_param('i', $this->bssId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $valor = (float) $fila['valor'];
            if ($valor > 0) {
                $this->tasasProductos[(int) $fila['producto_id']][$fila['par']] = $valor;
            }
        }
        $stmt->close();

        self::$cacheTasasProductos[$this->bssId] = $this->tasasProductos;
    }

    /**
     * Tasas efectivas de un producto: principales + overrides por par.
     */
    public function tasasDeProducto(array $producto): array
    {
        $this->cargarTasasProductos();

        $tasas = [
            'pesoDolar' => $this->pesoDolar,
            'peso_bolivar' => $this->peso_bolivar,
            'dolarBolivar' => $this->dolarBolivar,
            'bolivar_peso' => $this->bolivar_peso,
            'bcv' => $this->bcv,
        ];

        $productoId = (int) ($producto['producto_id'] ?? $producto['id'] ?? 0);
        $overrides = $productoId > 0 ? ($this->tasasProductos[$productoId] ?? []) : [];

        foreach ($overrides as $par => $valor) {
            if ((float) $valor <= 0) {
                continue;
            }
            switch ($par) {
                case 'usd_bs':
                    $tasas['dolarBolivar'] = (float) $valor;
                    $tasas['bcv'] = (float) $valor;
                    break;
                case 'usd_cop':
                    $tasas['pesoDolar'] = (float) $valor;
                    break;
                case 'cop_bs':
                    $tasas['peso_bolivar'] = (float) $valor;
                    break;
                case 'bs_cop':
                    $tasas['bolivar_peso'] = (float) $valor;
                    break;
            }
        }

        // Alias para el frontend del POS (tasasCalculo usa camelCase).
        $tasas['pesoBolivar'] = $tasas['peso_bolivar'];
        $tasas['bolivarPeso'] = $tasas['bolivar_peso'];

        return $tasas;
    }

    public function getTasa(string $nombre): float
    {
        switch ($nombre) {
            case 'dolarBolivar':
            case 'dolar_bolivar':
                return $this->dolarBolivar;
            case 'pesoDolar':
            case 'dolarPeso':
            case 'peso_dolar':
            case 'dolar_peso':
                return $this->pesoDolar;
            case 'peso_bolivar':
            case 'pesoBolivar':
                return $this->peso_bolivar;
            case 'bolivar_peso':
            case 'bolivarPeso':
                return $this->bolivar_peso;
            case 'bcv':
                return $this->bcv;
            default:
                return 0.0;
        }
    }

    public function getTasaProducto(string $nombre, array $producto): float
    {
        $t = $this->tasasDeProducto($producto);
        switch ($nombre) {
            case 'dolarBolivar':
            case 'dolar_bolivar':
                return $t['dolarBolivar'];
            case 'pesoDolar':
            case 'dolarPeso':
            case 'peso_dolar':
            case 'dolar_peso':
                return $t['pesoDolar'];
            case 'peso_bolivar':
            case 'pesoBolivar':
                return $t['peso_bolivar'];
            case 'bolivar_peso':
            case 'bolivarPeso':
                return $t['bolivar_peso'];
            case 'bcv':
                return $t['bcv'];
            default:
                return 0.0;
        }
    }

    public function calcularPrecios(array $producto): array
    {
        $cantidadUnidad = (float) $producto["cantidad_unidades"];
        $origen = $producto["origen"];
        $precioCompra = (float) $producto["precio_compra"];
        $porcentaje = (float) $producto["porcentaje"];
        $mayor = $producto["mayor"];
        $cantidad_por_precio = ($mayor == '1' ? 1 : $cantidadUnidad);
        if ($cantidad_por_precio <= 0) {
            $cantidad_por_precio = 1;
        }

        $t = $this->tasasDeProducto($producto);

        // Sin tasas válidas no se puede calcular; evitar división por cero.
        if ($t['peso_bolivar'] <= 0 || $t['bcv'] <= 0) {
            return [
                'precio_venta_dolar' => 0.0,
                'precio_venta_bs' => 0.0,
                'precio_venta_peso' => 0.0
            ];
        }

        // verifica si $this->recompensas esta vacio
        if (empty($this->recompensas)) {
            $recompensas = [];
        }else{
            $recompensas = $this->recompensas[0];
        }

        // Precio en dólares por unidad

        $precioDolarCompra = $precioCompra / $cantidad_por_precio;


        // Precio de venta en dólares
        $precioDolarVenta = $precioDolarCompra + ($precioDolarCompra * $porcentaje / 100);

        // Precio en pesos (dólar → pesos)
        $precioPesoVenta = $this->formatPeso($precioDolarVenta * $t['pesoDolar']);
        $precioPesoCompra = $this->formatPeso($precioDolarCompra * $t['pesoDolar']);
        // Precio en bolívares según el origen
        if ($origen === 'c') {
            $precioBsVenta = ($precioPesoVenta / $t['peso_bolivar']) / 1000;
            $precioBsCompra = ($precioPesoCompra / $t['peso_bolivar']) / 1000;
        } elseif ($origen === 'm') {
            $precioBsVenta = $precioDolarVenta * $t['bcv'];
            $precioBsCompra = $precioDolarCompra * $t['bcv'];
        } else {
            $precioBsVenta = $precioDolarVenta * $t['dolarBolivar'];
            $precioBsCompra = $precioDolarCompra * $t['dolarBolivar'];
        }


        // Bolívares → pesos
        $precioBolivarPeso = $this->formatPeso(($precioBsVenta * $t['bolivar_peso']) * 1000);

        // Bolívares → dólares
        $precio_bolivar_dolar = $precioBsVenta / $t['bcv'];

        
        // Determinar precio visible en dólares.
        // Se redondea el número, no el texto: number_format() agrega comas de miles y
        // castear "1,234.56" a float devolvía 1.00 en productos de más de $999.99.
        $precioDolar = isset($this->data_monedas['precio_dolar_visible'])
            ? round($precioDolarVenta, 2)
            : round($precio_bolivar_dolar, 2);


        // Determinar precio visible en pesos // precio_peso_visible es el resultado de multiplicar los dolares * la tasa de pesos

        $valorPesos = isset($this->data_monedas['precio_peso_visible']) ||
        $origen == 'c' ? $precioPesoVenta : $precioBolivarPeso;


        $precioDolarRecompensa = $precioDolar;
        $precioBsRecompensa = $precioBsVenta;

        // Aplicar recompensa

        if (!empty($recompensas)) {
            if ($recompensas && $recompensas['tipo'] === 'descuento_ganancia') {
                $descuento = $recompensas['porcentaje'];

                $ganancia_dolar = $precioDolar - $precioDolarCompra;
                $ganancia_bs = $precioBsVenta - $precioBsCompra;

                $precioDolarRecompensa = $precioDolar - ($ganancia_dolar * $descuento);
                $precioBsRecompensa = $precioBsVenta - ($ganancia_bs * $descuento);
            }
        }

        return [
            'recompensa' => $recompensas,
            'precio_venta_dolar' => (float) $precioDolar,
            'precio_venta_bs' => (float) $precioBsVenta,
            'precio_venta_peso' => (float) $valorPesos,
            // El precio de venta normal
            'dolar_con_recompensa' => (float) $precioDolarRecompensa,
            'bs_con_recompensa' => (float) $precioBsRecompensa,
            // El precio de venta con recompensa
            'precio_dolar_compra' => (float) $precioDolarCompra,
            'precio_bs_compra' => (float) $precioBsCompra
            // El precio de compra
        ];
    }

    public function convertirMonto($monto, $monedaOrigen, $origen, $producto = null)
    {
        $resultado = [
            'usd' => 0,
            'bs' => 0,
            'cop' => 0
        ];

        // Validación rápida
        if (!in_array($monedaOrigen, ['bs', 'usd', 'cop'])) {
            return $resultado;
        } // si no exta se retorna el array con valores 0

        $productoArr = is_array($producto)
            ? $producto
            : (is_numeric($producto) ? ['id' => (int) $producto] : []);
        $t = $this->tasasDeProducto($productoArr);

        // Caso 1 - Si la moneda origen es dolar y el producto es venezonlano
        if ($monedaOrigen === 'usd' && $origen != 'c') {

            // ** Precio en dólares
            $dolares = $monto; // Monto en dólares

            $resultado['usd'] = round($dolares, 2);



            // ** Precio en bolívares (dólar → bolívares)
            $bolivares = $monto * $t['dolarBolivar'];
            $resultado['bs'] = round($bolivares, 2);



            // ** Precio en pesos (dólar → pesos)
            $pesos = $monto * $t['pesoDolar'];

            // Para los pesos depende de la configuracion
            $valorPesos = isset($this->data_monedas['precio_peso_visible'])
                ? $pesos
                : ($bolivares * $t['bolivar_peso']) * 1000;

            $resultado['cop'] = $valorPesos;
        } // REVISADO

        // Caso 2 - Si la moneda origen es dolares y el producto es colombiano
        if ($monedaOrigen === 'usd' && $origen === 'c') {

            // ** Precio en pesos (dólar → pesos)
            $pesos = $monto * $t['pesoDolar'];
            $resultado['cop'] = $pesos;

            // ** Precio en bolívares (dólar → bolívares)
            $bolivares = ($pesos / $t['peso_bolivar']) / 1000;
            $resultado['bs'] = round($bolivares, 2);

            // ** Precio en dólares
            $resultado['usd'] = round($monto, 2);
        } // REVISADO

        // Caso 3 - Si la moneda origen es bolívares y el producto es venezolano
        if ($monedaOrigen === 'bs' && $origen != 'c') {

            // ** Precio en dólares
            // Para los dolares dependiendo de la configuracion
            $valorDolares =  $monto / $t['dolarBolivar'];

            $resultado['usd'] = round($valorDolares, 2);



            // ** Precio en bolívares (dólar → bolívares)
            $resultado['bs'] = round($monto, 2);



            // ** Precio en pesos (bolivar → pesos)
            $pesos = $valorDolares * $t['pesoDolar'];

            // Para los pesos depende de la configuracion
            $valorPesos = isset($this->data_monedas['precio_peso_visible'])
                ? $pesos
                : ($monto * $t['bolivar_peso']) * 1000;

            $resultado['cop'] = $valorPesos;
        } // REVISADO

        // Caso 4 - Si la moneda origen es bolivares y el producto es colombiano
        if ($monedaOrigen === 'bs' && $origen === 'c') { // primero se debe llevar a pesos y luego a dolares

            // ** Precio en pesos (bolivar → pesos)
            $pesos = ($monto * $t['peso_bolivar']) * 1000;
            $resultado['cop'] = $pesos;


            // ** Precio en dólares
            $valorDolares = $pesos / $t['pesoDolar'];
            $resultado['usd'] = round($valorDolares, 2);


            // ** Precio en bolívares (dólar → bolívares)
            $resultado['bs'] = round($monto, 2);
        } // REVISADO

        // Caso 5 - Si la moneda origen es pesos y el producto es venezolano
        if ($monedaOrigen === 'cop' && $origen != 'c') {

            if (isset($this->data_monedas['precio_bolivar_peso'])) { // TODO: HERE
                // ** Precio en bolívares (peso → bolívares)
                $bolivares = ($monto / $t['bolivar_peso']) / 1000;
                $resultado['bs'] = round($bolivares, 2);

                // ** Precio en dólares
                $valorDolares = $bolivares / $t['bcv'];
                $resultado['usd'] = round($valorDolares, 2);
            } else {
                // ** Precio en dólares
                $dolares = $monto / $t['pesoDolar'];
                $resultado['usd'] = round($dolares, 2);

                // ** Precio en bolívares (dólar → bolívares)
                $bolivares = $dolares * $t['dolarBolivar'];
                $resultado['bs'] = round($bolivares, 2);
            }

            // ** Precio en pesos
            $resultado['cop'] = round($monto, 0); // Monto en pesos ya que es la moneda de origen
        } // REVISADO

        // Caso 6 - Si la moneda origen es pesos y el producto es colombiano
        if ($monedaOrigen === 'cop' && $origen === 'c') {

            // ** Precio en pesos
            $resultado['cop'] = round($monto, 0); // Monto en pesos ya que es la moneda de origen

            // ** Precio en dólares
            $dolares = $monto / $t['pesoDolar'];
            $resultado['usd'] = round($dolares, 2);

            // ** Precio en bolívares (peso → bolívares)
            $bolivares = ($monto / $t['peso_bolivar']) / 1000;
            $resultado['bs'] = round($bolivares, 2);
        }
        return $resultado;
    }



    private function formatPeso(float $valor): float
    {
        if ($valor < 100) {
            return 100;
        }

        $residuo = fmod($valor, 100);

        if ($residuo >= 50) {
            return ceil($valor / 100) * 100; // Redondear hacia la siguiente centena
        } else {
            return floor($valor / 100) * 100; // Redondear hacia abajo
        }
    }
}
