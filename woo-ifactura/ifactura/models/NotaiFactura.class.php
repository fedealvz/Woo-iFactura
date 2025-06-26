<?php
class NotaiFactura
{
    public $HashFactura;
    public $NotaCredito;
    public $AutoEnviarCorreo;
    public function __construct()
    {
        $this->HashFactura = "";
        $this->NotaCredito = true;
        $this->AutoEnviarCorreo = true;
    }
}