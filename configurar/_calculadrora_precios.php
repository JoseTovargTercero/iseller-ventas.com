<?php
class CalculadoraPrecios
{
    private float $pesoDolar;
    private float $peso_bolivar;
    private float $dolarBolivar;
    private float $bolivar_peso;
    private float $bcv;
    private array $data_monedas;

    public function __construct(
        float $pesoDolar,
        float $peso_bolivar,
        float $dolarBolivar,
        float $bolivar_peso,
        float $bcv,
        array $data_monedas
    ) {
        $this->pesoDolar = $pesoDolar;
        $this->peso_bolivar = $peso_bolivar;
        $this->dolarBolivar = $dolarBolivar;
        $this->bolivar_peso = $bolivar_peso;
        $this->bcv = $bcv;
        $this->data_monedas = $data_monedas;
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

        // Sin tasas válidas no se puede calcular; evitar división por cero.
        if ($this->peso_bolivar <= 0 || $this->bcv <= 0) {
            return [
                'precio_venta_dolar' => 0.0,
                'precio_venta_bs' => 0.0,
                'precio_venta_peso' => 0.0
            ];
        }

        // Precio en dólares por unidad

        $precioDolarCompra = $precioCompra / $cantidad_por_precio;


        // Precio de venta en dólares
        $precioDolarVenta = $precioDolarCompra + ($precioDolarCompra * $porcentaje / 100);

        // Precio en pesos (dólar → pesos)
        $precioPesoVenta = $this->formatPeso($precioDolarVenta * $this->pesoDolar);

        // Precio en bolívares según el origen
        if ($origen === 'c') {
            $precioBsVenta = ($precioPesoVenta / $this->peso_bolivar) / 1000;
        } elseif ($origen === 'm') {
            $precioBsVenta = $precioDolarVenta * $this->bcv;
        } else {
            $precioBsVenta = $precioDolarVenta * $this->dolarBolivar;
        }

        // Bolívares → pesos
        $precioBolivarPeso = $this->formatPeso(($precioBsVenta * $this->bolivar_peso) * 1000);

        // Bolívares → dólares
        $precio_bolivar_dolar = $precioBsVenta / $this->bcv;

        // Determinar precio visible en pesos // precio_peso_visible es el resultado de multiplicar los dolares * la tasa de pesos

        $valorPesos = isset($this->data_monedas['precio_peso_visible']) ||
            $origen == 'c' ? $precioPesoVenta : $precioBolivarPeso;





        // Determinar precio visible en dólares.
        // Se redondea el número, no el texto: number_format() agrega comas de miles y
        // castear "1,234.56" a float devolvía 1.00 en productos de más de $999.99.
        $precioDolar = isset($this->data_monedas['precio_dolar_visible'])
            ? round($precioDolarVenta, 2)
            : round($precio_bolivar_dolar, 2);

        return [
            'precio_venta_dolar' => (float) $precioDolar,
            'precio_venta_bs' => (float) $precioBsVenta,
            'precio_venta_peso' => (float) $valorPesos
        ];
    }

    public function convertirMonto($monto, $monedaOrigen, $origen)
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

        // Caso 1 - Si la moneda origen es dolar y el producto es venezolano
        if ($monedaOrigen === 'usd' && $origen != 'c') {

            $resultado['usd'] = round($monto, 2);

            // El bolívar del producto venezolano siempre es dólar * dolarBolivar; si el carrito
            // muestra el dólar por BCV (precio_bolivar_dolar), ése es el dólar visible.
            $bolivares = isset($this->data_monedas['precio_dolar_visible'])
                ? $monto * $this->dolarBolivar
                : $monto * $this->bcv;
            $resultado['bs'] = round($bolivares, 2);

            // Los pesos salen de la base en dólares o directo del bolívar.
            if (isset($this->data_monedas['precio_peso_visible'])) {
                // Con precio_dolar_visible el monto YA es el dólar base; si no, se deriva del bolívar.
                $usdBase = isset($this->data_monedas['precio_dolar_visible'])
                    ? $monto
                    : $bolivares / $this->dolarBolivar;
                $resultado['cop'] = $usdBase * $this->pesoDolar;
            } else {
                $resultado['cop'] = ($bolivares * $this->bolivar_peso) * 1000;
            }
        } // REVISADO

        // Caso 2 - Si la moneda origen es dolares y el producto es colombiano
        if ($monedaOrigen === 'usd' && $origen === 'c') {

            $resultado['usd'] = round($monto, 2);

            if (isset($this->data_monedas['precio_dolar_visible'])) {
                // ** Pesos directos y de ahí a bolívares
                $pesos = $monto * $this->pesoDolar;
                $bolivares = ($pesos / $this->peso_bolivar) / 1000;
            } else {
                // Dólar mostrado = bolívares / BCV
                $bolivares = $monto * $this->bcv;
                $pesos = ($bolivares * $this->peso_bolivar) * 1000;
            }

            $resultado['cop'] = $pesos;
            $resultado['bs'] = round($bolivares, 2);
        } // REVISADO

        // Caso 3 - Si la moneda origen es bolívares y el producto es venezolano
        if ($monedaOrigen === 'bs' && $origen != 'c') {

            $resultado['bs'] = round($monto, 2);

            // Base en dólares del producto venezolano: siempre bolívares / dolarBolivar.
            $usdBase = $monto / $this->dolarBolivar;
            $resultado['usd'] = round(
                isset($this->data_monedas['precio_dolar_visible']) ? $usdBase : ($monto / $this->bcv),
                2
            );

            $resultado['cop'] = isset($this->data_monedas['precio_peso_visible'])
                ? ($usdBase * $this->pesoDolar)
                : (($monto * $this->bolivar_peso) * 1000);
        } // REVISADO

        // Caso 4 - Si la moneda origen es bolivares y el producto es colombiano
        if ($monedaOrigen === 'bs' && $origen === 'c') { // primero se debe llevar a pesos y luego a dolares

            $resultado['bs'] = round($monto, 2);

            $pesos = ($monto * $this->peso_bolivar) * 1000;
            $resultado['cop'] = $pesos;

            $resultado['usd'] = round(
                isset($this->data_monedas['precio_dolar_visible']) ? ($pesos / $this->pesoDolar) : ($monto / $this->bcv),
                2
            );
        } // REVISADO

        // Caso 5 - Si la moneda origen es pesos y el producto es venezolano
        if ($monedaOrigen === 'cop' && $origen != 'c') {

            $resultado['cop'] = round($monto, 0); // Monto en pesos ya que es la moneda de origen

            if (isset($this->data_monedas['precio_bolivar_peso'])) {
                // Pesos → bolívares por la tasa Bs/Peso del negocio
                $bolivares = ($monto / $this->bolivar_peso) / 1000;
            } else {
                // Pesos → dólares base → bolívares
                $bolivares = ($monto / $this->pesoDolar) * $this->dolarBolivar;
            }
            $resultado['bs'] = round($bolivares, 2);

            $resultado['usd'] = round(
                isset($this->data_monedas['precio_dolar_visible']) ? ($bolivares / $this->dolarBolivar) : ($bolivares / $this->bcv),
                2
            );
        } // REVISADO

        // Caso 6 - Si la moneda origen es pesos y el producto es colombiano
        if ($monedaOrigen === 'cop' && $origen === 'c') {

            $resultado['cop'] = round($monto, 0); // Monto en pesos ya que es la moneda de origen

            $bolivares = ($monto / $this->peso_bolivar) / 1000;
            $resultado['bs'] = round($bolivares, 2);

            $resultado['usd'] = round(
                isset($this->data_monedas['precio_dolar_visible']) ? ($monto / $this->pesoDolar) : ($bolivares / $this->bcv),
                2
            );
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
