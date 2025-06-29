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

        // Precio en dólares por unidad

        $precioDolarCompra = $precioCompra / $cantidad_por_precio;


        // Precio de venta en dólares
        $precioDolarVenta = $precioDolarCompra + ($precioDolarCompra * $porcentaje / 100);
        $precioDolarVisible = number_format($precioDolarVenta, 2, '.', ',');

        // Precio en pesos (dólar → pesos)
        $precioPesoVenta = $this->formatPeso($precioDolarVenta * $this->pesoDolar);

        // Precio en bolívares según el origen
        if ($origen === 'c') {
            $precioBsVenta = ($precioPesoVenta / $this->peso_bolivar) / 1000;
        } else {
            $precioBsVenta = $precioDolarVenta * $this->dolarBolivar;
        }

        // Bolívares → pesos
        $precioBolivarPeso = $this->formatPeso(($precioBsVenta * $this->bolivar_peso) * 1000);

        // Bolívares → dólares
        $precio_bolivar_dolar = $precioBsVenta / $this->bcv;

        // Determinar precio visible en pesos
        $valorPesos = isset($this->data_monedas['precio_peso_visible']) ? $precioPesoVenta : $precioBolivarPeso;

        // Determinar precio visible en dólares
        $precioDolar = isset($this->data_monedas['precio_dolar_visible']) ? $precioDolarVisible : number_format($precio_bolivar_dolar, 2, '.', ',');

        return [
            'precio_venta_dolar' => (float) $precioDolar,
            'precio_venta_bs' => (float) $precioBsVenta,
            'precio_venta_peso' => (float) $valorPesos
        ];
    }

    private function formatPeso(float $valor): float
    {
        return round($valor, 0); // Puedes aplicar otro formato si tienes uno propio
    }
}
