<?php
require_once(__DIR__ . "/ClienteiFactura.class.php");
require_once(__DIR__ . "/DetalleFacturaiFactura.class.php");
class FacturaiFactura
{
    public $Numero;
    public $Fecha;
    public $FechaVencimiento;
    public $FechaDesde;
    public $FechaHasta;
    public $AutoEnvioCorreo;
    public $PuntoVenta;
    public $FormaPago;
    public $TipoComprobante;
    public $DetalleFactura;
    /** @var ClienteiFactura */
    public $Cliente;
    public function __construct()
    {
        $this->Numero = "";
        $this->Fecha = date("Y-m-d H:i:s");
        $this->FechaVencimiento =  date("Y-m-d H:i:s", strtotime("+10 day"));
        $this->FechaDesde = date("Y-m-d H:i:s");
        $this->FechaHasta = date("Y-m-d H:i:s", strtotime("+10 day"));
        $this->AutoEnvioCorreo = false;
        $this->PuntoVenta = 0;
        $this->FormaPago = 0;
        $this->TipoComprobante = 0;
        $this->DetalleFactura = array();
        $this->Cliente = new ClienteiFactura();
    }
}
?>