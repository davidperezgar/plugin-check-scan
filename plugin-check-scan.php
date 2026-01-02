<?php
/**
 * Plugin Name: Plugin Check Scan
 * Plugin URI:  https://wordpress.org/plugins/plugin-check-scan/
 * Description: A Security Scanner for your WordPress installation based on Plugin Check Plugin. Audits all installed plugins and generates a security score.
 * Version:     1.0.0
 * Author:      David Perez
 * Author URI:  https://make.wordpress.org/plugins/
 * Text Domain: plugin-check-scan
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins: plugin-check
 *
 * @package     WordPress
 * @author      David Perez
 * @copyright   2026 David Perez
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 *
 * Prefix:      PCSC_
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'PCSC_VERSION', '1.0.0' );
define( 'PCSC_FILE', __FILE__ );
define( 'PCSC_PLUGIN', __FILE__ );
define( 'PCSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PCSC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Check if autoloader is loaded.
if ( ! file_exists( PCSC_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>Plugin Check Scan: ' . esc_html__( 'Composer autoloader not found. Please run "composer install".', 'plugin-check-scan' ) . '</p></div>';
			}
		);
	return;
}

require_once PCSC_PLUGIN_PATH . 'vendor/autoload.php';

add_action( 'plugins_loaded', 'pcsc_plugin_init' );
/**
 * Load localization files
 *
 * @return void
 */
function pcsc_plugin_init() {
	load_plugin_textdomain( 'plugin-check-scan', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Check if Plugin Check is active.
	if ( ! class_exists( 'WordPress\Plugin_Check\Plugin_Main' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'Plugin Check Scan requires the Plugin Check plugin to be installed and activated.', 'plugin-check-scan' );
				echo ' <a href="' . esc_url( admin_url( 'plugin-install.php?s=plugin-check&tab=search&type=term' ) ) . '">';
				echo esc_html__( 'Install Plugin Check', 'plugin-check-scanner' );
				echo '</a></p></div>';
			}
		);
		return;
	}

	// Initialize main plugin class.
	\PluginCheckScan\Plugin_Main::instance();
}

