<?php
require_once(__DIR__ . "/NotaiFactura.class.php");
class CancelarFacturaRequest
{
    /** @var NotaiFactura */
    public $Nota;
    /** @var String */
    public $Email;
    /** @var String */
    public $Password;
    public function __construct()
    {
        $this->Email = "";
        $this->Password = "";
        $this->Nota = new NotaiFactura();
    }
}