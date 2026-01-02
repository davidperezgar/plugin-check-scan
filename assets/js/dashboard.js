/**
 * Plugin Check Scan Dashboard Widget JavaScript
 */

(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initDashboardScan();
	});

	/**
	 * Initialize dashboard widget scan functionality.
	 */
	function initDashboardScan() {
		const scanForm = document.querySelector('#pcsc_dashboard_widget form[action*="admin-post.php"]');
		
		if (!scanForm) {
			return;
		}

		const submitButton = scanForm.querySelector('button[type="submit"]');

		if (!submitButton) {
			return;
		}

		// Store original text.
		const originalText = submitButton.textContent;

		// Replace form submission with redirect to tools page.
		scanForm.addEventListener('submit', function(e) {
			e.preventDefault();

			// Disable button.
			submitButton.disabled = true;
			submitButton.textContent = 'Redirecting...';

			// Redirect to scanner page.
			window.location.href = pcscAjax.redirectUrl;
		});
	}
})();

