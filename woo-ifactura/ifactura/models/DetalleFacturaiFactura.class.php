<?php
class DetalleFacturaiFactura
{
    public $Cantidad;
    public $ValorUnitario;
    public $Total;
    public $Descripcion;
    public $Codigo;
    public $AlicuotaIVA;
    public $UnidadMedida;
    public $Bonificacion;
    public $IVA;
    public $ConceptoFactura;
    public function __construct()
    {
        $this->Cantidad = 0;
        $this->ValorUnitario = 0.0;
        $this->Total = 0.0;
        $this->Descripcion = "";
        $this->Codigo = "";
        $this->AlicuotaIVA = 0;
        $this->UnidadMedida = 0;
        $this->Bonificacion = 0.0;
        $this->IVA = 0.0;
        $this->ConceptoFactura = 0;
    }
}