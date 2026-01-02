<?php
/**
 * Dashboard Widget Class
 *
 * @package PluginCheckScan
 */

namespace PluginCheckScan\Admin;

use PluginCheckScan\Scanner;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard Widget Class - Displays scan score on dashboard
 */
class Dashboard_Widget {

	/**
	 * Scanner instance.
	 *
	 * @var Scanner
	 */
	private $scanner;

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
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_scripts' ) );
	}

	/**
	 * Enqueue dashboard widget scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_dashboard_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}

		// Enqueue styles for dashboard widget.
		wp_enqueue_style(
			'pcsc-admin',
			PCSC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			PCSC_VERSION
		);

		wp_enqueue_script(
			'pcsc-dashboard',
			PCSC_PLUGIN_URL . 'assets/js/dashboard.js',
			array(),
			PCSC_VERSION,
			true
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'pcsc-dashboard',
			'pcscAjax',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'pcsc_ajax_nonce' ),
				'redirectUrl' => admin_url( 'tools.php?page=plugin-check-scan' ),
			)
		);
	}

	/**
	 * Add dashboard widget.
	 *
	 * @return void
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'pcsc_dashboard_widget',
			__( 'Plugin Check Scan', 'plugin-check-scan' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Render dashboard widget.
	 *
	 * @return void
	 */
	public function render_widget() {
		$latest_scan = $this->scanner->get_latest_scan();

		if ( ! $latest_scan ) {
			?>
			<div class="pcsc-dashboard-widget">
				<p><?php echo esc_html__( 'No scans available yet.', 'plugin-check-scan' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'tools.php?page=plugin-check-scan' ) ); ?>" class="button button-primary">
						<?php echo esc_html__( 'Run Your First Scan', 'plugin-check-scan' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}

		$score       = $latest_scan['average_score'];
		$score_class = $this->get_score_class( $score );

		?>
		<div class="pcsc-dashboard-widget">
			<div class="pcsc-widget-score <?php echo esc_attr( $score_class ); ?>">
				<div class="pcsc-score-circle">
					<span class="pcsc-score-value"><?php echo esc_html( $score ); ?></span>
					<span class="pcsc-score-max">/100</span>
				</div>
				<div class="pcsc-score-details">
					<h3><?php echo esc_html__( 'Security Score', 'plugin-check-scan' ); ?></h3>
					<p><?php echo esc_html( sprintf( __( '%d plugins scanned', 'plugin-check-scan' ), $latest_scan['total_plugins'] ) ); ?></p>
					<p class="pcsc-last-scan"><?php echo esc_html( sprintf( __( 'Last scan: %s', 'plugin-check-scan' ), human_time_diff( $latest_scan['timestamp'] ) . ' ' . __( 'ago', 'plugin-check-scan' ) ) ); ?></p>
				</div>
			</div>

			<div class="pcsc-widget-actions">
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=plugin-check-scan' ) ); ?>" class="button">
					<?php echo esc_html__( 'View Details', 'plugin-check-scan' ); ?>
				</a>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
					<?php wp_nonce_field( 'pcsc_run_scan' ); ?>
					<input type="hidden" name="action" value="pcsc_run_scan">
					<button type="submit" class="button button-primary">
						<?php echo esc_html__( 'Scan Now', 'plugin-check-scan' ); ?>
					</button>
				</form>
			</div>

			<?php if ( $score < 70 ) : ?>
				<div class="pcsc-widget-alert">
					<p><strong><?php echo esc_html__( 'Warning:', 'plugin-check-scan' ); ?></strong> <?php echo esc_html__( 'Your security score is below recommended levels. Check the detailed report for issues.', 'plugin-check-scan' ); ?></p>
				</div>
			<?php endif; ?>
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
}

