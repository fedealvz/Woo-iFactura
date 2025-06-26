<?php
class ConfiguracioniFactura
{
    /** @var Boolean Modo debug para ver var_dumps y más información de los procesos */
    public $modoDebug;
    /** @var Boolean Utilizar el modo demo de iFactura */
    public $modoDemo;
    /** @var String Usuario */
    public $usuario;
    /** @var String Password */
    public $password;
    /** @var Boolean Enviar comprobante emitido */
    public $autoEnvio;
    /** @var Integer Numero de punto de venta */
    public $puntoVenta;
    /** @var Integer Condicion impositiva configurada como emisor */
    public $condicionImpositiva;
    /** @var Boolean */
    public $facturarAutomatico;
    /** @var String */
    public $eventoParaFacturar;
    /** @var Boolean */
    public $ignorarEnvio;
    /** @var Boolean */
    public $agruparVenta;
    /** @var Integer Si es 0 se interpresta como 0%, si es 1 es Exento y si es 2 es No Gravado */
    public $considerarIVA0Como;
    public function __construct()
    {
        $this->modoDebug = false;
        $this->modoDemo = false;
        $this->facturarAutomatico = false;
        $this->eventoParaFacturar = "";
        $this->ignorarEnvio = false;
        $this->agruparVenta = false;
        $this->getModoSandbox();
        $this->usuario = get_option('wc_settings_tab_woo_ifactura_user');
        $this->password = get_option('wc_settings_tab_woo_ifactura_hash');
        $this->getAutoenvio();
        $this->puntoVenta = intval(get_option('wc_settings_tab_woo_ifactura_prefix'));
        $this->condicionImpositiva = intval(get_option('wc_settings_tab_woo_ifactura_impositive_treatment'));
        $this->getFacturarAutomatico();
        $this->considerarIVA0Como = intval(get_option('wc_settings_tab_woo_ifactura_considerariva0'));
        $this->woo_ifactura_getIgnorarEnvio();
        $this->woo_ifactura_getAgruparVenta();
    }
     /**
     * Método para saber si esta activada la opción de ignorar el envio
     * @return boolean
     */
    private function woo_ifactura_getIgnorarEnvio()
    {
        $ignorarShipping = get_option('wc_settings_tab_woo_ifactura_ignorarenvio');
        if (!$ignorarShipping || $ignorarShipping == 'no') {
            $ignorarShipping = false;
        } else {
            $ignorarShipping = true;
        }
        $this->ignorarEnvio = $ignorarShipping;
        return $ignorarShipping;
    }
    /**
     * Método para saber si esta activada la opción de agrupar los elementos de un venta
     * @return boolean
     */
    private function woo_ifactura_getAgruparVenta()
    {
        $agruparVenta = get_option('wc_settings_tab_woo_ifactura_agruparventa');
        if (!$agruparVenta || $agruparVenta == 'no') {
            $agruparVenta = false;
        } else {
            $agruparVenta = true;
        }
        $this->agruparVenta = $agruparVenta;
        return $agruparVenta;
    }
    private function getAutoenvio()
    {
        $autoenvio = get_option('wc_settings_tab_woo_ifactura_autoenvio');
        if (!$autoenvio || $autoenvio == 'no') {
            $this->autoEnvio = false;
        } else {
            $this->autoEnvio = true;
        }
        return $this->autoEnvio;
    }
    private function getFacturarAutomatico()
    {
        $facturaAutomatico = get_option('wc_settings_tab_woo_ifactura_facturarautomatico');
        if (!$facturaAutomatico || intval($facturaAutomatico) == 1 || intval($facturaAutomatico) == 0) {
            $this->facturarAutomatico = false;
        } else {
            $estadoEventoSeleccionado = "";
            switch (intval($facturaAutomatico)) {
                case 2:
                    $estadoEventoSeleccionado = "pending";
                    break;
                case 3:
                    $estadoEventoSeleccionado = "processing";
                    break;
                case 4:
                    $estadoEventoSeleccionado = "on-hold";
                    break;
                case 5:
                    $estadoEventoSeleccionado = "completed";
                    break;
                default:
                    $estadoEventoSeleccionado = "";
                    break;
            }
            $this->eventoParaFacturar = $estadoEventoSeleccionado;
            $this->facturarAutomatico = true;
        }
    }
    private function getModoSandbox()
    {
        $sandbox = get_option('wc_settings_tab_woo_ifactura_testmode');
        if (!$sandbox || $sandbox == 'no')
        {
            $this->modoDemo = false;
        }
        else
        {
            $this->modoDemo = true;
        }
    }
    /**
     * Método para recuperar la URL correcta para descargar la invoice
     * @return String
     */
    public function getURLApuntar()
    {
        $url = 'https://app.ifactura.com.ar/Factura/ImprimirExterno';
        if ($this->modoDemo)
        {
            $url = 'https://demo.ifactura.com.ar/Factura/ImprimirExterno';
        }
        return $url;
    }
}