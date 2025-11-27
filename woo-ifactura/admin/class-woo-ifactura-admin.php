<?php
require_once(__DIR__ . "/../ifactura/ConectoriFactura.class.php");
require_once(__DIR__ . "/../ifactura/ConfiguracioniFactura.class.php");
require_once(__DIR__ . "/../ifactura/WCOrdenExtendida.class.php");

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.ifactura.com.ar
 * @since      2.0
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
     * @since    2.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    2.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    2.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-ifactura-admin.css', array(), $this->version, 'all');
    }
    /**
     * Register the JavaScript for the admin area.
     *
     * @since    2.0
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
    * Mostrar advertencia si se es Responsable inscrpto pero estan desactivados los impuestos
    *
    **/
    public function woo_warning()
    {
        global $current_screen;
        $configuracioniFactura = new ConfiguracioniFactura();
        if ($current_screen->base == 'woocommerce_page_wc-settings') {
            if ('no'==get_option('woocommerce_calc_taxes') && $configuracioniFactura->condicionImpositiva == 1) {
                echo '<div class="notice notice-error"><p>Atención: Tu WooCommerce no tiene activado y configurado correctamente los impuestos para trabajar con IVA.</p></div>';
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
            $elegido = esc_attr($_POST['billing_condicionimpositiva_ifactura']);
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
        return $errors;
    }
    
    public static function woo_ifactura_add_allfields_to_my_account()
    {
        self::woo_ifactura_add_dni_field_to_my_account();
        self::woo_ifactura_add_condicionimpositiva_field_to_my_account();

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
    public function woo_ifactura_save_allfields($user_id)
    {
        $this->woo_ifactura_save_DNI($user_id);
        $this->woo_ifactura_save_condicionimpositiva($user_id);
    }
    /**
    * Saves DNI in my account and register page
    *
    *
    */
    public function woo_ifactura_save_DNI($user_id)
    {
        if (isset($_POST[ 'billing_dni_ifactura' ]) && preg_match('/^[0-9]*$/', $_POST['billing_dni_ifactura'])) {
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
    * Add DNI and Condición Impositiva field to checkout form
    *
    */
    public function woo_ifactura_dni_checkout_field($checkout_fields)
    {
        $user_meta  =  get_user_meta(get_current_user_id()); 
        $billing_dni = "";
        $condicionimpositiva = 4;
        if (!empty($user_meta))
        {
            $billing_dni =  $user_meta['billing_dni_ifactura']['0'];
            $condicionimpositiva = $user_meta['billing_condicionimpositiva_ifactura']['0'];
        }   
        $checkout_fields['billing']['billing_dni_ifactura']  =  array(
            'label'          => __('CUIT/CUIL/DNI', 'woo-ifactura'),
            'placeholder'    => __('enter your dni', 'woo-ifactura'),
            'required'       => true,
            'clear'          => false,
            'type'           => 'text',
            'class'          => array('form-row-wide'),
        );
        $opciones_condicion = array(
            "4" => "Consumidor Final",
            "3" => "Monotributo",
            '1' => "Responsable Inscripto",
            "2" => "Exento"       
        );
        $checkout_fields['billing']['billing_condicionimpositiva_ifactura'] = array(
            'label'          => __('Tax Treatment', 'woo-ifactura'),
            'required'       => true,
            'clear'          => false,
            'type'           => 'select',
            'options'       => $opciones_condicion,
            'default'        => $condicionimpositiva,
            'class'          => array('form-row-wide'),
        );
        return $checkout_fields;
    }
    /**
     * @see https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/additional-checkout-fields/
     */
    public function woo_ifactura_dni_checkout_field_blocks()
    {
        //revisar si la clase checkout existe. Para compatibilidad con versiones de woocommerce anteriores a 8
        if (!class_exists(CheckoutFields::class)) {
            return;
        }
        $customer = wc()->customer; // Or new WC_Customer( $id )       
        $checkout_fields = Package::container()->get(CheckoutFields::class);
        $billing_dni = "";
        $condicionimpositiva = "";
        if (!empty($customer)) {
            $billing_dni = $checkout_fields->get_field_from_object(WCOrdenExtendida::campoDNICodeBlock, $customer, 'contact');
            $condicionimpositiva = $checkout_fields->get_field_from_object(WCOrdenExtendida::campoCondicionImpositivaCodeBlock, $customer, 'contact');
        }
        $campoDNI = array(
            'id'            => 'wooifactura/dni',
            'label'         => __('CUIT/CUIL/DNI', 'woo-ifactura'),
            'optionalLabel' => __('CUIT/CUIL/DNI', 'woo-ifactura'),
            'location'      => 'contact',
            'type'          => 'text',
            'default'       => $billing_dni,
            'required'      => true,
            'attributes'    => array(
                'autocomplete'     => 'dni',
                'aria-describedby' => 'some-element',
                'aria-label'       => 'custom aria label',
                'title'            => __('CUIT/CUIL/DNI', 'woo-ifactura'),
                'data-custom'      => 'custom data',
            ),
        );
        $campoCondicionImpositiva = array(
            'id'            => 'wooifactura/tax-treatment',
            'label'         => __('Tax Treatment', 'woo-ifactura'),
            'optionalLabel' => __('Tax Treatment', 'woo-ifactura'),
            'location'      => 'contact',
            'required'      => true,
            'type'          => 'select',
            'options'       => array(
               array("value" => "1", "label" => "Responsable Inscripto"),
               array("value" => "2", "label" => "Exento"),  
                array("value" => "3", "label" => "Monotributo"),
                array("value" => "4", "label" => "Consumidor Final")
),
            'default'       => $condicionimpositiva,
            'attributes'    => array(
                'autocomplete'     => 'tax-treatment',
                'aria-describedby' => 'some-element',
                'aria-label'       => 'custom aria label',
                'title'            => __('Tax Treatment', 'woo-ifactura'),
                'data-custom'      => 'custom data',
            ),
        );
        woocommerce_register_additional_checkout_field($campoDNI);
        woocommerce_register_additional_checkout_field($campoCondicionImpositiva);
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
    }
    /**
    * Saves user DNI field to order
    *
    */
    public static function woo_ifactura_update_order_meta($order_id)
    {
        $ordenExtendida = new WCOrdenExtendida($order_id);
        if (! empty($_POST['billing_dni_ifactura']) && preg_match('/^[0-9]*$/', $_POST['billing_dni_ifactura'])) {
            $ordenExtendida->guardarDNI(sanitize_text_field($_POST['billing_dni_ifactura']));
        }
        if (! empty($_POST['billing_condicionimpositiva_ifactura'])) {
            $ordenExtendida->guardarCondicionImpositivaCliente(sanitize_text_field($_POST['billing_condicionimpositiva_ifactura']));
        }
    }
    /**
    * Shows customer DNI in order page
    *
    */
    public function woo_ifactura_display_admin_order_meta($order)
    {
        $conectoriFactura = new ConectoriFactura();
        $order_id = $this->get_order_id($order);
        $ordenExtendida = new WCOrdenExtendida($order_id);
        if ($ordenExtendida->modoBlocksWoocommerce) {
            echo '<p><strong>' . __('People Registry Type', "woo-ifactura") . ':</strong> ' . $conectoriFactura->woo_ifactura_gettipopersona($ordenExtendida->documentoIdentificador) . '</p>
                <p><strong>' . __('Invoice Type', "woo-ifactura") . ':</strong> ' . $conectoriFactura->getNombreTipoComprobanteOrden($order_id) . ' </p>';
        }
        else
        {
            echo '<p><strong>'.__('DNI',"woo-ifactura").':</strong> ' . $ordenExtendida->documentoIdentificador . '</p>
                <p><strong>'.__('Tax Treatment',"woo-ifactura").':</strong> ' . $conectoriFactura->woo_ifactura_gettipocondicionimpositiva($ordenExtendida->condicionImpositiva) . '</p>
                <p><strong>'.__('People Registry Type',"woo-ifactura").':</strong> ' . $conectoriFactura->woo_ifactura_gettipopersona($ordenExtendida->documentoIdentificador) . '</p>
                <p><strong>'.__('Invoice Type',"woo-ifactura").':</strong> ' . $conectoriFactura->getNombreTipoComprobanteOrden($order_id) .' </p>';
        }
    }    
    /**
    * Shows customer DNI in email
    *
    *
    */    
    public static function woo_ifactura_display_dni_in_email_fields($keys)
    {
        $keys["CUIT/CUIL/DNI"] = WCOrdenExtendida::campoDNIField;        
        return $keys;
    }
    public static function woo_ifactura_display_condicionimpositiva_in_email_fields($keys)
    {
        $keys["Condición Impositiva"] = WCOrdenExtendida::campoCondicionImpositivaField;       
        return $keys;
    }
    /**
    * Add iFactura metabox to the order screen
    *
    */
    public function woo_ifactura_add_metaboxes()
    {
        // ver en que screen se está
        // si se está usando Custom Orders Table, usar el screen_id de la tabla, sino
        $screen = wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';
        // Agregar el metabox de iFactura
        add_meta_box('ifactura_metabox', __('ifactura', 'woo-ifactura'), array( $this, 'woo_ifactura_order_buttons' ), $screen, 'side', 'high');
    }
    
    /**
    * Add iFactura buttons to order metabox
    *
    */
    public function woo_ifactura_order_buttons($post_or_order_object)
    {
        // PARA CHECKEAR SI ES UN PEDIDO O UN POST
        $order = ($post_or_order_object instanceof WP_Post) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;
        if (! $order) {
            return;        }
        if (! $order->has_status(array( 'cancelled' )) && ($order->has_status(array( 'processing' )) || $order->has_status(array( 'completed' ) ) || $order->has_status(array( 'pending' ) ))) {
            $ordenExtendida = new WCOrdenExtendida($this->get_order_id($order));
            if ($ordenExtendida->invoice_id=='') {
                do_action('before_invoice_button', $args=array()); ?>
				<p>
					<a class="button fy-invoice-button"  title="<?php _e('create invoice', 'woo-ifactura') ?>"></a>
				</p>
				<?php
            } else {
                ?>
				<p>
				<?php                
                switch ($ordenExtendida->estado_ifactura) {                    
                    case 1:                    
                        /* 1: generación en progreso */
                        ?>						
							<a class="button fy-awaiting-button"  title="<?php _e('awaiting invoice', 'woo-ifactura') ?>"></a> <?php _e('awaiting invoice', 'woo-ifactura') ?>
						<?php                        
                        break;                    
                    case 2:                        
                        /* 2: generada correctamente */
                        ?>						
							<a class="button fy-view-invoice-button"  title="<?php _e('view invoice', 'woo-ifactura') ?>"></a> <?php _e('view invoice', 'woo-ifactura') ?>
						<?php                        
                        break;                    
                    default:                    
                        /* 3: error.  */                        
                        ?>						
							<a class="button fy-invoice-button"  title="<?php _e('create invoice', 'woo-ifactura') ?>"></a> <?php _e('Invoice', 'woo-ifactura') ?>
						<?php                        
                        break;               
                } ?>
				</p>
				<?php
                if ($ordenExtendida->cancelacionInvoice_id == "")
                {
                    ?>
                        <p>
                            <a class="button fy-cancelinvoice-button"  title="<?php _e('cancel invoice', 'woo-ifactura') ?>"></a> <?php _e('cancel invoice', 'woo-ifactura') ?>
                        </p>
                    <?php
                }
                else
                {
                     ?>
                        <p>                            
                        <?php
                        switch ($ordenExtendida->estado_cancelacion_ifactura) {
                            case 1:
                                /* 1: generación en progreso */
                                ?>						
                                    <a class="button fy-awaiting-button"  title="<?php _e('awaiting invoice', 'woo-ifactura') ?>"><?php _e('awaiting invoice', 'woo-ifactura') ?></a>						
                                <?php
                                break;
                            case 2:
                                /* 2: generada correctamente */
                                ?>						
                                    <a class="button fy-view-cancelinvoice-button"  title="<?php _e('view invoice cancel', 'woo-ifactura') ?>"><?php _e('view invoice cancel', 'woo-ifactura') ?></a>								
                                <?php
                                break;
                            default:
                                /* 3: error.  */
                                ?>						
                                    <a class="button fy-cancelinvoice-button"  title="<?php _e('cancel invoice', 'woo-ifactura') ?>"><?php _e('cancel invoice', 'woo-ifactura') ?></a>						
                                <?php
                                break;
                        } ?>
                         Nota de crédito</p>
                    <?php
                }
                $configuracioniFactura = new ConfiguracioniFactura();
                if ($configuracioniFactura->modoDebug)
                {
                    ?>
                        <p>
                            <a class="button fy-deleteinvoice-button"  title="<?php _e('delete invoice', 'woo-ifactura') ?>"></a> <?php _e('delete invoice', 'woo-ifactura') ?>
                        </p>
                    <?php
                }
            }
        } else {
            echo 'Podrás facturar cuando el pedido esté en estado "Procesando" o "Pendiente de pago".';
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
            'facturarautomatico' => array(
                'name' => "Facturar automáticamente",
                'type' => 'select',
                'desc' => 'Generar automáticamente las facturas cuando las órdenes pasan a un estado definido.',
                'default' => '1',
                'id'   => 'wc_settings_tab_woo_ifactura_facturarautomatico',
                'options' => array(
                      '1' => "No",
                      '2' => "Cuando las órdenes pasen a estado 'Pendiente de Pago'",
                      '3' => "Cuando las órdenes pasen a estado 'Procesando'",
                      '4' => "Cuando las órdenes pasen a estado 'En Espera'",
                      '5' => "Cuando las órdenes pasen a estado 'Completado'"
                 )
            ),
            'ignorarshipping' => array(
                'name' => "Ignorar envios",
                'type' => 'checkbox',
                'desc' => "No facturar los items de una orden que son de tipo envío (shipping).",
                'id'   => 'wc_settings_tab_woo_ifactura_ignorarenvio'
            ),
            'agruparventa' => array(
                'name' => "Agrupar elementos de la venta",
                'type' => 'checkbox',
                'desc' => "Agrupar todos los elementos de la venta en un solo item. Se tomará el porcentaje de IVA detectado de los elementos. En caso de más un porcentaje de IVA no se podrá agrupar. <strong>El uso de plugins de descuentos puede generar conflictos con los impuestos si se combina esta opción con la de Ignorar Envios.</strong>",
                 'id'   => 'wc_settings_tab_woo_ifactura_agruparventa'
            ),
            'considerarIVA0' => array(
                'name' => "Productos con IVA 0% o sin IVA",
                'type' => 'select',
                'desc' => "<strong>¡Solo Responsables Inscriptos!</strong> Con que tipo de Alicuota de IVA serán procesados los elementos sin IVA o 0%. <strong>Importante configurar si los productos sin IVA deben ser considerados Exentos o No Gravados</strong>",
                'id'   => 'wc_settings_tab_woo_ifactura_considerariva0',
                'options' => array(
                    '1' => "IVA 0%",
                    '2' => "Exento",
                    '3' => "No gravado"
                )
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
        $order = $the_order;
        if (!$order->has_status(array( 'cancelled' )) && ($order->has_status(array( 'processing' )) || $order->has_status(array( 'completed' )))) {
            $order_id = $this->get_order_id($order);
            $ordenExtendida = new WCOrdenExtendida($order_id);       
            if ($ordenExtendida->invoice_id=='') {
                if (!is_plugin_active('woo-ifactura-exportacion/woo-ifactura-exportacion.php')) {
                    $actions['invoice'] = array(
                        'url'       => '#',
                        'name'      => __('Invoice', 'woo-ifactura'),
                        'action'    => "fy-invoice-button",
                    );
                }
            } else {
                switch ($ordenExtendida->estado_ifactura) {                    
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
                if ($ordenExtendida->cancelacionInvoice_id =='')
                {
                     $actions['invoice'] = array(
                        'url'       => '#',
                        'name'      => __('cancel invoice', 'woo-ifactura'),
                        'action'    => "fy-cancelinvoice-button",
                    );
                }
                else
                {
                    switch ($ordenExtendida->estado_cancelacion_ifactura) {
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
                                'name'      => __('view invoice cancel', 'woo-ifactura'),
                                'action'    => "fy-view-cancelinvoice-button",
                            );
                            break;
                        default:
                            /* 3: error */
                            break;
                    }
                }
            }
        }        
        return $actions;    
    }    
    /**
     * Método para generar un comprobante automáticamente fruto de un proceso cron o cambio de estado
     * Los pasos realizados por este método son registrados como admin notes de la orden
     * @param Integer $order_id ID de la orden
     */
    public function woo_ifactura_generarComprobanteAutomatico($order_id)
    {
        $order = wc_get_order($order_id);
        $estado_orden = $order->get_status();
        $estadoEventoSeleccionado = "";
        $configuracioniFactura = new ConfiguracioniFactura();
        $facturaAutomatico = $configuracioniFactura->facturarAutomatico;
        //RECUPERAR EL ESTADO DE LA FACTURA
        $ordenExtendida = new WCOrdenExtendida($order_id);
        $estado_ifactura = $ordenExtendida->estado_ifactura;
        //SOLO DEBUG
        if ($configuracioniFactura->modoDebug)
        {
            $nota = __("DEBUG estado de facturación: " . var_export($estado_ifactura,1));
            $ordenExtendida->AgregarNota($nota);
        }       
        //ESTADO 2 SIGNIFICA QUE YA SE GENERO UN COMPROBANTE Y 1 SIGNIFICA QUE SE ESTA EMITIENDO
        if ($facturaAutomatico === true && $estado_ifactura < 1)
        {
            $estadoEventoSeleccionado = $configuracioniFactura->eventoParaFacturar;
            if ($estadoEventoSeleccionado == $estado_orden) //CHECK SI COINCIDE EL NUEVO ESTADO DE LA ORDEN CON EL SELECCIONADO
            {
                $orden = wc_get_order($order_id);
                $nota = __("Generando comprobante en iFactura");
                $orden->add_order_note($nota);
                $iFactura = new ConectoriFactura();
                $respuesta = $iFactura->woo_ifactura_procesarInvoice($order_id);
                if ($respuesta->Exito == true) {
                    $nota = __("Creación de factura correcta.");
                    $orden->add_order_note($nota);
                } else {
                    if ($configuracioniFactura->modoDebug)
                    {
                        $nota = __("Fallo la creación de la factura: " . var_export($respuesta, 1));
                    }
                    else
                    {
                        $nota = __("Fallo la creación de la factura: " . $respuesta->Mensaje);
                    }                    
                    $orden->add_order_note($nota);
                }
            }
            else
            {
                //SOLO DEBUG
                if ($configuracioniFactura->modoDebug) {
                    $nota = __("DEBUG la creación de la factura: Estado seleccionado" . var_export($estadoEventoSeleccionado, 1) . " Estado en orden: " . var_export($estado_orden, 1));
                    $ordenExtendida->AgregarNota($nota);
                }          
            }
        }
    }
    /**
    * Procesar la petición AJAX para generar el comprobante.
    *
    **/    
    public function woo_ifactura_invoice()
    {         
        $order_id = intval($_POST["order"]);      
        $iFactura = new ConectoriFactura();  
        $respuesta = $iFactura->woo_ifactura_procesarInvoice($order_id);
        die(json_encode($respuesta,JSON_PRETTY_PRINT));
    }
     /**
    * Procesar la petición AJAX para generar la nota de crédito
    *
    **/    
    public function woo_ifactura_cancel_invoice()
    {         
        $order_id = intval($_POST["order"]);      
        $iFactura = new ConectoriFactura();  
        $respuesta = $iFactura->woo_ifactura_cancelarInvoice($order_id);
        die(json_encode($respuesta,JSON_PRETTY_PRINT));
    }
    public function woo_ifactura_view_invoice()
    {
        $order_id = intval($_POST["order"]);        
        $conector = new ConectoriFactura();
        $url = $conector->getUrlInvoiceGenerada($order_id);
        try {
            if (!empty($url))
            {
                $response = array("Exito" => true, "URLPDF" => $url);

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
    public function woo_ifactura_view_cancel_invoice()
    {
        $order_id = intval($_POST["order"]);        
        $conector = new ConectoriFactura();
        $url = $conector->getUrlNotaGenerada($order_id);
        try {
            if (!empty($url))
            {
                $response = array("Exito" => true, "URLPDF" => $url);

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
    public function woo_ifactura_view_delete_invoices()
    {
        $order_id = intval($_POST["order"]);
        $ordenExtendida = new WCOrdenExtendida($order_id);
        try
        {
            $ordenExtendida->borrarTodo();
            $response = array("Exito" => true, "Mensaje" => "Comprobantes eliminados. Debe actualizar la orden para actualizar el estado de los comporbantes generados asociados.");
        }
        catch (Exception $e)
        {
            $response = array("Exito" => false, "Mensaje" => $e->getMessage());
        }
        echo json_encode($response);
        exit;
    }
    public function woo_ifactura_buttons_column($columns)
    {
        foreach ($columns as $column_name => $column_info) {

            $new_columns[$column_name] = $column_info;

            if ('order_total' === $column_name) {
                $new_columns['order_buttons_wooifactura'] = __('iFactura', 'woo-ifactura');
            }
        }
        return $new_columns;
    }
    function woo_ifactura_content_column($column, $order)
    {
        if (is_int($order)) {
            $order_id = $order;
        } else {
            $order_id = $order->get_id();
        }
        $ordenExtendida = new WCOrdenExtendida($order_id);
        if ($column === 'order_buttons_wooifactura') {
            if ($ordenExtendida->invoice_id == '') {
                echo "Sin facturar";
                return;
            } else {
                switch ($ordenExtendida->estado_ifactura) {
                    case 1:
                        /* 1: generación en progreso */
                        echo __('awaiting invoice', 'woo-ifactura');
                        break;
                    case 2:
                        /* 2: generada correctamente */
                        $conectoriFactura = new ConectoriFactura();
                        $url = $conectoriFactura->getUrlInvoiceGenerada($order_id);
                        echo '<a href="'.$url.'" target="_blank" class="button fy-view-invoice-button"  title="' . __('view invoice', 'woo-ifactura') . '"></a> ';
                        break;
                    default:
                        /* 3: error.  */
                        echo "Sin datos de factura";
                        break;
                }
                return;
            }
        }
        return;
    }
}
