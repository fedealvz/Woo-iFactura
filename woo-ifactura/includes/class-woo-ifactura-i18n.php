<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.0.1
 * @package    woo-ifactura
 * @subpackage woo-ifactura/includes
 * @author     Federico Alvarez
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Woo_iFactura_i18n
{


    /**
     * Load the plugin text domain for translation.
     *
     * @since    0.0.1
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'woo-ifactura',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
