<?php
class EmitirFacturaRequest
{
    public $Email;
    public $Password;
    public $Factura;
    public function __construct()
    {
        $this->Email = "";
        $this->Password = "";
    }
    public function esValido()
    {
        if (!empty($this->Email) && !empty($this->Password) && !empty($this->Factura))
        {
            return true;
        }
        return false;
    }
}