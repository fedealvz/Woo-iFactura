<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.1
 * @package    woo-ifactura
 * @subpackage woo-ifactura/includes
 * @author     Federico Alvarez
 */
 
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

 
class Woo_iFactura
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    0.0.1
     * @access   protected
     * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    0.0.1
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    0.0.1
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    0.0.1
     */
    public function __construct()
    {
        $this->plugin_name = 'iFactura para WooCommerce';
        
        $this->version = '0.1';

        $this->load_dependencies();
        
        $this->set_locale();
        
        $this->define_admin_hooks();
        
        $this->define_public_hooks();
        
        add_action('woocommerce_api_'.strtolower(get_class($this)), array(&$this, 'handle_callback'));
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Woo_ifactura_Loader. Orchestrates the hooks of the plugin.
     * - Woo_ifactura_i18n. Defines internationalization functionality.
     * - Woo_ifactura_Admin. Defines all hooks for the admin area.
     * - Woo_ifactura_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    0.1
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-ifactura-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-ifactura-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-woo-ifactura-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-plugin-name-public.php';

        $this->loader = new Woo_iFactura_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    0.1
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new Woo_iFactura_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    0.1
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Woo_iFactura_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        $this->loader->add_filter('woocommerce_settings_tabs_array', $plugin_admin, 'add_settings_tab', 50);
        
        $this->loader->add_action('woocommerce_settings_tabs_settings_tab_woo_ifactura', $plugin_admin, 'settings_tab');
        
        $this->loader->add_action('woocommerce_update_options_settings_tab_woo_ifactura', $plugin_admin, 'update_settings');
        
        $this->loader->add_filter('woocommerce_admin_order_actions', $plugin_admin, 'woo_ifactura_order_actions', 10, 2);
        
        $this->loader->add_action('wp_ajax_woo_ifactura_do_ajax_request', $plugin_admin, 'woo_ifactura_invoice');
        
        $this->loader->add_action('wp_ajax_woo_ifactura_view_ajax_request', $plugin_admin, 'woo_ifactura_view_invoice');
        
        $this->loader->add_action('woocommerce_created_customer', $plugin_admin, 'woo_ifactura_save_allfields');
        
        $this->loader->add_action('woocommerce_edit_account_form', $plugin_admin, 'woo_ifactura_add_allfields_to_my_account');
        
        $this->loader->add_action('woocommerce_save_account_details', $plugin_admin, 'woo_ifactura_save_allfields');
        
        $this->loader->add_action('woocommerce_checkout_fields', $plugin_admin, 'woo_ifactura_dni_checkout_field');
        
        $this->loader->add_action('woocommerce_checkout_process', $plugin_admin, 'woo_ifactura_checkout_field_process');
        
        $this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_admin, 'woo_ifactura_update_order_meta');
        
        $this->loader->add_action('woocommerce_admin_order_data_after_billing_address', $plugin_admin, 'woo_ifactura_display_admin_order_meta');
        
        $this->loader->add_filter('woocommerce_email_order_meta_keys', $plugin_admin, 'woo_ifactura_display_dni_in_email_fields');
        $this->loader->add_filter('woocommerce_email_order_meta_keys', $plugin_admin, 'woo_ifactura_display_condicionimpositiva_in_email_fields');
        $this->loader->add_filter('woocommerce_email_order_meta_keys', $plugin_admin, 'woo_ifactura_display_tipopersona_in_email_fields');
        
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'woo_ifactura_add_metaboxes');
        
        $this->loader->add_action('admin_notices', $plugin_admin, 'woo_warning');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    0.1
     * @access   private
     */
    private function define_public_hooks()
    {

        //$plugin_public = new Plugin_Name_Public( $this->get_plugin_name(), $this->get_version() );

        //$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        //$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    0.1
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     0.1
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     0.1
     * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     0.1
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

}
