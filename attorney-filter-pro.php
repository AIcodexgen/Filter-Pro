<?php
/**
 * Plugin Name: Attorney Directory Pro
 * Plugin URI:  https://yourwebsite.com
 * Description: Advanced attorney directory with full admin backend. Manage attorneys, offices, theme colors, images, and layout — all from WordPress admin. Use [attorney_filter] shortcode.
 * Version:     2.0.0
 * Author:      Your Law Firm
 * License:     GPL2
 * Text Domain: attorney-filter-pro
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AFP_VERSION',  '2.0.0' );
define( 'AFP_PATH',     plugin_dir_path( __FILE__ ) );
define( 'AFP_URL',      plugin_dir_url( __FILE__ ) );
define( 'AFP_BASENAME', plugin_basename( __FILE__ ) );

require_once AFP_PATH . 'admin/class-afp-db.php';
require_once AFP_PATH . 'admin/class-afp-admin.php';
require_once AFP_PATH . 'frontend/class-afp-shortcode.php';

register_activation_hook( __FILE__,   [ 'AFP_DB', 'install' ] );
register_deactivation_hook( __FILE__, [ 'AFP_DB', 'deactivate' ] );

new AFP_Admin();
new AFP_Shortcode();
