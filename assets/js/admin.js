/**
 * Plugin Check Scan Admin JavaScript
 */

(function() {
	'use strict';

	let scanInProgress = false;
	let pluginsList = [];
	let scannedResults = {};
	let currentIndex = 0;

	document.addEventListener('DOMContentLoaded', function() {
		// Initialize accordion functionality.
		initAccordion();

		// Initialize AJAX scan.
		initAjaxScan();
	});

	/**
	 * Initialize accordion for plugin results.
	 */
	function initAccordion() {
		const accordionHeaders = document.querySelectorAll('.pcsc-accordion-header');

		accordionHeaders.forEach(function(header) {
			header.addEventListener('click', function() {
				const item = this.parentElement;
				const isActive = item.classList.contains('active');

				// Close all accordion items.
				document.querySelectorAll('.pcsc-accordion-item').forEach(function(accordionItem) {
					accordionItem.classList.remove('active');
				});

				// Toggle current item.
				if (!isActive) {
					item.classList.add('active');
				}
			});
		});
	}

	/**
	 * Initialize AJAX scan functionality.
	 */
	function initAjaxScan() {
		const scanForm = document.querySelector('form[action*="admin-post.php"] input[value="pcsc_run_scan"]');
		
		if (!scanForm) {
			return;
		}

		const form = scanForm.closest('form');
		const submitButton = form.querySelector('button[type="submit"]');

		if (!submitButton) {
			return;
		}

		// Replace form submission with AJAX.
		form.addEventListener('submit', function(e) {
			e.preventDefault();

			if (scanInProgress) {
				return;
			}

			startAjaxScan(submitButton);
		});
	}

	/**
	 * Start AJAX scan process.
	 */
	function startAjaxScan(button) {
		if (scanInProgress) {
			return;
		}

		scanInProgress = true;
		currentIndex = 0;
		scannedResults = {};
		pluginsList = [];

		// Disable button and show progress.
		button.disabled = true;
		button.textContent = pcscAjax.i18n.gettingPlugins;

		// Create or update progress container.
		showProgressContainer();

		// Step 1: Get list of plugins.
		getPluginsList();
	}

	/**
	 * Show progress container.
	 */
	function showProgressContainer() {
		let progressContainer = document.querySelector('.pcsc-scan-progress');

		if (!progressContainer) {
			const actionsDiv = document.querySelector('.pcsc-actions');
			progressContainer = document.createElement('div');
			progressContainer.className = 'pcsc-scan-progress';
			actionsDiv.parentNode.insertBefore(progressContainer, actionsDiv.nextSibling);
		}

		progressContainer.innerHTML = `
			<div class="pcsc-progress-bar">
				<div class="pcsc-progress-fill" style="width: 0%"></div>
			</div>
			<div class="pcsc-progress-text">${pcscAjax.i18n.pleaseWait}</div>
			<div class="pcsc-progress-details"></div>
		`;

		progressContainer.style.display = 'block';
		
		// Clear any previous results.
		const progressDetails = progressContainer.querySelector('.pcsc-progress-details');
		if (progressDetails) {
			progressDetails.innerHTML = '';
		}
	}

	/**
	 * Update progress display.
	 */
	function updateProgress(current, total, text, pluginResult = null) {
		const progressFill = document.querySelector('.pcsc-progress-fill');
		const progressText = document.querySelector('.pcsc-progress-text');
		const progressDetails = document.querySelector('.pcsc-progress-details');

		if (progressFill && total > 0) {
			const percentage = Math.round((current / total) * 100);
			progressFill.style.width = percentage + '%';
		}

		if (progressText && text) {
			progressText.textContent = text;
		}

		if (progressDetails) {
			if (pluginResult) {
				// Show plugin result details.
				const pluginName = pluginResult.name || 'Unknown';
				const errorCount = pluginResult.error_count || 0;
				const errorLowCount = pluginResult.error_low_count || 0;
				const warningCount = pluginResult.warning_count || 0;
				
				// Create result item HTML.
				const resultItem = document.createElement('div');
				resultItem.className = 'pcsc-progress-plugin-result';
				resultItem.innerHTML = `
					<div class="pcsc-progress-plugin-name">${escapeHtml(pluginName)}</div>
					<div class="pcsc-progress-plugin-stats">
						<span class="pcsc-stat-error">ERRORS: ${errorCount}</span>
						<span class="pcsc-stat-warning">WARNINGS: ${warningCount}</span>
						<span class="pcsc-stat-error-low">ERROR_LOW: ${errorLowCount}</span>
					</div>
				`;
				
				// Prepend to details (most recent first).
				progressDetails.insertBefore(resultItem, progressDetails.firstChild);
				
				// Scroll to top to show latest result.
				progressDetails.scrollTop = 0;
			} else if (current > 0 && total > 0) {
				// Show progress text.
				const progressInfo = document.createElement('div');
				progressInfo.className = 'pcsc-progress-info';
				progressInfo.textContent = pcscAjax.i18n.scanningPlugin
					.replace('%d', current)
					.replace('%d', total);
				progressDetails.appendChild(progressInfo);
			}
		}
	}

	/**
	 * Escape HTML to prevent XSS.
	 */
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Get list of plugins to scan.
	 */
	function getPluginsList() {
		const data = new FormData();
		data.append('action', 'pcsc_get_plugins_list');
		data.append('nonce', pcscAjax.nonce);

		fetch(pcscAjax.ajaxUrl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		})
		.then(response => response.json())
		.then(response => {
			if (response.success && response.data.plugins) {
				pluginsList = response.data.plugins;
				updateProgress(0, pluginsList.length, pcscAjax.i18n.scanning);
				// Start scanning plugins one by one.
				scanNextPlugin();
			} else {
				const errorMsg = response.data && response.data.message 
					? response.data.message 
					: pcscAjax.i18n.scanError;
				handleScanError(errorMsg);
			}
		})
		.catch(error => {
			console.error('Error getting plugins list:', error);
			handleScanError(pcscAjax.i18n.scanError);
		});
	}

	/**
	 * Scan next plugin in queue.
	 */
	function scanNextPlugin() {
		if (currentIndex >= pluginsList.length) {
			// All plugins scanned, finalize.
			finalizeScan();
			return;
		}

		const plugin = pluginsList[currentIndex];
		updateProgress(currentIndex + 1, pluginsList.length, pcscAjax.i18n.scanning);

		const data = new FormData();
		data.append('action', 'pcsc_scan_single_plugin');
		data.append('nonce', pcscAjax.nonce);
		data.append('plugin_file', plugin.file);
		data.append('plugin_slug', plugin.slug);

		fetch(pcscAjax.ajaxUrl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		})
		.then(response => response.json())
		.then(response => {
			let result = null;
			
			if (response.success && response.data.result) {
				result = response.data.result;
				scannedResults[response.data.plugin_file || plugin.file] = result;
			} else {
				// Check if it's a critical error (Plugin Check not activated).
				if (response.data && response.data.critical) {
					handleScanError(response.data.message || pcscAjax.i18n.scanError);
					return;
				}
				
				// Store error result and continue.
				result = {
					name: plugin.name,
					error: response.data && response.data.message 
						? response.data.message 
						: pcscAjax.i18n.scanError,
					score: 0,
					error_count: 0,
					error_low_count: 0,
					warning_count: 0
				};
				scannedResults[plugin.file] = result;
			}

			// Update progress with plugin result.
			if (result) {
				updateProgress(currentIndex + 1, pluginsList.length, pcscAjax.i18n.scanning, result);
			}

			currentIndex++;
			// Scan next plugin.
			scanNextPlugin();
		})
		.catch(error => {
			console.error('Error scanning plugin:', plugin.name, error);
			// Store error and continue.
			const errorResult = {
				name: plugin.name,
				error: error.message,
				score: 0,
				error_count: 0,
				error_low_count: 0,
				warning_count: 0
			};
			scannedResults[plugin.file] = errorResult;
			
			// Update progress with error result.
			updateProgress(currentIndex + 1, pluginsList.length, pcscAjax.i18n.scanning, errorResult);
			
			currentIndex++;
			scanNextPlugin();
		});
	}

	/**
	 * Finalize scan and save results.
	 */
	function finalizeScan() {
		updateProgress(pluginsList.length, pluginsList.length, pcscAjax.i18n.finalizing);

		const data = new FormData();
		data.append('action', 'pcsc_finalize_scan');
		data.append('nonce', pcscAjax.nonce);
		data.append('scan_results', JSON.stringify(scannedResults));

		fetch(pcscAjax.ajaxUrl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		})
		.then(response => response.json())
		.then(response => {
			if (response.success) {
				// Show success message and reload.
				const progressText = document.querySelector('.pcsc-progress-text');
				if (progressText) {
					progressText.textContent = pcscAjax.i18n.scanComplete;
					progressText.style.color = '#46b450';
				}

				// Redirect after 2 seconds.
				setTimeout(() => {
					window.location.href = response.data.redirect_url;
				}, 2000);
			} else {
				handleScanError(response.data.message || pcscAjax.i18n.scanError);
			}
		})
		.catch(error => {
			console.error('Error finalizing scan:', error);
			handleScanError(pcscAjax.i18n.scanError);
		})
		.finally(() => {
			scanInProgress = false;
			// Re-enable button.
			const submitButton = document.querySelector('form[action*="admin-post.php"] button[type="submit"]');
			if (submitButton) {
				submitButton.disabled = false;
				submitButton.textContent = submitButton.getAttribute('data-original-text') || 
					pcscAjax.i18n.scanning.replace('...', '');
			}
		});
	}

	/**
	 * Handle scan error.
	 */
	function handleScanError(message) {
		const progressContainer = document.querySelector('.pcsc-scan-progress');
		if (progressContainer) {
			progressContainer.innerHTML = `
				<div class="notice notice-error inline">
					<p>${message}</p>
				</div>
			`;
		}

		scanInProgress = false;

		// Re-enable button.
		const submitButton = document.querySelector('form[action*="admin-post.php"] button[type="submit"]');
		if (submitButton) {
			submitButton.disabled = false;
			submitButton.textContent = submitButton.getAttribute('data-original-text') || 
				pcscAjax.i18n.scanning.replace('...', '');
		}
	}
})();
