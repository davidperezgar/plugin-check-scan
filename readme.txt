=== Plugin Check Scan ===

Contributors:      wordpressdotorg
Tested up to:      6.7
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Tags:              plugin best practices, testing, accessibility, performance, security
Requires at least: 6.0
Requires PHP:      7.4
Requires Plugins:  plugin-check

A Scanner Security for your WordPress installation based on Plugin Check Plugin.

== Description ==

Plugin Check Scan is an addon for the Plugin Check plugin that provides automated security and quality auditing for your entire WordPress installation.

**Key Features:**

* **Automated Scanning**: Scan all installed plugins with a single click
* **Security Scoring**: Get an overall security score for your WordPress installation
* **Dashboard Widget**: View your security score directly from the WordPress dashboard
* **Detailed Reports**: See errors and warnings for each plugin with expandable accordion interface
* **Scan History**: Track your security improvements over time with historical data
* **Plugin Check Integration**: Uses official Plugin Check plugin with error-severity=7, warning-severity=6, and include-low-severity-errors options

**How It Works:**

1. Runs Plugin Check tests on all installed plugins
2. Calculates a security score based on errors (5 points deducted each) and warnings (2 points deducted each)
3. Displays results in an easy-to-understand format
4. Maintains history of scans for tracking improvements

**Perfect For:**

* Security auditing of WordPress installations
* Plugin quality monitoring
* Compliance checks
* Development best practices validation

== Installation ==

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* Plugin Check plugin (will be installed automatically if not present)

= Installation from within WordPress =

1. Visit **Plugins > Add New**.
2. Search for **Plugin Check Scan**.
3. Install and activate the Plugin Check Scan plugin.
4. The required Plugin Check plugin will be installed automatically if not present.

= Manual installation =

1. Ensure the Plugin Check plugin is installed and activated
2. Upload the entire `plugin-check-scan` folder to the `/wp-content/plugins/` directory.
3. Visit **Plugins**.
4. Activate the Plugin Check Scan plugin.
5. Run `composer install` in the plugin directory to install dependencies.

== Usage ==

1. Go to **Tools > Plugin Check Scan** in your WordPress admin
2. Click **Run Scanner Now** to perform a full scan of all plugins
3. View your security score and detailed results
4. Check the Dashboard widget for a quick overview of your security status

== Frequently Asked Questions ==

= What is the security score based on? =

The security score starts at 100 and deducts 5 points for each error and 2 points for each warning found by Plugin Check.

= How often should I run scans? =

We recommend running scans weekly or after installing/updating any plugins.

= Does this replace the Plugin Check plugin? =

No, this is an addon that extends Plugin Check's functionality to scan your entire installation and provide scoring.

= What do the different score colors mean? =

* Green (90-100): Excellent security
* Blue (70-89): Good security
* Yellow (50-69): Warning - improvements needed
* Red (0-49): Critical - immediate action required

== Screenshots ==

1. Main scanner page with latest score and scan history
2. Detailed plugin results with accordion interface
3. Dashboard widget showing security score
4. Scan history table

== Changelog ==

= 1.0.0 =

* Initial release of Plugin Check Scan
* Automated scanning of all installed plugins
* Security scoring system
* Dashboard widget
* Scan history tracking
* Detailed error and warning reports
* Integration with Plugin Check plugin

== Upgrade Notice ==

= 1.0.0 =
Initial release. Start auditing your WordPress installation security today!
