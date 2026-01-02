# Plugin Check Scan

A WordPress plugin addon for Plugin Check that provides automated security and quality auditing for your entire WordPress installation.

## Overview

Plugin Check Scan extends the functionality of the Plugin Check plugin by:
- Scanning all installed plugins automatically via AJAX (prevents timeouts)
- Generating a security score based on errors and warnings
- Providing real-time progress updates during scanning
- Providing a dashboard widget for quick status overview
- Maintaining scan history for tracking improvements

## Architecture

The plugin follows PSR-4 autoloading standards and uses a modular architecture:

### File Structure

```
plugin-check-scan/
├── plugin-check-scan.php    # Main plugin file
├── composer.json                # Dependencies and PSR-4 autoload config
├── includes/
│   ├── Plugin_Main.php         # Main plugin class (singleton)
│   ├── Scanner.php             # Core scanning logic
│   └── Admin/
│       ├── Admin_Page.php      # Tools menu page
│       └── Dashboard_Widget.php # Dashboard widget
└── assets/
    ├── css/
    │   └── admin.css           # Admin styling
    └── js/
        └── admin.js            # Admin JavaScript

```

### Classes

#### PluginCheckScan\Plugin_Main
Main plugin class using singleton pattern. Initializes all components:
- Scanner instance
- Admin Page (when in admin)
- Dashboard Widget (when in admin)

#### PluginCheckScan\Scanner
Handles all scanning functionality:
- `scan_all_plugins()` - Scans all installed plugins (legacy/synchronous)
- `scan_single_plugin()` - Scans individual plugin (public, used by AJAX)
- `scan_plugin()` - Scans individual plugin (private implementation)
- `calculate_score()` - Calculates security score
- `save_scan_results()` - Saves scan results (public, used by AJAX)
- `get_history()` - Retrieves scan history
- `get_latest_scan()` - Gets most recent scan

#### PluginCheckScan\Admin\Admin_Page
Manages the Tools menu page:
- Displays latest score widget
- Shows scan history table
- Renders detailed results in accordion format
- Handles scan and clear history actions

**AJAX Handlers**:
- `ajax_get_plugins_list()` - Returns list of all plugins to scan
- `ajax_scan_single_plugin()` - Scans a single plugin and returns result
- `ajax_finalize_scan()` - Saves all scan results to database

#### PluginCheckScan\Admin\Dashboard_Widget
Manages the dashboard widget:
- Shows security score with color coding
- Displays last scan information
- Provides quick actions (View Details, Scan Now)
- Shows warnings for low scores

## Scoring System

The scoring system works as follows:

1. **Base Score**: 100 points
2. **Errors**: -5 points each
3. **Warnings**: -2 points each
4. **Minimum**: 0 points
5. **Maximum**: 100 points

### Score Categories

- **90-100** (Excellent): Green - Very good security
- **70-89** (Good): Blue - Acceptable security
- **50-69** (Warning): Yellow - Improvements needed
- **0-49** (Danger): Red - Critical issues

## Plugin Check Integration

The scanner uses Plugin Check with these options:
- `error_severity`: 7
- `warning_severity`: 6
- `include_low_severity_errors`: true

## Data Storage

The plugin stores data in WordPress options:
- `pcs_latest_scan` - Most recent scan results
- `pcs_scan_history` - Array of up to 20 historical scans

## Development

### Requirements

- PHP 7.4+
- WordPress 6.0+
- Composer
- Plugin Check plugin

### Setup

```bash
cd plugin-check-scan
composer install
composer dump-autoload -o
```

### Linting

```bash
composer lint      # Check coding standards
composer format    # Auto-fix coding standards
```

## Usage

### Running a Scan

1. Navigate to **Tools > Plugin Check Scan**
2. Click **Run Scanner Now**
3. Watch the real-time progress bar as each plugin is scanned
4. Automatically redirected to results when complete
5. Review results in the accordion interface

**Note**: Scans run via AJAX, scanning one plugin at a time to prevent timeouts on large installations.

### Viewing Dashboard Widget

The dashboard widget automatically displays:
- Current security score
- Number of plugins scanned
- Last scan timestamp
- Quick action buttons

### Understanding Results

Each plugin in the results shows:
- Plugin name and version
- Individual security score
- Number of errors and warnings
- Expandable details with specific issues

## Hooks and Filters

The plugin follows WordPress best practices and can be extended through standard WordPress hooks.

## Security

The plugin implements:
- Nonce verification for all actions
- Capability checks (requires `manage_options`)
- Data sanitization and escaping
- Secure database operations

## Contributing

Follow WordPress coding standards:
- Use tabs for indentation
- Add proper documentation
- Write secure code
- Test thoroughly

## License

GPL-2.0-or-later

