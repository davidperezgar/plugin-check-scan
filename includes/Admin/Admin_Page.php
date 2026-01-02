<?php
/**
 * Admin Page Class
 *
 * @package PluginCheckScan
 */

namespace PluginCheckScan\Admin;

use PluginCheckScan\Scanner;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Page Class - Handles the Tools menu page
 */
class Admin_Page {

	/**
	 * Scanner instance.
	 *
	 * @var Scanner
	 */
	private $scanner;

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'plugin-check-scan';

	/**
	 * Constructor.
	 *
	 * @param Scanner $scanner Scanner instance.
	 */
	public function __construct( Scanner $scanner ) {
		$this->scanner = $scanner;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_pcsc_run_scan', array( $this, 'handle_scan' ) );
		add_action( 'admin_post_pcsc_clear_history', array( $this, 'handle_clear_history' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_pcsc_get_plugins_list', array( $this, 'ajax_get_plugins_list' ) );
		add_action( 'wp_ajax_pcsc_scan_single_plugin', array( $this, 'ajax_scan_single_plugin' ) );
		add_action( 'wp_ajax_pcsc_finalize_scan', array( $this, 'ajax_finalize_scan' ) );
	}

	/**
	 * Add admin menu page.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_management_page(
			__( 'Plugin Check Scan', 'plugin-check-scan' ),
			__( 'Plugin Check Scan', 'plugin-check-scan' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'tools_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'pcsc-admin',
			PCSC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			PCSC_VERSION
		);

		wp_enqueue_script(
			'pcsc-admin',
			PCSC_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			PCSC_VERSION,
			true
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'pcsc-admin',
			'pcscAjax',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pcsc_ajax_nonce' ),
				'i18n'    => array(
					'scanning'         => __( 'Scanning...', 'plugin-check-scan' ),
					'scanningPlugin'   => __( 'Scanning plugin %d of %d...', 'plugin-check-scan' ),
					'scanComplete'     => __( 'Scan completed successfully!', 'plugin-check-scan' ),
					'scanError'        => __( 'An error occurred during scanning.', 'plugin-check-scan' ),
					'pleaseWait'       => __( 'Please wait...', 'plugin-check-scan' ),
					'finalizing'       => __( 'Finalizing scan results...', 'plugin-check-scan' ),
					'gettingPlugins'   => __( 'Getting plugins list...', 'plugin-check-scan' ),
				),
			)
		);
	}

	/**
	 * Handle scan request.
	 *
	 * @return void
	 */
	public function handle_scan() {
		check_admin_referer( 'pcsc_run_scan' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'plugin-check-scan' ) );
		}

		// Run scan.
		$this->scanner->scan_all_plugins();

		// Redirect back to page.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'message' => 'scan_complete',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Handle clear history request.
	 *
	 * @return void
	 */
	public function handle_clear_history() {
		check_admin_referer( 'pcsc_clear_history' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'plugin-check-scan' ) );
		}

		// Clear history.
		$this->scanner->clear_history();

		// Redirect back to page.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'message' => 'history_cleared',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		$latest_scan = $this->scanner->get_latest_scan();
		$history     = $this->scanner->get_history( 10 );
		$message     = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Plugin Check Scanner', 'plugin-check-scan' ); ?></h1>

			<?php $this->render_messages( $message ); ?>

			<div class="pcsc-scanner-page">
				<!-- Run Scan Button -->
				<div class="pcsc-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'pcsc_run_scan' ); ?>
						<input type="hidden" name="action" value="pcsc_run_scan">
						<button type="submit" class="button button-primary button-hero">
							<?php echo esc_html__( 'Run Scanner Now', 'plugin-check-scan' ); ?>
						</button>
					</form>
				</div>

				<!-- Scan History -->
				<div class="pcsc-history">
					<h2><?php echo esc_html__( 'Scan History', 'plugin-check-scan' ); ?></h2>
					<?php $this->render_history( $history ); ?>
				</div>

				<!-- Latest Scan Results -->
				<?php if ( $latest_scan ) : ?>
					<div class="pcsc-results">
						<h2><?php echo esc_html__( 'Latest Scan Results', 'plugin-check-scan' ); ?></h2>
						<?php $this->render_scan_results( $latest_scan ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render admin messages.
	 *
	 * @param string $message Message type.
	 * @return void
	 */
	private function render_messages( $message ) {
		if ( empty( $message ) ) {
			return;
		}

		$messages = array(
			'scan_complete'   => __( 'Scan completed successfully!', 'plugin-check-scan' ),
			'history_cleared' => __( 'History cleared successfully!', 'plugin-check-scan' ),
		);

		if ( isset( $messages[ $message ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $message ] ) . '</p></div>';
		}
	}

	/**
	 * Render scan history.
	 *
	 * @param array $history Scan history.
	 * @return void
	 */
	private function render_history( $history ) {
		if ( empty( $history ) ) {
			echo '<p>' . esc_html__( 'No scan history available.', 'plugin-check-scan' ) . '</p>';
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Date', 'plugin-check-scan' ); ?></th>
					<th><?php echo esc_html__( 'Average Score', 'plugin-check-scan' ); ?></th>
					<th><?php echo esc_html__( 'Plugins Scanned', 'plugin-check-scan' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $history as $scan ) : ?>
					<tr>
						<td><?php echo esc_html( $scan['date'] ); ?></td>
						<td>
							<span class="pcsc-score-badge <?php echo esc_attr( $this->get_score_class( $scan['average_score'] ) ); ?>">
								<?php echo esc_html( $scan['average_score'] ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $scan['total_plugins'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 20px;">
			<?php wp_nonce_field( 'pcsc_clear_history' ); ?>
			<input type="hidden" name="action" value="pcsc_clear_history">
			<button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all scan history?', 'plugin-check-scan' ) ); ?>')">
				<?php echo esc_html__( 'Clear History', 'plugin-check-scan' ); ?>
			</button>
		</form>
		<?php
	}

	/**
	 * Render scan results with accordion.
	 *
	 * @param array $scan Scan data.
	 * @return void
	 */
	private function render_scan_results( $scan ) {
		if ( empty( $scan['plugins'] ) ) {
			echo '<p>' . esc_html__( 'No plugin results available.', 'plugin-check-scan' ) . '</p>';
			return;
		}

		?>
		<div class="pcsc-accordion">
			<?php foreach ( $scan['plugins'] as $plugin_file => $result ) : ?>
				<div class="pcsc-accordion-item">
					<div class="pcsc-accordion-header">
						<div class="pcsc-plugin-info">
							<strong><?php echo esc_html( $result['name'] ); ?></strong>
							<span class="pcsc-plugin-version"><?php echo esc_html( sprintf( __( 'v%s', 'plugin-check-scan' ), $result['version'] ) ); ?></span>
						</div>
						<div class="pcsc-plugin-score">
							<span class="pcsc-score-badge <?php echo esc_attr( $this->get_score_class( $result['score'] ) ); ?>">
								<?php echo esc_html__( 'Score:', 'plugin-check-scan' ); ?> <?php echo esc_html( $result['score'] ); ?>
							</span>
							<span class="pcsc-toggle">▼</span>
						</div>
					</div>
					<div class="pcsc-accordion-content">
						<div class="pcsc-result-summary">
							<p>
								<strong><?php echo esc_html__( 'Errors:', 'plugin-check-scan' ); ?></strong> <?php echo esc_html( isset( $result['error_count'] ) ? $result['error_count'] : 0 ); ?>
								<strong><?php echo esc_html__( 'Warnings:', 'plugin-check-scan' ); ?></strong> <?php echo esc_html( isset( $result['warning_count'] ) ? $result['warning_count'] : 0 ); ?>
							</p>
						</div>

						<?php if ( ! empty( $result['errors'] ) ) : ?>
							<div class="pcsc-issues pcsc-errors">
								<h4><?php echo esc_html__( 'Errors', 'plugin-check-scan' ); ?></h4>
								<ul>
									<?php foreach ( $result['errors'] as $error ) : ?>
										<li><?php echo esc_html( is_array( $error ) ? implode( ' - ', $error ) : $error ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $result['warnings'] ) ) : ?>
							<div class="pcsc-issues pcsc-warnings">
								<h4><?php echo esc_html__( 'Warnings', 'plugin-check-scan' ); ?></h4>
								<ul>
									<?php foreach ( $result['warnings'] as $warning ) : ?>
										<li><?php echo esc_html( is_array( $warning ) ? implode( ' - ', $warning ) : $warning ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php if ( isset( $result['error'] ) ) : ?>
							<div class="notice notice-error inline">
								<p><?php echo esc_html( $result['error'] ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Get CSS class based on score.
	 *
	 * @param float $score Score value.
	 * @return string CSS class.
	 */
	private function get_score_class( $score ) {
		if ( $score >= 90 ) {
			return 'pcsc-score-excellent';
		} elseif ( $score >= 70 ) {
			return 'pcsc-score-good';
		} elseif ( $score >= 50 ) {
			return 'pcsc-score-warning';
		} else {
			return 'pcsc-score-danger';
		}
	}

	/**
	 * AJAX handler to get list of plugins to scan.
	 *
	 * @return void
	 */
	public function ajax_get_plugins_list() {
		check_ajax_referer( 'pcsc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Insufficient permissions.', 'plugin-check-scan' ) )
			);
		}

		$plugins = get_plugins();
		$plugin_list = array();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$plugin_slug = dirname( $plugin_file );
			if ( '.' === $plugin_slug ) {
				$plugin_slug = basename( $plugin_file, '.php' );
			}

			$plugin_list[] = array(
				'file' => $plugin_file,
				'slug' => $plugin_slug,
				'name' => $plugin_data['Name'],
			);
		}

		wp_send_json_success(
			array(
				'plugins' => $plugin_list,
				'total'   => count( $plugin_list ),
			)
		);
	}

	/**
	 * AJAX handler to scan a single plugin.
	 *
	 * @return void
	 */
	public function ajax_scan_single_plugin() {
		check_ajax_referer( 'pcsc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Insufficient permissions.', 'plugin-check-scan' ) )
			);
		}

		$plugin_file = isset( $_POST['plugin_file'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) ) : '';
		$plugin_slug = isset( $_POST['plugin_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_slug'] ) ) : '';

		if ( empty( $plugin_file ) || empty( $plugin_slug ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid plugin data.', 'plugin-check-scan' ) )
			);
		}

		// Get plugin data.
		$all_plugins = get_plugins();
		if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Plugin not found.', 'plugin-check-scan' ) )
			);
		}

		$plugin_data = $all_plugins[ $plugin_file ];

		// Scan the plugin.
		$result = $this->scanner->scan_single_plugin( $plugin_slug, $plugin_file, $plugin_data );

		if ( $result ) {
			// Always return success, even if there was an error during scan.
			// The error will be shown in the result.
			wp_send_json_success(
				array(
					'plugin_file' => $plugin_file,
					'result'      => $result,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message'     => __( 'Scan failed for this plugin.', 'plugin-check-scan' ),
					'plugin_file' => $plugin_file,
				)
			);
		}
	}

	/**
	 * AJAX handler to finalize scan and save results.
	 *
	 * @return void
	 */
	public function ajax_finalize_scan() {
		check_ajax_referer( 'pcsc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Insufficient permissions.', 'plugin-check-scan' ) )
			);
		}

		$scan_results = isset( $_POST['scan_results'] ) ? json_decode( wp_unslash( $_POST['scan_results'] ), true ) : array();

		if ( empty( $scan_results ) ) {
			wp_send_json_error(
				array( 'message' => __( 'No scan results provided.', 'plugin-check-scan' ) )
			);
		}

		// Calculate totals and save.
		$total_score   = 0;
		$plugin_count  = 0;

		foreach ( $scan_results as $result ) {
			if ( isset( $result['score'] ) ) {
				$total_score += $result['score'];
				$plugin_count++;
			}
		}

		$average_score = $plugin_count > 0 ? round( $total_score / $plugin_count, 2 ) : 0;

		$scan_data = array(
			'timestamp'     => current_time( 'timestamp' ),
			'date'          => current_time( 'mysql' ),
			'plugins'       => $scan_results,
			'total_plugins' => $plugin_count,
			'average_score' => $average_score,
		);

		// Save results.
		$this->scanner->save_scan_results( $scan_data );

		wp_send_json_success(
			array(
				'message'       => __( 'Scan completed successfully!', 'plugin-check-scan' ),
				'average_score' => $average_score,
				'total_plugins' => $plugin_count,
				'redirect_url'  => add_query_arg(
					array(
						'page'    => self::PAGE_SLUG,
						'message' => 'scan_complete',
					),
					admin_url( 'tools.php' )
				),
			)
		);
	}
}

