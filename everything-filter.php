<?php
/**
 * Plugin Name: Everything Filter
 * Plugin URI:  https://yourwebsite.com
 * Description: A flexible, filterable directory plugin for WordPress.
 * Version:     2.0.0
 * Author:      Your Law Firm
 * License:     GPL2
 * Text Domain: everything-filter
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EF_VERSION',  '2.0.0' );
define( 'EF_PATH',     plugin_dir_path( __FILE__ ) );
define( 'EF_URL',      plugin_dir_url( __FILE__ ) );
define( 'EF_BASENAME', plugin_basename( __FILE__ ) );

require_once EF_PATH . 'admin/class-ef-db.php';
require_once EF_PATH . 'admin/class-ef-admin.php';
require_once EF_PATH . 'frontend/class-ef-shortcode.php';

register_activation_hook( __FILE__,   [ 'EF_DB', 'install' ] );
register_deactivation_hook( __FILE__, [ 'EF_DB', 'deactivate' ] );

new EF_Admin();
new EF_Shortcode();
