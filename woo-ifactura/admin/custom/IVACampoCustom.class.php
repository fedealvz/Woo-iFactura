<?php

class IVACampoCustom
{
    public function getField()
    {
        $fieds = array(            
            'IVA' => array(
                'name' => __('IVA', 'woo-ifactura'),
                'type' => 'select',
                'desc' => __('IVA %', 'woo-ifactura'),
                'id'   => 'wc_settings_tab_woo_ifactura_IVA',
                'default' => '21',
                'options' => array(
                      '21' => '21 %',
                      '10,5' => '10,5 %',
                      '27' => '27 %',
                      '2,5' => '2,5 %',
                      '5' => '5 %',
                      '0' => '0 %'
                 )
            )
        );
    }
    public function iva_field()
    {
        global $post;
        $product = wc_get_product($post->ID);
        $value = $product->get_meta('IVA');
        if ($value == "") {
            $value = '1'; //IVA 0% POR DEFECTO
        }
        $select_field = array(
          'name' => __('IVA', 'woo-ifactura'),
          'label' => __('IVA iFactura', 'Se adicionara este porcentaje a la factura'),
          'type' => 'select',
          'desc' => __('IVA %', 'woo-ifactura'),
          'id'   => 'wc_settings_tab_woo_ifactura_IVA',
          'value' => $value,
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
        woocommerce_wp_select($select_field);
    } 
    public function save_iva_field($post_id)
    {
        $custom_field_value = isset($_POST['IVA']) ? sanitize_text_field($_POST['IVA']) : '';    
        $product = wc_get_product($post_id);
        $product->update_meta_data('IVA', $custom_field_value);
        $product->save();
    }
}
