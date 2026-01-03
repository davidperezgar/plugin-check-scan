<?php
/**
 * Scanner Class
 *
 * @package PluginCheckScan
 */

namespace PluginCheckScan;

defined( 'ABSPATH' ) || exit;

/**
 * Scanner Class - Handles plugin scanning and scoring
 */
class Scanner {

	/**
	 * Option name for scan history.
	 *
	 * @var string
	 */
	const HISTORY_OPTION = 'pcsc_scan_history';

	/**
	 * Option name for latest scan.
	 *
	 * @var string
	 */
	const LATEST_SCAN_OPTION = 'pcsc_latest_scan';

	/**
	 * Maximum number of history records to keep.
	 *
	 * @var int
	 */
	const MAX_HISTORY = 20;

	/**
	 * Scan all plugins in the WordPress installation.
	 *
	 * @return array Scan results with scores.
	 */
	public function scan_all_plugins() {
		$plugins = get_plugins();
		$results = array();
		$total_score = 0;
		$plugin_count = 0;

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$plugin_slug = dirname( $plugin_file );
			if ( '.' === $plugin_slug ) {
				$plugin_slug = basename( $plugin_file, '.php' );
			}

			$scan_result = $this->scan_plugin( $plugin_slug, $plugin_file, $plugin_data );
			
			if ( $scan_result ) {
				$results[ $plugin_file ] = $scan_result;
				$total_score += $scan_result['score'];
				$plugin_count++;
			}
		}

		// Calculate average score.
		$average_score = $plugin_count > 0 ? round( $total_score / $plugin_count, 2 ) : 0;

		$scan_data = array(
			'timestamp'     => current_time( 'timestamp' ),
			'date'          => current_time( 'mysql' ),
			'plugins'       => $results,
			'total_plugins' => $plugin_count,
			'average_score' => $average_score,
		);

		// Save to history.
		$this->save_to_history( $scan_data );

		// Save as latest scan.
		update_option( self::LATEST_SCAN_OPTION, $scan_data );

		return $scan_data;
	}

	/**
	 * Scan a single plugin (public method for AJAX).
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_file Plugin file path.
	 * @param array  $plugin_data Plugin data.
	 * @return array|false Scan result or false on failure.
	 */
	public function scan_single_plugin( $plugin_slug, $plugin_file, $plugin_data ) {
		return $this->scan_plugin( $plugin_slug, $plugin_file, $plugin_data );
	}

	/**
	 * Scan a single plugin (private implementation).
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_file Plugin file path.
	 * @param array  $plugin_data Plugin data.
	 * @return array|false Scan result or false on failure.
	 */
	private function scan_plugin( $plugin_slug, $plugin_file, $plugin_data ) {
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;

		// Skip if plugin path doesn't exist.
		if ( ! file_exists( $plugin_path ) ) {
			return array(
				'name'    => $plugin_data['Name'],
				'version' => $plugin_data['Version'],
				'author'  => $plugin_data['Author'],
				'score'   => 0,
				'error'   => 'Plugin directory not found: ' . $plugin_slug,
			);
		}

		try {
			// Use AJAX_Runner to run checks (same as Plugin Check admin interface).
			$runner = new \WordPress\Plugin_Check\Checker\AJAX_Runner();
			
			// Configure the runner - set_plugin expects plugin basename (e.g., 'plugin-name/plugin-name.php').
			$runner->set_plugin( $plugin_file );
			$runner->set_slug( $plugin_slug );
			$runner->set_experimental_flag( false );
			$runner->set_categories( array(
				\WordPress\Plugin_Check\Checker\Check_Categories::CATEGORY_PLUGIN_REPO,
			) );
			
			set_time_limit( 300 );
			ini_set( 'max_execution_time', '300' );
			
			// Run checks - this handles all setup, preparation, and execution.
			$check_result = $runner->run();

			// Calculate score based on results.
			$score = $this->calculate_score( $check_result );

			// Count errors by severity and flatten structure.
			$errors         = $check_result->get_errors();
			$warnings       = $check_result->get_warnings();
			$error_count    = $check_result->get_error_count();
			$error_low_count = 0;

			// Flatten errors into readable format and count low severity.
			$errors_flat = $this->flatten_messages( $errors, true, $error_low_count );

			// Flatten warnings into readable format.
			$warnings_flat = $this->flatten_messages( $warnings, false );

			return array(
				'name'           => $plugin_data['Name'],
				'version'        => $plugin_data['Version'],
				'author'         => $plugin_data['Author'],
				'score'          => $score,
				'errors'         => $errors_flat,
				'warnings'       => $warnings_flat,
				'error_count'    => $error_count,
				'error_low_count' => $error_low_count,
				'warning_count'  => count( $warnings_flat ),
				'timestamp'      => current_time( 'timestamp' ),
			);
		} catch ( \Exception $e ) {
			// Log the error for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'Plugin Check Scan Error [' . $plugin_data['Name'] . ']: ' . $e->getMessage() );
			}
			
			return array(
				'name'    => $plugin_data['Name'],
				'version' => $plugin_data['Version'],
				'author'  => $plugin_data['Author'],
				'score'   => 0,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Calculate score based on check results.
	 *
	 * @param object $check_result Check result object.
	 * @return float Score between 0 and 100.
	 */
	private function calculate_score( $check_result ) {
		$error_count   = $check_result->get_error_count();
		$warning_count = $check_result->get_warning_count();

		// Base score.
		$score = 100;

		// Deduct points for errors (5 points each).
		$score -= ( $error_count * 5 );

		// Deduct points for warnings (2 points each).
		$score -= ( $warning_count * 2 );

		// Ensure score is between 0 and 100.
		$score = max( 0, min( 100, $score ) );

		return round( $score, 2 );
	}

	/**
	 * Flatten nested messages structure into readable array.
	 *
	 * @param array $messages Nested messages array (file => line => column => messages).
	 * @param bool  $is_errors Whether these are errors (to count low severity).
	 * @param int   $error_low_count Reference to count low severity errors (for errors only).
	 * @return array Flat array of formatted message strings.
	 */
	private function flatten_messages( array $messages, $is_errors = false, &$error_low_count = 0 ) {
		$flat = array();

		foreach ( $messages as $file => $file_messages ) {
			foreach ( $file_messages as $line => $line_messages ) {
				foreach ( $line_messages as $column => $column_messages ) {
					foreach ( $column_messages as $message_data ) {
						// Count low severity errors (only for errors, not warnings).
						if ( $is_errors && isset( $message_data['severity'] ) && $message_data['severity'] < 5 ) {
							$error_low_count++;
						}

						// Build readable message.
						$message = isset( $message_data['message'] ) ? $message_data['message'] : '';
						
						// Add file and line info if available.
						$parts = array();
						if ( ! empty( $file ) && '' !== $file ) {
							$parts[] = $file;
						}
						if ( ! empty( $line ) && 0 !== $line ) {
							$parts[] = sprintf( 'line %d', $line );
						}
						if ( ! empty( $column ) && 0 !== $column ) {
							$parts[] = sprintf( 'column %d', $column );
						}

						$location = ! empty( $parts ) ? ' (' . implode( ', ', $parts ) . ')' : '';
						
						// Add code if available.
						$code = isset( $message_data['code'] ) && ! empty( $message_data['code'] ) 
							? ' [' . $message_data['code'] . ']' 
							: '';

						$flat[] = $message . $location . $code;
					}
				}
			}
		}

		return $flat;
	}

	/**
	 * Save scan to history.
	 *
	 * @param array $scan_data Scan data.
	 * @return void
	 */
	private function save_to_history( $scan_data ) {
		$history = get_option( self::HISTORY_OPTION, array() );

		// Add new scan to beginning of array.
		array_unshift( $history, $scan_data );

		// Keep only last MAX_HISTORY records.
		$history = array_slice( $history, 0, self::MAX_HISTORY );

		update_option( self::HISTORY_OPTION, $history );
	}

	/**
	 * Get scan history.
	 *
	 * @param int $limit Number of records to retrieve.
	 * @return array Scan history.
	 */
	public function get_history( $limit = 10 ) {
		$history = get_option( self::HISTORY_OPTION, array() );

		if ( $limit > 0 ) {
			$history = array_slice( $history, 0, $limit );
		}

		return $history;
	}

	/**
	 * Get latest scan.
	 *
	 * @return array|false Latest scan data or false if none exists.
	 */
	public function get_latest_scan() {
		return get_option( self::LATEST_SCAN_OPTION, false );
	}

	/**
	 * Clear scan history.
	 *
	 * @return void
	 */
	public function clear_history() {
		delete_option( self::HISTORY_OPTION );
		delete_option( self::LATEST_SCAN_OPTION );
	}

	/**
	 * Save scan results (public method for AJAX).
	 *
	 * @param array $scan_data Scan data.
	 * @return void
	 */
	public function save_scan_results( $scan_data ) {
		// Save to history.
		$this->save_to_history( $scan_data );

		// Save as latest scan.
		update_option( self::LATEST_SCAN_OPTION, $scan_data );
	}
}

