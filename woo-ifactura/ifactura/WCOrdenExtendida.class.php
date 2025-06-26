<?php

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
class WCOrdenExtendida
{
    /** @var Integer Id de la orden */
    public $order_id;
    /** @var String Hash de la factura emitida por iFactura */
    public $invoice_id;
    /** @var Integer Estado de la factura */
    public $estado_ifactura;
    /** @var String Hash de la factura de cancelación */
    public $cancelacionInvoice_id;
    /** @var Integer Estado de la factura de cancelacion */
    public $estado_cancelacion_ifactura;
    /** @var Integer Condición impositiva de la orden/cliente */
    public $condicionImpositiva;
    /** @var String Documento o identificador del cliente */    
    public $documentoIdentificador;
    /** @var Boolean Flag para detectar si se esta usando blocks de woocommecer para guardado de los campos custom */
    public $modoBlocksWoocommerce;
    //NOMBRE DE CAMPOS
    const invoiceidField = "_invoice_id";
    const estadoInvoiceField = "_estado_ifactura";
    const cancelacionInvoiceIdField = "_cancelacioninvoice_id";
    const estadoCancelacionInvoiceField = "_estado_cancelacion_ifactura";
    const campoDNIField = "DNI";
    const campoCondicionImpositivaField = "condicionimpositiva";
    public const campoDNICodeBlock = "wooifactura/dni";
    public const campoCondicionImpositivaCodeBlock = "wooifactura/tax-treatment";
    public function __construct($order_id)
    {
        $this->order_id = $order_id;
        $order = wc_get_order($order_id);
        //revisar si la clase checkout existe. Para compatibilidad con versiones de woocommerce anteriores a 8
        $this->modoBlocksWoocommerce = true;
        $checkout_fields = null;
        if (!class_exists(CheckoutFields::class)) {
            $this->modoBlocksWoocommerce = false; //si no existe la clase, no se esta usando blocks de woocommerce
        }
        else
        {
            $checkout_fields = Package::container()->get(CheckoutFields::class);
        }
        //CAMPOS DE LA ORDEN
        $this->invoice_id = $order ? $order->get_meta(self::invoiceidField) : '';
        $this->estado_ifactura = $order ? intval($order->get_meta(self::estadoInvoiceField)) : 0;
        $this->cancelacionInvoice_id = $order ? $order->get_meta(self::cancelacionInvoiceIdField) : '';
        $this->estado_cancelacion_ifactura = $order ? $order->get_meta(self::estadoCancelacionInvoiceField) : 0;
        $this->condicionImpositiva = "";
        $this->documentoIdentificador = "";
        if ($this->modoBlocksWoocommerce) {
            $this->condicionImpositiva = $order ? $checkout_fields->get_field_from_object(self::campoCondicionImpositivaCodeBlock, $order, 'contact') : '';
            //check si el dato esta guardado en el campo condicion impositiva en el formato anterior
            if (empty($this->condicionImpositiva)) {
                $this->condicionImpositiva = $order ? $order->get_meta(self::campoCondicionImpositivaField) : 0;
                $this->modoBlocksWoocommerce = false; //si se encuentra el dato en el formato anterior, no se esta usando blocks de woocommerce
            }
            $this->documentoIdentificador = $order ? $checkout_fields->get_field_from_object(self::campoDNICodeBlock, $order, 'contact') : '';
            //check si el dato esta guardado en el campo DNI en el formato anterior
            if (empty($this->documentoIdentificador)) {
                $this->documentoIdentificador = $order ? $order->get_meta(self::campoDNIField) : '';
                $this->modoBlocksWoocommerce = false; //si se encuentra el dato en el formato anterior, no se esta usando blocks de woocommerce        
            }
        }
        else {
            $this->condicionImpositiva = $order ? $order->get_meta(self::campoCondicionImpositivaField) : 0;
            $this->documentoIdentificador = $order ? $order->get_meta(self::campoDNIField) : '';
        }
        
    }
    private function ActualizarValorMeta($order_id, $campo, $valor)
    {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_meta_data($campo, $valor);
            $order->save();
            return true;
        }
        return false;
    }
    private function BorrarValorMeta($order_id, $campo)
    {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->delete_meta_data($campo);
            $order->save();
            return true;
        }
        return false;
    }
    public function GuardarInvoiceId($invoice_id)
    {
        $this->invoice_id = $invoice_id;
        return $this->ActualizarValorMeta($this->order_id,SELF::invoiceidField,$invoice_id);
    }
    public function GuardarEstadoInvoice($estado_invoice)
    {
        $this->estado_ifactura = $estado_invoice;
        return $this->ActualizarValorMeta($this->order_id,SELF::estadoInvoiceField,$estado_invoice);
    }
    public function GuardarInvoiceCancelacionId($invoice_id)
    {
        $this->cancelacionInvoice_id = $invoice_id;
        return $this->ActualizarValorMeta($this->order_id,SELF::cancelacionInvoiceIdField,$invoice_id);
    }
    public function GuardarEstadoCancelacionInvoice($estado_invoice)
    {
        $this->estado_cancelacion_ifactura = $estado_invoice;
        return $this->ActualizarValorMeta($this->order_id,SELF::estadoCancelacionInvoiceField,$estado_invoice);
    }
    public function guardarDNI($DNI)
    {
        $this->documentoIdentificador = $DNI;
        return $this->ActualizarValorMeta($this->order_id, SELF::campoDNIField, $DNI);
    }
    public function guardarCondicionImpositivaCliente($condicionImpositiva)
    {
        $this->condicionImpositiva = $condicionImpositiva;
        return $this->ActualizarValorMeta($this->order_id, SELF::campoCondicionImpositivaField, $condicionImpositiva);
    }
    public function BorrarInvoiceId()
    {
        $this->invoice_id = "";
        return $this->BorrarValorMeta($this->order_id,SELF::invoiceidField);
    }
    public function BorrarEstadoInvoice()
    {
        $this->estado_ifactura = 0;
        return $this->BorrarValorMeta($this->order_id,SELF::estadoInvoiceField);
    }
    public function  BorrarInvoiceCancelacionId()
    {
        $this->cancelacionInvoice_id = "";
        return $this->BorrarValorMeta($this->order_id,SELF::cancelacionInvoiceIdField);
    }
    private function getCondicionImpositiva()
    {

    }
    public function borrarTodo()
    {
        $this->BorrarInvoiceId();
        $this->BorrarEstadoInvoice();
        $this->BorrarInvoiceCancelacionId();
    }
    /**
     * Método para agregar una nota a una orden
     * @param String $nota
     */
    public function AgregarNota($nota)
    {
        $orden = wc_get_order($this->order_id);
        return $orden->add_order_note($nota);
    }
}