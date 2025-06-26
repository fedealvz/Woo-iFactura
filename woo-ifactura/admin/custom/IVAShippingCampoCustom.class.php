<?php

class IVAShippingCampoCustom
{
    public function add_extra_fields_in_shipping_methods()
    {
        $sm = WC()->shipping->get_shipping_methods();
        foreach ($sm as $key => $value) {
            add_filter('woocommerce_shipping_instance_form_fields_'.$key, array(&$this, 'set_iva_field'), 10, 2);
        }
    }

    public function set_iva_field($settings)
    {
        $counter = 0;
        $arr = array();
        foreach ($settings as $key => $value) {
            if ($key=='cost' && $counter==0) {
                $arr[$key] = $value;
                $arr['IVA'] = array(
                    'title' => 'IVA',
                    'name' => __('IVA', 'woo-ifactura'),
                    'label' => __('IVA iFactura', 'Se adicionara este porcentaje a la factura'),
                    'type' => 'select',
                    'desc' => __('IVA %', 'woo-ifactura'),
                    'id'   => 'wc_settings_tab_woo_ifactura_IVA',
                    'value' => 21,
                    'options' => array(
                            '1' => '0 %',
                            '3' => '21 %',
                            '2' => '10,5 %',
                            '4' => '27 %',
                            '6' => '2,5 %',
                            '5' => '5 %',
                            '7' => 'Exento',
                            '8' => 'No gravado'
                     )
                  );
                $counter++;
            } else {
                $arr[$key] = $value;
            }
        }
        return $arr;
    }
}
