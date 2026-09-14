<?php

namespace App\Exceptions;

use RuntimeException;

class StockInsuficienteException extends RuntimeException
{
    public function __construct(string $nombreProducto, float $stockActual, float $cantidadSolicitada)
    {
        parent::__construct(
            "Stock insuficiente de \"{$nombreProducto}\": hay {$stockActual} y se intentó descontar {$cantidadSolicitada}.",
        );
    }
}
