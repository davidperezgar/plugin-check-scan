<?php
/**
 * Main Plugin Class
 *
 * @package PluginCheckScan
 */

namespace PluginCheckScan;

defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin Class
 */
class Plugin_Main {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin_Main|null
	 */
	private static $instance = null;

	/**
	 * Scanner instance.
	 *
	 * @var Scanner
	 */
	public $scanner;

	/**
	 * Admin Page instance.
	 *
	 * @var Admin\Admin_Page
	 */
	public $admin_page;

	/**
	 * Dashboard Widget instance.
	 *
	 * @var Admin\Dashboard_Widget
	 */
	public $dashboard_widget;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin_Main
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
		$this->init_classes();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_notices', array( $this, 'check_dependencies' ) );
	}

	/**
	 * Check if Plugin Check is activated.
	 *
	 * @return void
	 */
	public function check_dependencies() {
		// Only show on our plugin pages.
		$screen = get_current_screen();
		if ( ! $screen || ( 'dashboard' !== $screen->id && 'tools_page_plugin-check-scan' !== $screen->id ) ) {
			return;
		}

		// Check_Runner is an interface, not a class.
		if ( ! interface_exists( 'WordPress\Plugin_Check\Checker\Check_Runner' ) ) {
			$plugin_check_installed = file_exists( WP_PLUGIN_DIR . '/plugin-check/plugin.php' );
			
			if ( $plugin_check_installed ) {
				$activation_url = wp_nonce_url(
					admin_url( 'plugins.php?action=activate&plugin=plugin-check/plugin.php' ),
					'activate-plugin_plugin-check/plugin.php'
				);
				
				echo '<div class="notice notice-warning"><p>';
				echo '<strong>Plugin Check Scan:</strong> ';
				echo esc_html__( 'Plugin Check is installed but not activated.', 'plugin-check-scan' );
				echo ' <a href="' . esc_url( $activation_url ) . '" class="button button-primary button-small">';
				echo esc_html__( 'Activate Plugin Check', 'plugin-check-scan' );
				echo '</a></p></div>';
			} else {
				$install_url = wp_nonce_url(
					admin_url( 'update.php?action=install-plugin&plugin=plugin-check' ),
					'install-plugin_plugin-check'
				);
				
				echo '<div class="notice notice-error"><p>';
				echo '<strong>Plugin Check Scan:</strong> ';
				echo esc_html__( 'This plugin requires Plugin Check to function.', 'plugin-check-scan' );
				echo ' <a href="' . esc_url( $install_url ) . '" class="button button-primary button-small">';
				echo esc_html__( 'Install Plugin Check', 'plugin-check-scan' );
				echo '</a></p></div>';
			}
		}
	}

	/**
	 * Initialize plugin classes.
	 *
	 * @return void
	 */
	private function init_classes() {
		// Initialize Scanner.
		$this->scanner = new Scanner();

		// Initialize Admin classes.
		if ( is_admin() ) {
			$this->admin_page       = new Admin\Admin_Page( $this->scanner );
			$this->dashboard_widget = new Admin\Dashboard_Widget( $this->scanner );
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'plugin-check-scan',
			false,
			dirname( plugin_basename( PCSC_FILE ) ) . '/languages'
		);
	}
}

