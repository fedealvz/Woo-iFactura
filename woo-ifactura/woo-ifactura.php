<?php

/**
* Plugin Name: Woo iFactura
* Description: Woo iFactura integra WooCommerce con el servicio de factura electrónica de iFactura.com.ar
* Version: 1.0
* Author: Federico Alvarez
* Author URI: https://github.com/fedealvz/Woo-iFactura
* Text Domain: woo-ifactura
* Domain Path: /languages/
* License: GPL v3 or later
*
*
* Copyright: © 2019 Federico Alvarez
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
* 
* @author Federico Alvarez
* @package woo-ifactura
* @version 1.0
*/

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-woo-ifactura.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.1
 */
function run_woo_ifactura()
{
    $plugin = new Woo_IFactura();
    $plugin->run();
}
run_woo_ifactura();
