<?php
require_once(__DIR__ . "/models/FacturaiFactura.class.php");
require_once(__DIR__ . "/models/NotaiFactura.class.php");
require_once(__DIR__ . "/models/CancelarFacturaRequest.class.php");
require_once(__DIR__ . "/models/CancelarFacturaResponse.class.php");
require_once(__DIR__ . "/models/EmitirFacturaRequest.class.php");
require_once(__DIR__ . "/models/EmitirFacturaResponse.class.php");
require_once(__DIR__ . "/ConfiguracioniFactura.class.php");
class ConectoriFactura
{
    /** @var Boolean */
    public $modoDebug;
    /** @var Boolean */
    public $modoDemo;
    /** @var ConfiguracioniFactura */
    public $configuracion;
    /** @var Integer Timeout para el CURL a iFactura. En segundos */
    private $timeoutCurl;
    /** @var String Useragent para que sea reconocido por iFactura */
    private $userAgent;
    public function __construct()
    {
        $this->modoDebug = false;
        $this->modoDemo = false;
        $this->configuracion = new ConfiguracioniFactura();
        $this->modoDemo = $this->configuracion->modoDemo;
        $this->modoDebug = $this->configuracion->modoDebug;
        $this->timeoutCurl = 50;
        $this->userAgent = "Woo-iFactura 2.0";
        if ($this->modoDebug)
        {
            //PARA PODER DEBUGEAR LOS WP-AJAX
            if (defined('XMLRPC_REQUEST') || defined('REST_REQUEST') || (defined('WP_INSTALLING') && WP_INSTALLING) || wp_doing_ajax()) {
                @ini_set('display_errors', 1);
            }
        }
    }
     /**
     * Método para generar los comprobantes en base a un order id
     * @param Integer $order_id Númeron de orden
     * @return EmitirFacturaResponse Mensaje de respuesta del procesamiento 
     */  
    public function woo_ifactura_procesarInvoice($order_id)
    {
        global $woocommerce;
        $totalShipping = 0.0;
        $order = wc_get_order($order_id);
        $ordenExtendida = new WCOrdenExtendida($order_id);
        if ($ordenExtendida->invoice_id > 0)
        {
            $respuesta = new EmitirFacturaResponse();
            $respuesta->Exito = false;
            $respuesta->Mensaje = "La orden ya contiene una factura emitida correctamente.";
            return $respuesta;
        }
        //DEPRECADO EL TEMA DE LOS CUPONES DE DESCUENTOS DESDE VERSIÓN 5
        $shipping_data = $order->get_items('shipping');
        $total = 0;
        //Condición impositiva
        $it = $this->configuracion->condicionImpositiva;
        if (!$it) {
            //die("Sin Condición Impositiva");
            $respuesta = new EmitirFacturaResponse();
            $respuesta->Exito = false;
            $respuesta->Mensaje = "Sin condición impositiva configurada.";
            return $respuesta;
        }
        $option_taxes = get_option('woocommerce_calc_taxes');
        $usoImpuestosWoo = count($order->get_taxes()) > 0 ? true : false;
        /** Shipping **/
        $shipping_methods = $this->procesarShipping($shipping_data,$total);
        $totalShipping = $total;
        //FEES
        $fees = $order->get_fees();
        $order_fees = $this->procesarFees($fees,$total);
        $items = $order->get_items();
        /** Products **/
        $Bienes = $this->procesarItems($items,$usoImpuestosWoo,$order,$total);
        //AGREGAR SHIPPING Y FEES
        $ignorarShipping = $this->configuracion->ignorarEnvio;
        //SI SE IGNORA EL SHIPPING NO SE CARGA EN EL ARRAY DE BIENES
        if (!$ignorarShipping)
        {
            if (is_array($shipping_methods) && count($shipping_methods)>0) {
                foreach ($shipping_methods as $k=>$v) {
                    array_push($Bienes, $v);
                }
            }
        }
        if (count($order_fees)>0) {
            foreach ($order_fees as $k=>$v) {
                array_push($Bienes, $v);
            }
        }
        try
        {
            $cliente = $this->armarCliente($order_id);
        }
        catch(\Exception $ex)
        {
            $respuesta = new EmitirFacturaResponse();
            $respuesta->Exito = false;
            $respuesta->Mensaje =  $ex->getMessage();
            return $respuesta;
        }        
        $factura = new FacturaiFactura();
        $factura->Numero = $order_id;
        //LAS FECHAS SE USAN LAS POR DEFECTO DEL CONSTRUCTOR DEL OBJETO
        $factura->AutoEnvioCorreo = $this->configuracion->autoEnvio;
        $factura->PuntoVenta =  $this->configuracion->puntoVenta;
        $factura->FormaPago = 7; // HARDCODEADO A OTROS
        $factura->TipoComprobante = $this->woo_ifactura_elegirtipocomprobante($this->configuracion->condicionImpositiva, $ordenExtendida->condicionImpositiva);
        $factura->DetalleFactura = array();
        $totalfinal = 0;
        $iva = 0;
        $alicuotaDetectadaIVA = array();
        $alicuotaDetectadaTotal = array();
        $valorNegativosDetectados = false;
        //SI SE IGNORA EL SHIPPING NO ESTARAN ACA LAS LINEAS DE SHIPPING
        foreach($Bienes as $linea)
        {
            if (floatval($linea['Total']) < 0)
            {
                $valorNegativosDetectados = true;
            }
            $alicuotaDetectada = $linea['AlicuotaIVA'];
            $totalfinal = $totalfinal + floatval($linea['Total']);
            //POR SI EL INDICE NO EXISTE
            if (key_exists($alicuotaDetectada,$alicuotaDetectadaTotal))
            {
                $alicuotaDetectadaTotal[$alicuotaDetectada] = floatval($alicuotaDetectadaTotal[$alicuotaDetectada]) + floatval($linea['Total']);
            }
            else
            {
                $alicuotaDetectadaTotal[$alicuotaDetectada] = floatval($linea['Total']);
            }            
        }
        $agruparVenta = $this->configuracion->agruparVenta;
        if ($agruparVenta)
        {
            //HACIENDO TRUE ESTA VARIABLE SE PROCESE A AGRUPAR TODOS LOS ELEMENTOS EN UNA SOLA LINEA DE DESCRIPCIÓN
            $valorNegativosDetectados = true;
        }
        if ($valorNegativosDetectados)
        {
            if (count($alicuotaDetectadaTotal) >= 2)
            {
                $respuesta = new EmitirFacturaResponse();
                $respuesta->Exito = false;
                if($agruparVenta)
                {
                    $respuesta->Mensaje = "No se puede agrupar los elementos de la orden porque hay más de un porcentaje de IVA detectado.";
                    //return array("Exito" => false, "Mensaje" => "No se puede agrupar los elementos de la orden porque hay más de un porcentaje de IVA detectado.");
                }
                else
                {
                    $respuesta->Mensaje = "No se puede calcular el descuento global sobre productos con distintos porcentajes de IVA. Verificar todos los items de la orden que porcentaje de impuestos tienen. En caso de items negativos el monto de descuento sobre los impuestos debe ser negativo tambien.";
                    //return array("Exito" => false, "Mensaje" => "No se puede calcular el descuento global sobre productos con distintos porcentajes de IVA. Verificar todos los items de la orden que porcentaje de impuestos tienen. En caso de items negativos el monto de descuento sobre los impuestos debe ser negativo tambien.");
                }                
                return $respuesta;
            }
            else
            {
                $tipoIVA = max(array_keys($alicuotaDetectadaTotal));
                $porcentajeIVA = $this->woo_ifactura_porcentajeIVA($tipoIVA);
                $valorIVA = round($totalfinal * ($porcentajeIVA / 100),2);
                $iva = $valorIVA;
                $DetalleFactura = new DetalleFacturaiFactura();
                $DetalleFactura->Cantidad = 1;
                $DetalleFactura->ValorUnitario = round($totalfinal,2);
                $DetalleFactura->Total = round($totalfinal,2);
                $DetalleFactura->Descripcion =  "Segun venta " . $order_id;
                $DetalleFactura->Codigo =  "";
                $DetalleFactura->AlicuotaIVA = $tipoIVA;
                $DetalleFactura->UnidadMedida = 7;
                $DetalleFactura->Bonificacion = 0.0;
                $DetalleFactura->IVA = round($valorIVA, 2);
                $DetalleFactura->ConceptoFactura = 1; //PRODUCTOS
                array_push($factura->DetalleFactura,$DetalleFactura);
                $gettotals = floatval($order->get_total());
                $totalFactura = floatval(($iva + $totalfinal));
                if (!$ignorarShipping)
                {                    
                    //PARA COMPROBAR QUE LOS TOTALES COINCIDAN AL MENOS QUE SE FUERCE A AGRUPAR LOS DETALLES. SI SE AGRUPA EL DETALLE PUEDE SER QUE SE IGNORE EL ENVIO
                    //SI LA DIFERENCIA ENTRE EL TOTAL DE LA ORDEN CON RESPECTO AL VALOR CALCULADO ES MAYOR O IGUAL A 1 ERROR
                    if (abs($gettotals - $totalFactura) >= 1) {
                        $respuesta = new EmitirFacturaResponse();
                        $respuesta->Exito = false;
                        if ($agruparVenta)
                        {
                            $respuesta->Mensaje = "No se puede agrupar los elementos de la orden porque no los totales calculados no coinciden. Puede ser que haya elementos sin IVA y otros con IVA.";
                            //return array("Exito" => false, "Mensaje" => "No se puede agrupar los elementos de la orden porque no los totales calculados no coinciden. Puede ser que haya elementos sin IVA y otros con IVA.");
                        }
                        else
                        {
                            $respuesta->Mensaje = "No se puede calcular el descuento global sobre los productos dado que el descuento no esta aplicado sobre los impuestos tambien. Por ende no se puede calcular el IVA verdadero.";
                            //return array("Exito" => false, "Mensaje" => "No se puede calcular el descuento global sobre los productos dado que el descuento no esta aplicado sobre los impuestos tambien. Por ende no se puede calcular el IVA verdadero.");
                        }    
                        return $respuesta;                    
                    }
                }   
                else
                {
                    //PARA COMPROBAR QUE LOS TOTALES COINCIDAN AL MENOS QUE SE FUERCE A AGRUPAR LOS DETALLES. SI SE AGRUPA EL DETALLE PUEDE SER QUE SE IGNORE EL ENVIO
                    //SI LA DIFERENCIA ENTRE EL TOTAL DE LA ORDEN CON RESPECTO AL VALOR CALCULADO ES MAYOR O IGUAL A 1 ERROR
                    //SE LE RESTA EL TOTAL DE SHIPPING YA QUE SE IGNORA EL MISMO
                    if (abs(($gettotals - $totalShipping) - $totalFactura) >= 1) {
                        $respuesta = new EmitirFacturaResponse();
                        $respuesta->Exito = false;
                        if ($agruparVenta)
                        {
                            $respuesta->Mensaje = "No se puede agrupar los elementos de la orden porque no los totales calculados no coinciden. Puede ser que haya elementos sin IVA y otros con IVA.";
                            //return array("Exito" => false, "Mensaje" => "No se puede agrupar los elementos de la orden porque no los totales calculados no coinciden. Puede ser que haya elementos sin IVA y otros con IVA.");
                        }
                        else
                        {
                            $respuesta->Mensaje = "No se puede calcular el descuento global sobre los productos dado que el descuento no esta aplicado sobre los impuestos tambien. Por ende no se puede calcular el IVA verdadero.";
                            //return array("Exito" => false, "Mensaje" => "No se puede calcular el descuento global sobre los productos dado que el descuento no esta aplicado sobre los impuestos tambien. Por ende no se puede calcular el IVA verdadero.");
                        }        
                        return $respuesta;                
                    }
                }            
            }               
        }
        else
        {
            $totalfinal = 0;
            //NO SE REVISA EL AGRUPAR ELEMENTOS PORQUE AL ESTAR PUESTO AGRUPAR ELEMENTOS SE ENTRA EN LA CONDICIÓN DE VALORES NEGATIVOS
            foreach($Bienes as $linea)
            {
                $DetalleFactura = new DetalleFacturaiFactura();
                $DetalleFactura->Cantidad = intval($linea['Cantidad']);
                $DetalleFactura->ValorUnitario =  floatval($linea['ValorUnitario']);
                $DetalleFactura->Total =  floatval($linea['Total']);
                $DetalleFactura->Descripcion =  $linea['Descripcion'];
                $DetalleFactura->Codigo =  $linea['Codigo'];
                $DetalleFactura->AlicuotaIVA = $linea['AlicuotaIVA'];
                $DetalleFactura->UnidadMedida = 7;
                $DetalleFactura->Bonificacion = $linea['Bonificacion'];
                $DetalleFactura->IVA = floatval($linea['IVA']);            
                $DetalleFactura->ConceptoFactura = 1; //PRODUCTOS  
                array_push($factura->DetalleFactura, $DetalleFactura);         
                $totalfinal = $totalfinal + $linea['Total'];
                $iva = $iva + $linea['IVA'];
            }
        }
        $factura->Cliente = $cliente;
        $resultado = $this->enviarFactura($factura,$order_id);
        return $resultado;
    }
    private function procesarShipping($shipping_data,&$total)
    {
        global $woocommerce;
        $it = $this->configuracion->condicionImpositiva;
        $option_taxes = get_option('woocommerce_calc_taxes');
        $shipping_methods = array();
        if (is_array($shipping_data)){
            foreach ($shipping_data as $k=>$sm) {
                //OBTENER DATOS DE IVA PROPIEDAD CUSTOMIZADA A SHIPPING
                /*$method_id = $sm->get_method_id();
                $instance_id = $sm->get_instance_id();
                $shipping_settings = get_option('woocommerce_'.$method_id.'_'.$instance_id.'_settings');
                $id_iva_seleccionado = isset($shipping_settings['IVA'])?$shipping_settings['IVA']:1; //POR DEFECTO ID DE IVA 0%*/
                /** TAXES shipping **/
                $iva_total = 0;
                if ($it == 1) { //RESPONSABLE INSCRIPTO
                    if ($sm['total_tax']==0 && $option_taxes == 'no') {

                        $iva = 21;
                        if ($woocommerce->version >= "3.0") {
                            $precio = $sm->get_total();
                        } else {
                            $precio  = $sm['item_meta']['cost'][0];
                        }
                        $iva_total = round((floatval($iva) * floatval($precio)) / (100 + floatval($iva)), 2);
                        $total_linea = round($precio - $iva_total, 2);
                    } else {
                        if ($sm['total_tax']==0) {
                            $iva = 0;
                        } else {
                            $iva = (($sm['total_tax']*100)/$sm['total']);
                        }                    
                        if ($woocommerce->version >= "3.0") {
                            $precio = $sm->get_total();
                        } else {
                            $precio  = $sm['item_meta']['cost'][0];
                        }
                        $iva_total = round($sm['total_tax'], 2);
                        $total_linea = round(floatval($sm['total']) + $iva_total, 2);
                    }
                } else {
                    $iva = 0;
                    if ($woocommerce->version >= "3.0") {
                        $precio = $sm->get_total();
                    } else {
                        $precio  = $sm['item_meta']['cost'][0];
                    }
                    $total_linea = $precio;
                }
                if ($woocommerce->version >= "3.0") {
                    $shipping_name = $sm->get_name();
                } else {
                    $shipping_name = $sm['name'];
                }
                if (!empty(floatval($precio))) {
                    $porcentaje_iva = $this->woo_ifactura_alicuotaiva($iva);
                    array_push(
                        $shipping_methods,
                        array(
                            'Bonificacion' => 0,
                            'Cantidad' => 1,
                            'Codigo' => '',
                            'Descripcion' => $shipping_name,
                            'AlicuotaIVA' => $porcentaje_iva,
                            'IVA' => round($iva_total, 2),
                            'ValorUnitario' => round(floatval($precio), 2),
                            'Total' => round($total_linea, 2)
                        )
                    );
                    $total+=$precio;
                    $totalShipping = $total_linea;
                }
            }
        }
        return $shipping_methods;
    }
    private function procesarFees($fees,&$total)
    {
        global $woocommerce;
        $order_fees = array();
        if (is_array($fees)) {
            foreach ($fees as $k=>$v) {
                $precio = $v->get_total();
                if (!empty(floatval($precio)))
                {
                    $porcentaje_iva = $this->woo_ifactura_alicuotaiva(0);
                    $iva_total = 0;
                    array_push(
                        $order_fees,
                        array(
                            'Bonificacion' => 0,
                            'Cantidad' => 1,
                            'Codigo' => '',
                            'Descripcion' => $v->get_name(),
                            'AlicuotaIVA' => $porcentaje_iva,
                            'IVA' => $iva_total,                           
                            'ValorUnitario' => round(floatval($precio),2),
                            'Total' => round($precio - $iva_total,2)                          
                        )                    
                    );
                    $total+=$precio;
                }
            }
        }
        return $order_fees;
    }
    private function procesarItems($items,$usoImpuestosWoo,$order,&$total)
    {
        global $woocommerce;
        $it = $this->configuracion->condicionImpositiva;
        $option_taxes = get_option('woocommerce_calc_taxes');
        $Bienes = array();
        foreach ($items as $k=>$item) {
            //PARA RECUPERAR EL ID DE IVA DE LOS CAMPOS CUSTOM
            //$idIVA = ($product->get_meta('IVA'))!=''? intval($product->get_meta('IVA')):1; //POR DEFECTO IVA 0
            $product_id = $item['product_id'];
            $item_quantity = $order->get_item_meta($k, '_qty', true);
            $item_total = $order->get_item_meta($k, '_line_total', true);
            if ($item['variation_id']>0) {
                $product_id = $item['variation_id'];
            }
            $product = wc_get_product($product_id);
            $price = round(floatval($item_total/$item_quantity), 2);     //UNITARIO CON IVA
            $sku = $product->get_sku();
            if ($sku == '') {
                $sku = $product_id;
            }
            if (!empty(floatval($item_total))) {
                /* TAXES para productos */
                $iva_total = 0;
                //DETECCIÓN SI CALCULAR IVA O NO SEGÚN CONDICIÓN IMPOSITIVA CONFIGURADA
                if ($it == 1) { //RESPONSABLE INSCRIPTO
                    $item_meta = $item->get_data();
                    if ($item_meta['total_tax']==0 && $option_taxes == 'no') {
                        //NO TIENE LO IMPUESTOS CONFIGURADOS Y LO CONSIDERA QUE ESTAN INCLUIDOS EN EL VALOR FINAL
                        $iva = 21;
                        $iva_total = round((floatval($iva) * floatval($item_total)) / (100 + floatval($iva)), 2);
                        $total_linea = round($item_total - $iva_total, 2);
                    } else {
                        //LO TIENE POR SEPARADO DADO QUE TIENE CONFIGURADOS LOS IMPUESTOS
                        $tax = new WC_Tax();
                        $tax_rate = $tax->get_rates($item_meta['tax_class']);
                        $taxes = array_shift($tax_rate);
                        if ($item_meta['total_tax']==0) {
                            $iva = 0;
                        } else {
                            $iva = $taxes['rate'];
                        }
                        $iva_total = round($item_meta['total_tax'], 2);
                        $total_linea = round($item_total, 2);
                    }
                } else {
                    //SIN IMPUESTOS, MONOTRIBUTO O EXENTO
                    $iva_total = 0;
                    $iva = 0;
                    $total_linea = round($item_total - $iva_total, 2);
                }
                $porcentaje_iva = $this->woo_ifactura_alicuotaiva($iva);
                array_push($Bienes, array(
                    'Bonificacion' => 0,
                    'Cantidad' => $item_quantity,
                    'Codigo' => $sku,
                    'Descripcion' => $item['name'],
                    'AlicuotaIVA' => $porcentaje_iva,
                    'IVA' => round($iva_total, 2),
                    'ValorUnitario' => round(floatval($price), 2),
                    'Total' => $total_linea
                ));
                $total+= $item['qty'] * $item_total;
            }
        }
        return $Bienes;
    }
    /**
     * Método para enviar la Factura a iFactura
     * @param FacturaiFactura $factura Factura enviar
     * @param Integer $order_id Id de Orden de Woocommerce
     * @return CrearFacturaResponse
     */
    private function enviarFactura($factura,$order_id)
    {
        $url = 'https://app.ifactura.com.ar/API/EmitirFactura';
        if ($this->modoDemo) {
            $url = 'https://demo.ifactura.com.ar/API/EmitirFactura';
        }
        $data = new EmitirFacturaRequest();
        $data->Email = $this->configuracion->usuario;
        $data->Password =$this->configuracion->password;
        $data->Factura = $factura;
        try {
            //ARMAR JSON
            $data_string = json_encode($data);
            //INICIAR CONEXION
            $ch = curl_init($url);
            //CONFIGURAR CURL
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutCurl);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data_string))
                );
            //EJECUTAR
            $resultcurl = curl_exec($ch);
              if ($resultcurl === false)
            {
                $respuesta = new EmitirFacturaResponse();
                $respuesta->Exito = false;
                if ($this->configuracion->modoDebug)
                {
                    $respuesta->Mensaje = "Error en CURL de enviar factura: " . curl_error($ch);
                }
                else
                {
                    $respuesta->Mensaje = "No se pudo conectar con el servicio de iFactura. Intente nuevamente más tarde.";
                }
                return $respuesta;
            }
            //CERRAR
            curl_close($ch);
            $respuesta = new EmitirFacturaResponse($resultcurl);
            $ordenExtendida = new WCOrdenExtendida($order_id);
            //$decode = json_decode($resultcurl);
            if ($respuesta->conversionJSONResultado) {
                if ($respuesta->Exito == true) {
                    $ordenExtendida->GuardarInvoiceId($respuesta->IdFactura);
                    //update_post_meta($order_id, '_invoice_id', $respuesta->IdFactura);
                    //Estados 1: en espera, 2: list0, 3: error.
                    $ordenExtendida->GuardarEstadoInvoice(2);
                    //update_post_meta($order_id, '_estado_ifactura', 2);
                    $respuesta->Mensaje =  "Comprobante generado correctamente";
                } else {
                    if ($this->configuracion->modoDebug)
                    {
                        $respuesta->Mensaje = $respuesta->Mensaje . " Datos enviados: ". var_export($data,1);
                    }
                }
                
            } else {
                $respuesta->Exito = false;
                $respuesta->Mensaje = "La comunicación fallo con un mensaje incorrecto. Intente nuevamente más tarde.";
                //"No se pudo realizar la comunicación con iFactura. Intente nuevamente más tarde"
                //return array("Exito" => false, "Mensaje" => __('Failed to comunicate.', 'woo-ifactura'));
            }
            $ordenExtendida->AgregarNota("iFactura: " . $respuesta->Mensaje);
            return $respuesta;
        }
        catch(\Exception $ex)
        {
            $respuesta = new EmitirFacturaResponse();
            $respuesta->Exito = false;
            $respuesta->Mensaje = "Error de comunicación con iFactura: " . $ex->getMessage();
            return $respuesta;
        }
    }
     /**
     * Método para generar una nota de crédito que cancela la factura ya creada previamente
     * @param Integer $order_id Númeron de orden
     * @return CancelarFacturaResponse Mensaje de respuesta del procesamiento 
     */  
    public function woo_ifactura_cancelarInvoice($order_id)
    {
        $ordenExtendida = new WCOrdenExtendida($order_id);
        if ($ordenExtendida->cancelacionInvoice_id > 0)
        {
            $response = new CancelarFacturaResponse();
            $response->Exito = false;
            $response->Mensaje = "Ya se encuentrá emitda una nota de crédito.";
            return $response;
        }
        else
        {
            //VALIDAR QUE HAYA UNA FACTURA YA EMITIDA
            if (empty($ordenExtendida->invoice_id))
            {
                $response = new CancelarFacturaResponse();
                $response->Exito = false;
                $response->Mensaje = "No existe una factura ya emitida para la orden $order_id para ser cancelada.";
                return $response;
            }
            $nota = new NotaiFactura();
            $nota->HashFactura = $ordenExtendida->invoice_id;
            $nota->NotaCredito = true;
            $nota->AutoEnviarCorreo = $this->configuracion->autoEnvio;
            return $this->enviarNotaCredito($nota,$order_id);
        }
    }
     /**
     * Método para enviar la Factura a iFactura
     * @param NotaiFactura $nota Nota enviar
     * @param Integer $order_id Id de Orden de Woocommerce
     * @return CancelarFacturaResponse
     */
    private function enviarNotaCredito($nota,$order_id)
    {
        $url = 'https://app.ifactura.com.ar/API/EmitirNotadesdeFactura';
        if ($this->modoDemo) {
            $url = 'https://demo.ifactura.com.ar/API/EmitirNotadesdeFactura';
        }
        $data = new CancelarFacturaRequest();
        $data->Email = $this->configuracion->usuario;
        $data->Password =$this->configuracion->password;
        $data->Nota = $nota;
        try {
            //ARMAR JSON
            $data_string = json_encode($data);
            //INICIAR CONEXION
            $ch = curl_init($url);
            //CONFIGURAR CURL
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutCurl);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data_string))
                );
            //EJECUTAR
            $resultcurl = curl_exec($ch);
            if ($resultcurl === false)
            {
                $respuesta = new CancelarFacturaResponse();
                $respuesta->Exito = false;
                if ($this->configuracion->modoDebug)
                {
                    $respuesta->Mensaje = "Error en CURL de enviar nota de crédito: " . curl_error($ch);
                }
                else
                {
                    $respuesta->Mensaje = "No se pudo conectar con el servicio de iFactura. Intente nuevamente más tarde.";
                }
                return $respuesta;
            }
            //CERRAR
            curl_close($ch);
            $respuesta = new CancelarFacturaResponse($resultcurl);
            $ordenExtendida = new WCOrdenExtendida($order_id);
            //$decode = json_decode($resultcurl);
            if ($respuesta->conversionJSONResultado) {
                if ($respuesta->Exito == true) {
                    $ordenExtendida->GuardarInvoiceCancelacionId($respuesta->IdFactura);
                    //update_post_meta($order_id, '_invoice_id', $respuesta->IdFactura);
                    //Estados 1: en espera, 2: list0, 3: error.
                    $ordenExtendida->GuardarEstadoCancelacionInvoice(2);
                    //update_post_meta($order_id, '_estado_ifactura', 2);
                    $respuesta->Mensaje = __('Successful generated.', 'woo-ifactura');
                } else {
                    if ($this->configuracion->modoDebug)
                    {
                        $respuesta->Mensaje = $respuesta->Mensaje . " Datos enviados: ". var_export($data,1);
                    }
                }
                
            } else {
                $respuesta->Exito = false;
                $respuesta->Mensaje = __('Failed to comunicate.', 'woo-ifactura');
                //"No se pudo realizar la comunicación con iFactura. Intente nuevamente más tarde"
                //return array("Exito" => false, "Mensaje" => __('Failed to comunicate.', 'woo-ifactura'));
            }
            $ordenExtendida->AgregarNota("iFactura: " . $respuesta->Mensaje);
            return $respuesta;
        }
        catch(\Exception $ex)
        {
            $respuesta = new CancelarFacturaResponse();
            $respuesta->Exito = false;
            $respuesta->Mensaje = "Error de comunicación con iFactura: " . $ex->getMessage();
            return $respuesta;
        }
    }
    /**
     * Método para recuperar el ID de Alicuota de IVA en base al porcentaje de IVA
     * @param Double $porcentaje Porcentaje de impuestos aplicado
     * @return Integer ID de Alicuota IVA
     */
    public function woo_ifactura_alicuotaiva($porcentaje)
    {
        //SACADO DE TABLA DE SISTEMA
        $valor = floatval($porcentaje);
        if ($valor >= 27)
        {
            return 4;
        }
        elseif ($valor >= 21)
        {
            return 3;
        }
        elseif ($valor >= 10.5)
        {
            return 2;
        }
        elseif ($valor >= 5)
        {
            return 5;
        }
        elseif ($valor >= 2.5)
        {
            return 6;
        }
        else
        {
            if ($this->configuracion->condicionImpositiva === 1) //SOLO RESPONSABLE INSCRIPTO
            {
                if ($this->configuracion->considerarIVA0Como === 1)
                {
                    return 1; //0%
                }
                else if ($this->configuracion->considerarIVA0Como === 2)
                {
                    return 7; //EXENTO
                }
                else if ($this->configuracion->considerarIVA0Como === 3)
                {
                    return 8; //NO GRAVADO
                }
            }            
            return 1; //0% por defecto
        }
    }
    /**
     * Método para recuperar el porcentaje de IVA en base a su ID de Alicuota IVA
     * @param Integer $idIVA ID de Alicuota IVA
     * @return Double 0.0 es por defecto
     */
    public function woo_ifactura_porcentajeIVA($idIVA)
    {
        switch ($idIVA) {
            case 1:
                return 0.0;
                break;
            case 2:
                return 10.5;
                break;
            case 3:
                return 21.0;
                break;
            case 4:
                return 27.0;
                break;
            case 5:
                return 5.0;
                break;
            case 6:
                return 2.5;
                break;
            case 7: 
                return 0.0;
                break;
            case 8:
                return 0.0;
                break;
            default:
                return 0.0;
                break;
        }
    }
    /**
     * Método para recuperar el tipo de documento en base a su condición impositiva
     * @param Integer $condicionimpositiva ID de condición impositiva
     * @return Integer
     */
    public function woo_ifactura_tipodocumento($condicionimpositiva)
    {
        if ($condicionimpositiva > 0 && $condicionimpositiva <= 3)
        {
            return 1;
        }
        else
        {
            return 10;
        }
    }
    /**
     * Método para buscar el ID de provincia en base al nombre de la provincia
     * @param String $Provincia Nombre de la provincia
     * @return Integer
     */
    public function woo_ifactura_elegirprovincia($Provincia)
    {
        if ($Provincia == "Ciudad Autónoma de Buenos Aires") {
            return 1;
        } elseif ($Provincia == "Buenos Aires") {
            return 2;
        } elseif ($Provincia == "Catamarca") {
            return 3;
        } elseif ($Provincia == "Córdoba") {
            return 4;
        } elseif ($Provincia == "Corrientes") {
            return 5;
        } elseif ($Provincia == "Entre Ríos") {
            return 6;
        } elseif ($Provincia == "Jujuy") {
            return 7;
        } elseif ($Provincia == "Mendoza") {
            return 8;
        } elseif ($Provincia == "La Rioja") {
            return 9;
        } elseif ($Provincia == "Salta") {
            return 10;
        } elseif ($Provincia == "San Juan") {
            return 11;
        } elseif ($Provincia == "San Luis") {
            return 12;
        } elseif ($Provincia == "Santa Fe") {
            return 13;
        } elseif ($Provincia == "Santiago del Estero") {
            return 14;
        } elseif ($Provincia == "Tucumán") {
            return 15;
        } elseif ($Provincia == "Chaco") {
            return 16;
        } elseif ($Provincia == "Chubut") {
            return 17;
        } elseif ($Provincia == "Formosa") {
            return 18;
        } elseif ($Provincia == "Misiones") {
            return 19;
        } elseif ($Provincia == "Neuquén") {
            return 20;
        } elseif ($Provincia == "La Pampa") {
            return 21;
        } elseif ($Provincia == "Río Negro") {
            return 22;
        } elseif ($Provincia == "Santa Cruz") {
            return 23;
        } elseif ($Provincia == "Tierra del Fuego") {
            return 24;
        }        
    }
    /**
     * Método para recuperar el id de tipo de comprobante a ser emitido
     * @param Integer $condicion_emisor ID de Condición impositiva del emisor
     * @param Integer $condicion_cliente ID de Condición impositiva del receptor del comprobante
     * @return Integer ID del tipo de comprobante a emitir
     */
    public function woo_ifactura_elegirtipocomprobante($condicion_emisor,$condicion_cliente)
    {
        if ($condicion_emisor == 1) { //RESPONSABLE INSCRIPTO
            if ($condicion_cliente == 1) { //RESPONSABLE INSCRIPTO
                return 1; //FACTURA A
            } elseif ($condicion_cliente == 2) { //EXENTO
                return 4; //FACTURA B
            } elseif ($condicion_cliente == 3) { //MONOTRIBUTO
                return 4; //FACTURA B
            } elseif ($condicion_cliente == 4) { //CONSUMIDOR FINAL
                return 4; //FACTURA B
            }
        } else {
            return 19; //FACTURA C
        }
    }
    /**
     * Método para recuperar el Nombre del tipo de comprobante en base a su id de tipo de comprobante
     * @param Integer $idTipoComprobante ID de tipo de comprobante
     * @return String
     */
    public function getNombreTipoComprobante($idTipoComprobante)
    {
        $tipoComprobante = "Desconocido";
        switch (intval($idTipoComprobante)) {
            case 1:
                $tipoComprobante = "Factura A";
                break;
            case 2:
                $tipoComprobante = "Nota de Débito A";
                break;
            case 3:
                $tipoComprobante = "Nota de Crédito A";
                break;
            case 4:
                $tipoComprobante = "Factura B";
                break;
            case 5:
                $tipoComprobante = "Nota de Débito B";
                break;
            case 6:
                $tipoComprobante = "Nota de Crédito B";
                break;
            case 7:
                $tipoComprobante = "Recibo A";
                break;
            case 8:
                $tipoComprobante = "Recibo B";
                break;
            case 19:
                $tipoComprobante = "Factura C";
                break;
            case 20:
                $tipoComprobante = "Nota de Débito C";
                break;
            case 21:
                $tipoComprobante = "Nota de Crédito C";
                break;
            case 22:
                $tipoComprobante = "Recibo C";
                break;
            default:
                $tipoComprobante = "Desconocido";
                break;
        }
        return $tipoComprobante;
    }
    /**
     * Método para recuperar el nombre del tipo de comprobante de una orden
     * @param Integer $order_id ID de la orden
     * @return String
     */
    public function getNombreTipoComprobanteOrden($order_id)
    {
        $ordenExtendida = new WCOrdenExtendida($order_id);
        $condicionEmisor = $this->configuracion->condicionImpositiva;
        $condicionReceptor = $ordenExtendida->condicionImpositiva;
        $idTipoComprobante = $this->woo_ifactura_elegirtipocomprobante($condicionEmisor,$condicionReceptor);
        return $this->getNombreTipoComprobante($idTipoComprobante);
    }
    /**
     * Método para recuperar la condición impositiva para ser mostrada
     * @param Integer $id
     * @return String
     */
    public function woo_ifactura_gettipocondicionimpositiva($id)
    {
        switch ($id) {
            case '1':
                return "Responsable Inscripto";
                break;
            case '2':
                return "Exento";
                break;
            case '3':
                return "Monotributo";
                break;
            case '4':
                return "Consumidor Final";
                break;
            default:
                return "Desconocido";
                # code...
                break;
        }
    }
    /**
     * Método para recuperar el tipo de persona para ser mostrada
     * @param String $id Documento o Identificador del cliente
     * @return String
     */
    public function woo_ifactura_gettipopersona($id)
    {
        $dni_str = strval($id);
        if (strlen($dni_str) > 8)
        {
            $codigo_tipo = substr($dni_str,0,2);
            if ($codigo_tipo == "30" || $codigo_tipo == "33" || $codigo_tipo == "34" )
            {
                return "Jurídica";
            }
            else if($codigo_tipo == "20" || $codigo_tipo == "23" || $codigo_tipo == "24" || $codigo_tipo == "27")
            {
                return "Física";
            }
            else
            {
                return "Desconocido";
            }
        }
        else if ($dni_str > 0) {
            return "Física";
        }
        else
        {
            return "Desconocido";
        }
    }
    /**
     * Método para recuperar el ID de tipo de persona en base a un DNI o documento identificador
     * @param String $dni
     * @return Integer ID de tipo de persona. 1 Fisica 2 Juridica
     */
    public function woo_ifactura_gettipopersona_id($dni)
    {
        $dni_str = strval($dni);
        if (strlen($dni_str) > 8) {
            $codigo_tipo = substr($dni_str, 0, 2);
            if ($codigo_tipo == "30" || $codigo_tipo == "33" || $codigo_tipo == "34") {
                return 2;
            } elseif ($codigo_tipo == "20" || $codigo_tipo == "23" || $codigo_tipo == "24" || $codigo_tipo == "27") {
                return 1;
            } else {
                return 1;
            }
        } else {
            return 1;
        }
    }
    /**
     * Método para armar el objeto cliente
     * @param Integer $order_id Id de orden
     * @return ClienteiFactura
     * @throws Exception
     */
    protected function armarCliente($order_id)
    {
        global $woocommerce;
        $ordenExtendida = new WCOrdenExtendida($order_id);
        $order_meta = get_post_meta($order_id);
        $billing_currency = $order_meta["_order_currency"][0];
        $billing_first_name = $order_meta["_billing_first_name"][0];
        $billing_last_name = $order_meta["_billing_last_name"][0];
        $billing_email = $order_meta["_billing_email"][0];
        $billing_postcode = $order_meta["_billing_postcode"][0];
        $payment_method = $order_meta["_payment_method"][0];
        $billing_address_1 = $order_meta["_billing_address_1"][0];
        $billing_address_2 = "";
        //PORQUE PUEDE ESTAR VACIO O NULO
        if (key_exists("_billing_address_2",$order_meta))
        {
            $billing_address_2 = $order_meta["_billing_address_2"][0];
        }        
        $billing_city = $order_meta["_billing_city"][0];
        $billing_phone = $order_meta["_billing_phone"][0];
        $billing_company = $order_meta["_billing_company"][0];
        if (class_exists("WC_Countries")) {
            /* WC > 2.3.0 */
            $countries = new WC_Countries();
            $states = $countries->get_states("AR");
            $billing_state = $states[$order_meta["_billing_state"][0]]; // SOLO ARGENTINA
        } else {
            global $states;
            $billing_state = $states["AR"][$order_meta["_billing_state"][0]]; // SOLO ARGENTINA
        }
        $customer_user = $order_meta["_customer_user"][0];
        /*Patch for DOS61*/
        if (isset($order_meta['_billing_street'])) {
            $billing_address_1 = $order_meta['_billing_street'][0].' '.$order_meta['_billing_number'][0];
            $billing_address_2 = $order_meta['_billing_floor'][0].' '.$order_meta['_billing_apartment'][0];
        }
        $cliente = new ClienteiFactura();
        $razonsoc = $billing_first_name .  " " . $billing_last_name;
        if (!empty($billing_company)) {
            $razonsoc = $billing_company;
        }
        $cliente->RazonSocial = $razonsoc;
        $cliente->Identificador = $ordenExtendida->documentoIdentificador;
        if (empty($cliente->Identificador)) {
            throw new \Exception(__('Your DNI is invalid.', 'woo-ifactura'));
        }
        $cliente->Email = $billing_email;
        $cliente->Direccion = $billing_address_1.' '.$billing_address_2;
        $cliente->Localidad = $billing_city;
        $cliente->CodigoPostal = $billing_postcode;
        $cliente->Provincia = $this->woo_ifactura_elegirprovincia(html_entity_decode($billing_state));
        $cliente->Provincia_str = html_entity_decode($billing_state);
        $cliente->CondicionImpositiva = $ordenExtendida->condicionImpositiva;
        if (empty($cliente->CondicionImpositiva)) {
            throw new \Exception(__('Tax Treatment is invalid.', 'woo-ifactura'));
        }
        $cliente->TipoPersona = $this->woo_ifactura_gettipopersona_id($ordenExtendida->documentoIdentificador);
        if (empty($cliente->TipoPersona)) {
            throw new \Exception(__('People Registry Type is incorrect.', 'woo-ifactura'));
        }
        $cliente->TipoDocumento = $this->woo_ifactura_tipodocumento($ordenExtendida->condicionImpositiva);
        if (empty($cliente->TipoDocumento)) {
            throw new \Exception(__('Tax Treatment is invalid.', 'woo-ifactura'));
        }
        $cliente->Actualizar = true;
        return $cliente;
    }
    /**
     * Método para recuperar la URL de una invoice generada
     * @param Integer $order_id Id de la orden
     * @return String Devuelve la url de la invoice o vacio si no tiene invoice
     */
    public function getUrlInvoiceGenerada($order_id)
    {
        $ordenExtendida = new WCOrdenExtendida($order_id);
        if (empty($ordenExtendida->invoice_id))
        {
            return "";
        }
        $url = $this->configuracion->getURLApuntar() . "/$ordenExtendida->invoice_id";
        return $url;
    }
    /**
     * Método para recuperar la URL de una invoice generada
     * @param Integer $order_id Id de la orden
     * @return String Devuelve la url de la invoice o vacio si no tiene invoice
     */
    public function getUrlNotaGenerada($order_id)
    {
        $ordenExtendida = new WCOrdenExtendida($order_id);
        if (empty($ordenExtendida->cancelacionInvoice_id))
        {
            return "";
        }
        $url = $this->configuracion->getURLApuntar() . "/$ordenExtendida->cancelacionInvoice_id";
        return $url;
    }
}