<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.ifactura.com.ar
 * @since      0.1
 *
 * @package    woo-ifactura
 * @subpackage woo-ifactura/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    woo-ifactura
 * @subpackage woo-ifactura/admin
 * @author     Federico Alvarez
 */
 
/*
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
*/

class Woo_iFactura_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    0.0.1
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    0.0.1
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.0.1
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    private $soap_url;
    
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    0.0.1
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-ifactura-admin.css', array(), $this->version, 'all');
    }
    /**
     * Register the JavaScript for the admin area.
     *
     * @since    0.0.1
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-ifactura-admin.js', array( 'jquery' ), $this->version, false);
        wp_localize_script($this->plugin_name, 'fyifacturaAdminVars', array('ifacturaUrl' => plugin_dir_url(__FILE__) ));
    }
    
    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs['settings_tab_woo_ifactura'] = __('iFactura Settings', 'woo-ifactura');        
        return $settings_tabs;
    }
    
    /**
    * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
    *
    * @uses woocommerce_admin_fields()
    * @uses self::get_settings()
    */
    public static function settings_tab()
    {
        woocommerce_admin_fields(self::get_settings());
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings()
    {
        woocommerce_update_options(self::get_settings());
    }
    
    /**
    * Show warnings if taxes are ON.
    *
    **/
    public function woo_warning()
    {
        global $current_screen;
         
        if ($current_screen->base == 'woocommerce_page_wc-settings') {
            if ('yes'==get_option('woocommerce_calc_taxes')) {
                echo '<div class="notice notice-error"><p>Atención: El cálculo de impuestos debe estar desactivado para que Woo iFactura funcione correctamente en esta versión. </p></div>';
            }
        }
    }
    
    /**
     * Gets order id in WooCommerce 3.0 and older versions
     *
     * @since 0.0.3
     */
    
    public function get_order_id($order)
    {
        global $woocommerce;        
        if ($woocommerce->version >= '3.0') {
            $order_id = $order->get_id();
        } else {
            $order_id = $order->id;
        }        
        return $order_id;
    }    
    /**
    * Adds DNI to register form
    *
    */
    public static function woo_ifactura_add_dni_field_to_register()
    {
        ?>
			<p class="form-row form-row-first">
				<label for="woo_ifactura_billing_dni"><?php _e('DNI', 'woo-ifactura'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="billing_dni_ifactura" id="reg_billing_dni_ifactura" value="<?php if (! empty($_POST['billing_dni_ifactura'])) {
            esc_attr_e($_POST['billing_dni_ifactura']);
        } ?>" />
			</p>
			<?php
    }
    public static function woo_ifactura_add_condicionimpositiva_field_to_register()
    {
        if (! empty($_POST['billing_condicionimpositiva_ifactura'])) {
            $elegido = esc_attr_e($_POST['billing_condicionimpositiva_ifactura']);
        }
         ?>
			<p class="form-row form-row-first">
                <label for="woo_ifactura_billing_condicionimpositiva"><?php _e('Tax Treatment', 'woo-ifactura'); ?> <span class="required">*</span></label>
                <select name="billing_condicionimpositiva_ifactura" id="reg_billing_condicionimpositiva_ifactura">
                    <option value="1" <?php $elegido == 1 ? "selected" : "" ?>>Responsable Inscripto</option>
                    <option value="2" <?php $elegido == 2 ? "selected" : "" ?>>Exento</option>
                    <option value="3" <?php $elegido == 3 ? "selected" : "" ?>>Monotributo</option>
                    <option value="4" <?php $elegido == 4 ? "selected" : "" ?>>Consumidor Final</option>
                </select>				
			</p>
			<?php
    }
    public static function woo_ifactura_add_tipopersona_field_to_register()
    {
        if (! empty($_POST['billing_tipopersona_ifactura'])) {
            $elegido = esc_attr_e($_POST['billing_tipopersona_ifactura']);
        }
         ?>
			<p class="form-row form-row-first">
                <label for="woo_ifactura_billing_tipopersona"><?php _e('People Registry Type', 'woo-ifactura'); ?> <span class="required">*</span></label>
                <select name="billing_tipopersona_ifactura" id="reg_billing_tipopersona_ifactura">
                    <option value="1" <?php $elegido == 1 ? "selected" : "" ?>>Física</option>
                    <option value="2" <?php $elegido == 2 ? "selected" : "" ?>>Jurídica</option>
                </select>				
			</p>
			<?php
    }
    /**
    * Validates DNI and Condición Impositiva in register form
    *
    */
    public static function woo_ifactura_validate_extra_register_fields($errors, $username, $email)
    {
        if (isset($_POST['billing_dni_ifactura']) && empty($_POST['billing_dni_ifactura'])) {
            if (!preg_match('/^[0-9]*$/', $_POST['billing_dni_ifactura'])) {
                $errors->add('billing_dni_ifactura_error', __('<strong>Error</strong>: DNI must be a numeric value!.', 'woo-ifactura'));
            }
            
            $errors->add('billing_dni_ifactura_error', __('<strong>Error</strong>: DNI is required!.', 'woo-ifactura'));
        }
        if (isset($_POST['billing_condicionimpositiva_ifactura']) && empty($_POST['billing_condicionimpositiva_ifactura'])) {
            if ($_POST["billing_condicionimpositiva_ifactura"] > 4 || $_POST["billing_condicionimpositiva_ifactura"] <= 0) {
                $errors->add('billing_condicionimpositiva_ifactura_error', __('<strong>Error</strong>: Tax Treatment is invalid.', 'woo-ifactura'));
            }                    
            $errors->add('billing_condicionimpositiva_ifactura_error', __('<strong>Error</strong>: Tax Treatment is required.', 'woo-ifactura'));
        }
        if (isset($_POST['billing_tipopersona_ifactura']) && empty($_POST['billing_tipopersona_ifactura'])) {
            if ($_POST["billing_tipopersona_ifactura"] > 2 || $_POST["billing_tipopersona_ifactura"] <= 0) {
                $errors->add('billing_tipopersona_ifactura_error', __('<strong>Error</strong>: People Registry Type is invalid.', 'woo-ifactura'));
            }                            
            $errors->add('billing_tipopersona_ifactura_error', __('<strong>Error</strong>: People Registry Type is incorrect.', 'woo-ifactura'));
        }
        return $errors;
    }
    
    public static function woo_ifactura_add_allfields_to_my_account()
    {
        self::woo_ifactura_add_dni_field_to_my_account();
        self::woo_ifactura_add_condicionimpositiva_field_to_my_account();
        self::woo_ifactura_add_tipopersona_field_to_my_account();

    }
    /**
    * Adds DNI to my account form
    *
    */
    public static function woo_ifactura_add_dni_field_to_my_account()
    {
        $user_id = get_current_user_id();
            
        $user = get_userdata($user_id);
 
        if (!$user) {
            return;
        } ?>
			  <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide"> 
				  <label for="woo_ifactura_billing_dni"><?php _e('CUIT/CUIL/DNI', 'woo-ifactura'); ?> <span class="required">*</span></label> 
				  <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_dni_ifactura" id="ma_billing_dni_ifactura" value="<?php echo esc_attr($user->billing_dni_ifactura); ?>" /> 
			  </p> 
			<?php
    }
    /**
    * Adds Condición Impositiva to my account form
    *
    */
    public static function woo_ifactura_add_condicionimpositiva_field_to_my_account()
    {
        $user_id = get_current_user_id();
            
        $user = get_userdata($user_id);
 
        if (!$user) {
            return;
        } 
        if (! empty($_POST['billing_condicionimpositiva_ifactura'])) {
            $elegido = esc_attr($user->billing_condicionimpositiva_ifactura);
        }
         ?>
			<p class="form-row form-row-first">
                <label for="woo_ifactura_billing_condicionimpositiva"><?php _e('Tax Treatment', 'woo-ifactura'); ?> <span class="required">*</span></label>
                <select name="billing_condicionimpositiva_ifactura" id="ma_billing_condicionimpositiva_ifactura">
                    <option value="1" <?php $elegido == 1 ? "selected" : "" ?>>Responsable Inscripto</option>
                    <option value="2" <?php $elegido == 2 ? "selected" : "" ?>>Exento</option>
                    <option value="3" <?php $elegido == 3 ? "selected" : "" ?>>Monotributo</option>
                    <option value="4" <?php $elegido == 4 ? "selected" : "" ?>>Consumidor Final</option>
                </select>				
			</p>
			<?php
    }
    /**
    * Adds Tipo persona to my account form
    *
    */
    public static function woo_ifactura_add_tipopersona_field_to_my_account()
    {
        $user_id = get_current_user_id();
            
        $user = get_userdata($user_id);
 
        if (!$user) {
            return;
        } 
        if (! empty($_POST['billing_condicionimpositiva_ifactura'])) {
            $elegido = esc_attr($user->billing_tipopersona_ifactura);
        }
         ?>
			<p class="form-row form-row-first">
                <label for="woo_ifactura_billing_tipopersona"><?php _e('People Registry Type', 'woo-ifactura'); ?> <span class="required">*</span></label>
                <select name="billing_tipopersona_ifactura" id="ma_billing_tipopersona_ifactura">
                    <option value="1" <?php $elegido == 1 ? "selected" : "" ?>>Física</option>
                    <option value="2" <?php $elegido == 2 ? "selected" : "" ?>>Jurídica</option>
                </select>				
			</p>
			<?php
    }
    public function woo_ifactura_save_allfields($user_id)
    {
        $this->woo_ifactura_save_DNI($user_id);
        $this->woo_ifactura_save_condicionimpositiva($user_id);
        $this->woo_ifactura_save_tipopersona($user_id);

    }
    /**
    * Saves DNI in my account and register page
    *
    *
    */
    public function woo_ifactura_save_DNI($user_id)
    {
        if (isset($_POST[ 'billing_dni_ifactura' ])) {
            update_user_meta($user_id, 'billing_dni_ifactura', htmlentities($_POST[ 'billing_dni_ifactura' ]));
        }
    }
    /**
    * Saves Condición Impositiva in my account and register page
    *
    *
    */
    public function woo_ifactura_save_condicionimpositiva($user_id)
    {
        if (isset($_POST[ 'billing_condicionimpositiva_ifactura' ])) {
            update_user_meta($user_id, 'billing_condicionimpositiva_ifactura', htmlentities($_POST[ 'billing_condicionimpositiva_ifactura' ]));
        }
    }
    /**
    * Saves Condición Impositiva in my account and register page
    *
    *
    */
    public function woo_ifactura_save_tipopersona($user_id)
    {
        if (isset($_POST[ 'billing_tipopersona_ifactura' ])) {
            update_user_meta($user_id, 'billing_tipopersona_ifactura', htmlentities($_POST[ 'billing_tipopersona_ifactura' ]));
        }
    }
    /**
    * Add DNI and Condición Impositiva field to checkout form
    *
    */
    public static function woo_ifactura_dni_checkout_field($checkout_fields)
    {
        $user_meta  =  get_user_meta(get_current_user_id());
         
        $billing_dni =  $user_meta['billing_dni_ifactura']['0'];
        $condicionimpositiva = $user_meta['billing_condicionimpositiva_ifactura']['0'];
        $tipopersona =  $user_meta['billing_tipopersona_ifactura']['0'];

        $checkout_fields['billing']['billing_dni_ifactura']  =  array(
            'label'          => __('CUIT/CUIL/DNI', 'woocommerce'),
            'placeholder'    => _x('enter your dni', 'placeholder', 'woo-ifactura'),
            'required'       => true,
            'clear'          => false,
            'type'           => 'text',
            'class'          => array('form-row-wide'),
        );
        $opciones_condicion = array(
            "4" => "Consumidor Final",
            '1' => "Responsable Inscripto",
            "2" => "Exento",
            "3" => "Monotributo"           
        );
        $checkout_fields['billing']['billing_condicionimpositiva_ifactura'] = array(
            'label'          => __('Tax Treatment', 'woocommerce'),
            'required'       => true,
            'clear'          => false,
            'type'           => 'select',
            'options'       => $opciones_condicion,
            'class'          => array('form-row-wide'),
        );
        $opciones_tipopersona = array(
            "1" => "Física",
            '2' => "Jurídica",
        );
        $checkout_fields['billing']['billing_tipopersona_ifactura'] = array(
            'label'          => __('People Registry Type', 'woocommerce'),
            'required'       => true,
            'clear'          => false,
            'type'           => 'select',
            'options'       => $opciones_tipopersona,
            'class'          => array('form-row-wide'),
        );

        return $checkout_fields;
    }
    
    /**
    * Validates DNI field in checkout proccess
    *
    */
    public static function woo_ifactura_checkout_field_process()
    {
        if (! $_POST['billing_dni_ifactura'] ||  !preg_match('/^[0-9]*$/', $_POST['billing_dni_ifactura'])) {
            wc_add_notice(__('Your DNI is invalid.', 'woo-ifactura'), 'error');
        }
        if (! $_POST['billing_condicionimpositiva_ifactura'] || (intval($_POST['billing_condicionimpositiva_ifactura']) > 4) || (intval($_POST['billing_condicionimpositiva_ifactura']) <= 0)) {
            wc_add_notice(__('Tax Treatment is invalid.', 'woo-ifactura'), 'error');
        }
        if (! $_POST['billing_tipopersona_ifactura'] || (intval($_POST['billing_tipopersona_ifactura']) > 2) || (intval($_POST['billing_condicionimpositiva_ifactura']) <= 0)) {
            wc_add_notice(__('People Registry Type is incorrect.', 'woo-ifactura'), 'error');
        }

    }
    
    /**
    * Saves user DNI field to order
    *
    */
    public static function woo_ifactura_update_order_meta($order_id)
    {
        if (! empty($_POST['billing_dni_ifactura'])) {
            update_post_meta($order_id, 'DNI', sanitize_text_field($_POST['billing_dni_ifactura']));
        }
        if (! empty($_POST['billing_condicionimpositiva_ifactura'])) {
            update_post_meta($order_id, 'condicionimpositiva', sanitize_text_field($_POST['billing_condicionimpositiva_ifactura']));
        }
        if (! empty($_POST['billing_tipopersona_ifactura'])) {
            update_post_meta($order_id, 'tipopersona', sanitize_text_field($_POST['billing_tipopersona_ifactura']));
        }

    }
    
    /*
    * Shows customer DNI in order page
    *
    */
    public function woo_ifactura_display_admin_order_meta($order)
    {
        echo '<p><strong>'.__('DNI',"woo-ifactura").':</strong> ' . get_post_meta($this->get_order_id($order), 'DNI', true) . '</p>
            <p><strong>'.__('Tax Treatment',"woo-ifactura").':</strong> ' . $this->woo_ifactura_gettipocondicionimpositiva(get_post_meta($this->get_order_id($order), 'condicionimpositiva', true)) . '</p>
            <p><strong>'.__('People Registry Type',"woo-ifactura").':</strong> ' . $this->woo_ifactura_gettipopersona(get_post_meta($this->get_order_id($order), 'tipopersona', true)) . '</p>';
    }    
    /*
    * Shows customer DNI in email
    *
    *
    */    
    public static function woo_ifactura_display_dni_in_email_fields($keys)
    {
        $keys['DNI'] = 'DNI';        
        return $keys;
    }
    public static function woo_ifactura_display_condicionimpositiva_in_email_fields($keys)
    {
        $keys['condicionimpositiva'] = 'condicionimpositiva';        
        return $keys;
    }
    public static function woo_ifactura_display_tipopersona_in_email_fields($keys)
    {
        $keys['tipopersona'] = 'tipopersona';        
        return $keys;
    }
    /*
    * Add iFactura metabox to the order screen
    *
    */
    public function woo_ifactura_add_metaboxes()
    {
        add_meta_box('ifactura_metabox', __('ifactura', 'woo-ifactura'), array( $this, 'woo_ifactura_order_buttons' ), 'shop_order', 'side', 'core');
    }
    
    /*
    * Add iFactura buttons to order metabox
    *
    */
    public function woo_ifactura_order_buttons()
    {
        global $post;
        
        $the_order = new WC_Order($post->ID);
        
        
        if (! $the_order->has_status(array( 'cancelled' )) && ($the_order->has_status(array( 'processing' )) || $the_order->has_status(array( 'completed' )))) {
            $invoice_id = get_post_meta($this->get_order_id($the_order), '_invoice_id', true);
            
            $estado_ifactura = get_post_meta($this->get_order_id($the_order), '_estado_ifactura', true);
            
        
            if ($invoice_id=='') {
                do_action('before_invoice_button', $args=array()); ?>
				<p>
					<a class="button fy-invoice-button"  title="<?php _e('create invoice', 'woo-ifactura') ?>"><?php __('Invoice', 'woo-ifactura') ?></a>
				</p>
				<?php
            } else {
                ?>
				<p>
				<?php
                
                switch ($estado_ifactura) {                    
                    case 1:                    
                        /* 1: generación en progreso */
                        ?>						
							<a class="button fy-awaiting-button"  title="<?php _e('awaiting invoice', 'woo-ifactura') ?>"><?php __('awaiting invoice', 'woo-ifactura') ?></a>						
						<?php                        
                        break;                    
                    case 2:                        
                        /* 2: generada correctamente */
                        ?>						
							<a class="button fy-view-invoice-button"  title="<?php _e('view invoice', 'woo-ifactura') ?>"><?php __('view invoice', 'woo-ifactura') ?></a>							
						<?php                        
                        break;                    
                    default:                    
                        /* 3: error.  */                        
                        ?>						
							<a class="button fy-invoice-button"  title="<?php _e('create invoice', 'woo-ifactura') ?>"><?php __('Invoice', 'woo-ifactura') ?></a>						
						<?php                        
                        break;               
                } ?>
				</p>
				<?php
            }
        } else {
            echo 'Podrás facturar cuando el pedido esté en estado "Procesando".';
        }
    }    
    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings()
    {
        $settings = array(
            'section_title' => array(
                'name'     => __('Input parameters', 'woo-ifactura'),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_settings_tab_woo_ifactura_section_title'
            ),
            'testmode' => array(
                'name' => __('Activate test mode', 'woo-ifactura'),
                'type' => 'checkbox',
                'desc' => __('Activate sandbox mode.', 'woo-ifactura'),
                'id'   => 'wc_settings_tab_woo_ifactura_testmode'
            ),
            'user' => array(
                'name' => __('User', 'woo-ifactura'),
                'type' => 'text',
                'desc' => 'Usuario provisto por <a href="https://www.ifactura.com.ar" target="_blank">ifactura.com.ar</a>',
                'id'   => 'wc_settings_tab_woo_ifactura_user',
                'custom_attributes' =>array('required'=>'required'),
            ),
            'hash' => array(
                'name' => __('Hash', 'woo-ifactura'),
                'type' => 'password',
                'desc' => 'Clave provista por <a href="https://www.ifactura.com.ar" target="_blank">ifactura.com.ar</a>',
                'id'   => 'wc_settings_tab_woo_ifactura_hash',
                'custom_attributes' =>array('required'=>'required'),
            ),
            'prefix' => array(
                'name' => __('Point of sale', 'woo-ifactura'),
                'type' => 'text',
                'desc' => __('Point of sale for invoices', 'woo-ifactura'),
                'id'   => 'wc_settings_tab_woo_ifactura_prefix'
            ),
            
            'impositive_treatment' => array(
                'name' => __('Tax treatment', 'woo-ifactura'),
                'type' => 'select',
                'desc' => '',
                'id'   => 'wc_settings_tab_woo_ifactura_impositive_treatment',
                'default' => '3',
                'options' => array(
                      '1' => "Responsable Inscripto",
                      '2' => "Exento",
                      "3" => "Monotributo"
                 )
            ),
            'autoenviocorreo' => array(
                'name' => __('Autosend invoice', 'woo-ifactura'),
                'type' => 'checkbox',
                'desc' => __('Autosend desc', 'woo-ifactura'),
                'id'   => 'wc_settings_tab_woo_ifactura_autoenvio'
            ),
            'sectionend' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_settings_tab_woo_ifactura_section_title'
            ),
            'section_contacto' => array(
                'name'     => __('Contact', 'woo-ifactura'),
                'type'     => 'title',
                'desc' => 'Soporte del Plugin: <a href="https://github.com/fedealvz/Woo-iFactura">https://github.com/fedealvz/Woo-iFactura</a> <br>Web iFactura: <a href="https://www.ifactura.com.ar" target="_blank">https://www.ifactura.com.ar</a>',
                'id'       => 'wc_settings_tab_woo_ifactura_section_contacto'
            ),
            'sectionend_contacto' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_settings_tab_woo_ifactura_section_contacto'
            ),
        );
        return apply_filters('wc_settings_tab_woo_ifactura', $settings);
    }    
    /**
    * Botones para las acciones
    */    
    public function woo_ifactura_order_actions($actions, $the_order)
    {
        if (!$the_order->has_status(array( 'cancelled' )) && ($the_order->has_status(array( 'processing' )) || $the_order->has_status(array( 'completed' )))) {
            $invoice_id = get_post_meta($this->get_order_id($the_order), '_invoice_id', true);            
            $estado_ifactura = get_post_meta($this->get_order_id($the_order), '_estado_ifactura', true);        
            if ($invoice_id=='') {
                if (!is_plugin_active('woo-ifactura-exportacion/woo-ifactura-exportacion.php')):                
                    $actions['invoice'] = array(                        
                        'url'       => '#',                        
                        'name'      => __('Invoice', 'woo-ifactura'),                        
                        'action'    => "fy-invoice-button",                    
                    );                
                endif;
            } else {
                switch ($estado_ifactura) {                    
                    case 1:                    
                        /* 1: procesando invoice */
                        $actions['awaiting_invoice'] = array(                    
                                'url'       => '',                                
                                'name'      => __('awaiting invoice', 'woo-ifactura'),                                
                                'action'    => "fy-awaiting-button",                            
                        );                        
                        break;                    
                    case 2:                        
                        /* 2: invoice lista */
                        $actions['view_invoice'] = array(                
                            'url'       => '#',                            
                            'name'      => __('view invoice', 'woo-ifactura'),                            
                            'action'    => "fy-view-invoice-button",                        
                        );                        
                        break;                    
                    default:                  
                        /* 3: error */                      
                        break;                
                }
            }
        }        
        return $actions;    }
    
    public function woo_item_price($impositive_treatment, $price, $iva='')
    {
        if ($iva=='') {
            $iva=21;
        }        
        $divisor = ($iva/100) + 1;        
        if (in_array($impositive_treatment, array(3,2,5))) {
            $price = $price/$divisor;
        }        
        return $price;
    }    
    /**
    * Procesar la petición AJAX para generar el comprobante.
    *
    **/    
    public function woo_ifactura_invoice()
    {
        //error_reporting(E_ALL);
        //ini_set("display_errors", 1);
        global $woocommerce;           
        $order_id = intval($_POST["order"]);        
        $order = wc_get_order($order_id);        
        $coupons = $order->get_used_coupons();        
        if ($woocommerce->version >= "3.0") {
            $discount = $order->discount_total;
        } else {
            $discount = $order->get_total_discount();
        }        
        $shipping_data = $order->get_items('shipping');
        $shipping_methods = array();        
        $total = 0;        
        $precio_sin_iva = 0;        
        //Condición impositiva
        $it = get_option('wc_settings_tab_woo_ifactura_impositive_treatment');        
        if (!$it) {
            //die("Sin Condición Impositiva");
            die(json_encode(array("Exito" => false, "Mensaje" => "Sin condición impositiva")));
        }        
        $option_taxes = get_option('woocommerce_calc_taxes');              
        /** Shipping **/        
        if (is_array($shipping_data)):        
            foreach ($shipping_data as $k=>$sm) {                
                /** TAXES shipping **/                
                //if( false !== get_option('wc_settings_tab_woo_ifactura_IVA') ){
                if (is_plugin_active('woo-ifactura-iva/woo-ifactura-iva.php')) {
                    if ($sm['total_tax']==0 && $option_taxes == 'no') {
                        $iva = get_option('wc_settings_tab_woo_ifactura_IVA');
                        
                        if ($woocommerce->version >= "3.0") {
                            $precio = $this->woo_item_price($it, $sm->get_total(), $iva);
                        } else {
                            $precio  = $this->woo_item_price($it, $sm['item_meta']['cost'][0], $iva);
                        }
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
                    }
                } else {
                    $iva = 21;
                    
                    if ($woocommerce->version >= "3.0") {
                        $precio = $this->woo_item_price($it, $sm->get_total(), $iva);
                    } else {
                        $precio  = $this->woo_item_price($it, $sm['item_meta']['cost'][0], $iva);
                    }
                }                
                
                if ($woocommerce->version >= "3.0") {
                    $shipping_name = $sm->get_name();
                } else {
                    $shipping_name = $sm['name'];
                }                
                $porcentaje_iva = $this->woo_ifactura_alicuotaiva($iva);
                $precio_iva = round((floatval($iva) * floatval($precio)) / (100 + floatval($iva)),2);
                array_push(
                    $shipping_methods,                
                    array(                    
                        'Bonificacion' => 0,                        
                        'Cantidad' => 1,                        
                        'Codigo' => '',                        
                        'Descripcion' => $shipping_name,                         
                        'AlicuotaIVA' => $porcentaje_iva,                    
                        'IVA' => $precio_iva,                        
                        'ValorUnitario' => floatval($precio),
                        'Total' => $precio                        
                    )                    
                );
            }            
        $total+=$precio;        
        endif;        
        $fees = $order->get_fees();        
        $order_fees = array();    
        if (is_array($fees)) {
            foreach ($fees as $k=>$v) {
                $iva = 21;
                $precio = $this->woo_item_price($it, $v->get_total());
                $porcentaje_iva = $this->woo_ifactura_alicuotaiva($iva);
                $precio_iva = round((floatval($iva) * floatval($precio)) / (100 + floatval($iva)), 2);
                array_push(
                    $order_fees,                
                    array(                    
                        'Bonificacion' => 0,                        
                        'Cantidad' => 1,                        
                        'Codigo' => '',                        
                        'Descripcion' => $v->get_name(),                        
                        'AlicuotaIVA' => $porcentaje_iva,
                        'IVA' => $precio_iva,                         
                        'ValorUnitario' => floatval($precio),
                        'Total' => $precio                         
                    )                    
                );
            }
        }        
        //DATOS DEL CLIENTE
        $order_meta = get_post_meta($order_id);        
        $items = $order->get_items();        
        $billing_currency = $order_meta["_order_currency"][0];        
        $billing_first_name = $order_meta["_billing_first_name"][0];        
        $billing_last_name = $order_meta["_billing_last_name"][0];        
        $billing_email = $order_meta["_billing_email"][0];        
        $billing_postcode = $order_meta["_billing_postcode"][0];        
        $payment_method = $order_meta["_payment_method"][0];        
        $billing_address_1 = $order_meta["_billing_address_1"][0];        
        $billing_address_2 = $order_meta["_billing_address_2"][0];        
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
        
        $time = new DateTime;        
        $today_atom = $time->format(DateTime::ATOM);        
        /** Products **/        
        //Bienes
        $Bienes = array();        
        foreach ($items as $k=>$item) {
            $product_id = $item['product_id'];            
            $item_quantity = $order->get_item_meta($k, '_qty', true);            
            $item_total = $order->get_item_meta($k, '_line_total', true);           
            
            if ($item['variation_id']>0) {
                $product_id = $item['variation_id'];
            }
            
            $product = wc_get_product($product_id);
            $price = $item_total/$item_quantity;            
            $sku = $product->get_sku();            
            if ($sku == '') {
                $sku = $product_id;
            }            
            /* TAXES para productos */            
            /* Si existe el plugin utiliza taxes, sino el IVA es de 21% siempre */
            if (is_plugin_active('woo-ifactura-iva/woo-ifactura-iva.php')) {
                $item_meta = $item->get_data();            
                if ($item_meta['total_tax']==0 && $option_taxes == 'no') {
                    $iva = get_option('wc_settings_tab_woo_ifactura_IVA');
                    
                    $precio = $this->woo_item_price($it, $price, $iva);
                } else {
                    $tax = new WC_Tax();                
                    $taxes = array_shift($tax->get_rates($item_meta['tax_class']));
                    
                    if ($item_meta['total_tax']==0) {
                        $iva = 0;
                    } else {
                        $iva = $taxes['rate'];
                    }                    
                    /* Cuando se utiliza los impuestos integrados no quito el IVA */                    
                    $precio = $price;
                }
            } else {
                $iva = 21;                
                $precio = $this->woo_item_price($it, $price, $iva);
            }            
            $porcentaje_iva = $this->woo_ifactura_alicuotaiva($iva);
            $iva_unitario = round((floatval($iva) * floatval($precio)  / (100 + floatval($iva))), 2);
            $precio_iva = round((floatval($iva) * (floatval($precio) * floatval($item_quantity)) / (100 + floatval($iva))), 2);
            array_push($Bienes, array(            
                'Bonificacion' => 0,                
                'Cantidad' => $item_quantity,                
                'Codigo' => $sku,                
                'Descripcion' => $item['name'],
                'AlicuotaIVA' => $porcentaje_iva,                    
                'IVA' => $precio_iva,                 
                'ValorUnitario' => floatval($price),
                'Total' => round(($precio - $iva_unitario)  * floatval($item_quantity),2)       
            ));            
            $total+= $item['qty'] * $precio;
        }
        //AGREGAR SHIPPING Y FEES
        if (is_array($shipping_methods) && count($shipping_methods)>0) {
            foreach ($shipping_methods as $k=>$v) {
                array_push($Bienes, $v);
            }
        }
        if (count($order_fees)>0) {
            foreach ($order_fees as $k=>$v) {
                array_push($Bienes, $v);
            }
        }
        
        
        $sandbox = get_option('wc_settings_tab_woo_ifactura_testmode');        
        if (!$sandbox || $sandbox == 'no') {
            $url = 'https://app.ifactura.com.ar/API/EmitirFactura';
        } else {
            $url = 'https://demo.ifactura.com.ar/API/EmitirFactura';
        }
        $razonsoc = $billing_first_name .  " " . $billing_last_name;
        if (!empty($billing_company))
        {
            $razonsoc = $billing_company;
        }
        $cliente->RazonSocial = $razonsoc;
        $cliente->Identificador = get_post_meta($order_id, 'DNI', true);
        $cliente->Email = $billing_email;
        $cliente->Direccion = $billing_address_1.' '.$billing_address_2;
        $cliente->Localidad = $billing_city;
        $cliente->CodigoPostal = $billing_postcode;
        $cliente->Provincia = $this->woo_ifactura_elegirprovincia(html_entity_decode($billing_state));
        $cliente->Provincia_str = html_entity_decode($billing_state);
        $cliente->CondicionImpositiva = get_post_meta($order_id, 'condicionimpositiva', true);
        $cliente->TipoPersona = get_post_meta($order_id, 'tipopersona', true);
        $cliente->TipoDocumento = $this->woo_ifactura_tipodocumento(get_post_meta($order_id, 'condicionimpositiva', true));
        $cliente->Actualizar = true;
        $factura->Numero = $order_id;       
        $autoenvio = get_option('wc_settings_tab_woo_ifactura_autoenvio');        
        if (!$autoenvio || $autoenvio == 'no') {
            $autoenvio = false;
        } else {
            $autoenvio = true;
        }
        $factura->Fecha = date("Y-m-d H:i:s");
        $factura->FechaVencimiento =  date("Y-m-d H:i:s",strtotime("+10 day"));
        $factura->FechaDesde = date("Y-m-d H:i:s");
        $factura->FechaHasta = date("Y-m-d H:i:s", strtotime("+10 day"));
        $factura->AutoEnvioCorreo = $autoenvio;
        $factura->PuntoVenta =  intval(get_option('wc_settings_tab_woo_ifactura_prefix'));
        $factura->FormaPago = 7; // HARDCODEADO A OTROS
        $factura->CondicionImpositiva = get_option('wc_settings_tab_woo_ifactura_impositive_treatment');
        $factura->TipoComprobante = $this->woo_ifactura_elegirtipocomprobante(get_option('wc_settings_tab_woo_ifactura_impositive_treatment'), get_post_meta($order_id, 'condicionimpositiva', true));
        $factura->DetalleFactura = array();
        $i = 0;
        $totalfinal = 0;
        $iva = 0;
        foreach($Bienes as $linea)
        {
            $factura->DetalleFactura[$i]->Cantidad = intval($linea['Cantidad']);
            $factura->DetalleFactura[$i]->ValorUnitario =  floatval($linea['ValorUnitario']);
            $factura->DetalleFactura[$i]->Total =  floatval($linea['Total']);
            $factura->DetalleFactura[$i]->Descripcion =  $linea['Descripcion'];
            $factura->DetalleFactura[$i]->Codigo =  $linea['Codigo'];
            $factura->DetalleFactura[$i]->AlicuotaIVA = $linea['AlicuotaIVA'];
            $factura->DetalleFactura[$i]->UnidadMedida = 7;
            $factura->DetalleFactura[$i]->Bonificacion = $linea['Bonificacion'];
            $factura->DetalleFactura[$i]->IVA = floatval($linea['IVA']);            
            $factura->DetalleFactura[$i]->ConceptoFactura = 1; //PRODUCTOS           
            $totalfinal = $totalfinal + $linea['Total'];
            $iva = $iva + $linea['IVA'];
            $i = $i + 1;
        }
        $factura->Cliente = $cliente;
        $data->Email = get_option('wc_settings_tab_woo_ifactura_user');
        $data->Password =get_option('wc_settings_tab_woo_ifactura_hash');
        $data->Factura = $factura;
        try
        {
            //ARMAR JSON
            $data_string = json_encode($data);            
            //INICIAR CONEXION
            $ch = curl_init($url);            
            //CONFIGURAR CURL
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(

                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string))

            );            
            //EJECUTAR
            $resultcurl = curl_exec($ch);            
            //CERRAR
            curl_close($ch);                
            $decode = json_decode($resultcurl);
            if ($resultcurl != false) {
                if ($decode->Exito == true) {
                    update_post_meta($order_id, '_invoice_id', $decode->IdFactura);                
                    //Estados 1: en espera, 2: list0, 3: error.
                    update_post_meta($order_id, '_estado_ifactura', 2);
                    $decode->Mensaje = __('Successful generated.', 'woo-ifactura');
                }               
                else
                {
                    //$decode->Mensaje = $decode->Mensaje . " " . var_export($data);
                }
                die(json_encode($decode,JSON_PRETTY_PRINT));
            }
            else
            {
                //"No se pudo realizar la comunicación con iFactura. Intente nuevamente más tarde"
                die(json_encode(array("Exito" => false, "Mensaje" => __('Failed to comunicate.', 'woo-ifactura')),JSON_PRETTY_PRINT));
            }
        }
        catch(Exception $ex)
        {
            //"No se pudo realizar la comunicación con iFactura. Intente nuevamente más tarde. "
            die(json_encode(array("Exito" => false, "Mensaje" => __('Failed to comunicate.', 'woo-ifactura') . $ex->getMessage(),JSON_PRETTY_PRINT)));
        }
    }
    
    public function woo_ifactura_view_invoice()
    {
        $order_id = intval($_POST["order"]);        
        $invoice_id = get_post_meta($order_id, '_invoice_id', true);        
        $sandbox = get_option('wc_settings_tab_woo_ifactura_testmode');        
        if (!$sandbox || $sandbox == 'no') {
            $url = 'https://app.ifactura.com.ar/Factura/ImprimirExterno';
        } else {
            $url = 'https://demo.ifactura.com.ar/Factura/ImprimirExterno';
        }       
        try {
            if (!empty($invoice_id))
            {
                $response = array("Exito" => true, "URLPDF" => $url . "/$invoice_id");

            }
            else
            {
                $response = array("Exito" => false, "Mensaje" => "Comprobante no existe.");
            }                      
            echo json_encode($response);              
            exit;
        } catch (Exception $e) {
            echo json_encode(array("Exito" => false, "Mensaje" => $e->getMessage()));
        }
    }
    //FUNCIONES DEL SISTEMA
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
            return 1;
        }
    }
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
    public function woo_ifactura_elegirtipocomprobante($condicion_emisor,$condicion_cliente)
    {
        if ($condicion_emisor == 1) {
            if ($condicion_cliente == 1) {
                return 1; //FACTURA A
            } elseif ($condicion_cliente == 2) {
                return 4; //FACTURA B
            } elseif ($condicion_cliente == 3) {
                return 4; //FACTURA B
            } elseif ($condicion_cliente == 4) {
                return 4; //FACTURA B
            }
        } else {
            return 19; //FACTURA C
        }
    }
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
    public function woo_ifactura_gettipopersona($id)
    {
        switch ($id) {
            case '1':
                return "Física";
                break;
            case '2':
                return "Jurídica";
                break;
            default:
                return "Desconocido";
                # code...
                break;
        }
    }


}
