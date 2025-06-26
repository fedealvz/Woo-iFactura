<?php
class ClienteiFactura
{
    public $RazonSocial;
    public $Identificador;
    public $Email;
    public $Direccion;
    public $Localidad;
    public $CodigoPostal;
    public $Provincia;
    public $Provincia_str;
    public $CondicionImpositiva;
    public $TipoPersona;
    public $TipoDocumento;
    public $Actualizar;
    public function __construct()
    {
        $this->RazonSocial = "";
        $this->Identificador = "";
        $this->Email = "";
        $this->Direccion = "";
        $this->Localidad = "";
        $this->CodigoPostal = "";
        $this->Provincia = 0;
        $this->Provincia_str = "";
        $this->CondicionImpositiva = 0;
        $this->TipoPersona = 0;
        $this->TipoDocumento = 0;
        $this->Actualizar = false;
    }
}
?>