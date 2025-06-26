<?php
class EmitirFacturaResponse
{
    public $Exito;
    public $IdFactura;
    public $Mensaje;
    public $conversionJSONResultado;
    public function __construct($json = false)
    {
        $this->Exito = false;
        $this->IdFactura = "";
        $this->Mensaje = "";
        $this->conversionJSONResultado = true;
        if ($json) {
            $this->set(json_decode($json, true));
            $this->conversionJSONResultado = json_last_error() === JSON_ERROR_NONE;
        }
    }
    private function set($data) {
        foreach ($data AS $key => $value) {
            if (is_array($value)) {
                $sub = new JSONObject;
                $sub->set($value);
                $value = $sub;
            }
            $this->{$key} = $value;
        }
    }
}