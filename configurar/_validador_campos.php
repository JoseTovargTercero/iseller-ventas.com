<?php

class ValidadorCampos
{
    private $campos;
    private $origen;
    private $faltantes = [];

    /**
     * @param array $campos - Lista de nombres de campos a validar.
     * @param string $origen - 'POST' o 'GET' (por defecto: 'POST').
     */
    public function __construct(array $campos, string $origen = 'POST')
    {
        $this->campos = $campos;
        $this->origen = strtoupper($origen);
    }

    public function validar()
    {
        $fuente = $this->origen === 'GET' ? $_GET : $_POST;

        foreach ($this->campos as $campo) {
            if (!isset($fuente[$campo]) || $fuente[$campo] === '') {
                $this->faltantes[] = $campo;
            }
        }

        if (!empty($this->faltantes)) {
            $this->respuestaError();
        }
    }

    private function respuestaError()
    {
        echo json_encode([
            'success' => false,
            'message' => 'Faltan los siguientes campos: ' . implode(', ', $this->faltantes)
        ]);
        exit;
    }
}
